<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Balance;
use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\PendingPayment;
use App\Models\PendingPayout;


class XenditController extends Controller
{
    protected $xenditKey;

    protected $appUrl;
    protected $baseUrlV3 = 'https://api.xendit.co/v2/invoices ';
    protected $baseUrlV2 = 'https://api.xendit.co';

    protected $webhookUrl = 'https://api.xendit.co/v3/payments';

    public function __construct()
    {
        $this->xenditKey = env('Xendit_API_KEY');
        $this->appUrl = env('APP_URL');
    }

    public function viewTopup()
    {
        $user = Auth::user();
        $balance = Balance::where('user_id', Auth::id())->first();
        return view('payment.topup', compact('balance'));
    }

    public function payoutsView() 
    {
        $user = Auth::user();
        $balance = Balance::where('user_id', Auth::id())->first();
        return view('payment.payout', compact('balance'));
    }

    // Helper function to create Guzzle Client with Basic Auth
    protected function getClient($versionHeader = null)
    {
        $config = [
            'base_uri' => $this->baseUrlV3,
            'auth' => [$this->xenditKey, ''],
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ];

        if ($versionHeader) {
            $config['headers']['api-version'] = $versionHeader;
        }

        return new Client($config);
    }


    // Create Payment Request
    public function createPaymentRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        $user = Auth::user()->load('student');

        if (!$user) {
            return redirect()
            ->route('login.view')
            ->response()
            ->json(['error' => 'Unauthorized'], 401);
        }

        $user_name = $user->student->name;
        $user_email = $user->email;
        $username = $user->username;

        $timestamp = now()->timestamp;
        $referenceId = "order-" . Str::random(22) . '-' . $timestamp;

        PendingPayment::create([
            'external_id' => $referenceId,
            'user_id' => $user->id,
            'amount' => $request->amount,
        ]);

        \Log::info("User email used for payment", ['email' => $user_email,'username'=> $username, 'name'=> $user_name]);

        $payload = [
            "external_id" => $referenceId,
            "amount" => $request->amount,
            "description" => "Top Up",
            "invoice_duration" => 86400,
            "customer" => [
                "given_names" => $user_name,
                "surname" => $username,
                "email" => $user_email,
            ],
            "success_redirect_url" => $this->appUrl . "/user",
            "failure_redirect_url" => $this->appUrl . "/payment",
            "currency" => "IDR",
            "metadata" => [
                "store_branch" => "Bali"
            ],
        ];

        try {
            $client = new Client();
            $response = $client->post($this->baseUrlV3, [
                'auth' => [$this->xenditKey, ''],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody(), true);
            return redirect($body['invoice_url']);
            
        } catch (\Exception $e) {
            \Log::error('Xendit Payment Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function handleWebhook(Request $request)
    {
        \Log::info("Xendit Webhook Triggered", [
            'timestamp' => now()->toDateTimeString(),
            'headers' => $request->header(),
        ]);
        \Log::info("Xendit Webhook Received", $request->all());
        

        $status = $request->input('status');
        $externalId = $request->input('external_id');

        if ($status === 'PAID' && $externalId) {
            // Retrieve the pending payment
            $pendingPayment = PendingPayment::where('external_id', $externalId)->first();

            if (!$pendingPayment) {
                \Log::warning("No matching pending payment found for external_id", compact('externalId'));
                return response()->json(['status' => 'received']);
            }

            $userId = $pendingPayment->user_id;
            $amount = $pendingPayment->amount;

            try {
                DB::transaction(function () use ($userId, $amount, $externalId) {
                    $balance = Balance::firstOrCreate(['user_id' => $userId]);
                    $balance->increment('amount', $amount);

                    BalanceTransaction::create([
                        'user_id' => $userId,
                        'balance_id' => $balance->id,
                        'transaction_type' => 'top_up',
                        'amount' => $amount,
                        'reference_type' => 'XenditInvoice',
                        'reference_id' => $externalId,
                    ]);
                });

                // Optional: Delete or mark as completed
                $pendingPayment->delete(); // or update status field
            } catch (\Exception $e) {
                \Log::error("Failed to process webhook", [
                    'error' => $e->getMessage(),
                    'external_id' => $externalId
                ]);
                return response()->json(['status' => 'error'], 500);
            }
        }


        // ====== PAYOUT HANDLING ======
        if ($status === 'COMPLETED' || $status === 'FAILED') {
            $pendingPayout = PendingPayout::where('external_id', $externalId)->first();

            if (!$pendingPayout) {
                \Log::warning("No matching pending payout found for external_id", compact('externalId'));
                return response()->json(['status' => 'received']);
            }

            $userId = $pendingPayout->user_id;
            $amount = $pendingPayout->amount;

            try {
                DB::transaction(function () use ($userId, $amount, $externalId, $status) {
                    if ($status === 'COMPLETED') {
                        // Deduct from balance (already done initially, but can log transaction again)
                        $balance = Balance::firstOrCreate(['user_id' => $userId]);
                        BalanceTransaction::create([
                            'user_id' => $userId,
                            'balance_id' => $balance->id,
                            'transaction_type' => 'payout',
                            'amount' => $amount,
                            'reference_type' => 'XenditPayout',
                            'reference_id' => $externalId,
                            'metadata' => json_encode([
                                'status' => 'completed',
                                'updated_at' => now()
                            ]),
                        ]);
                    }

                    if ($status === 'FAILED') {
                        // Refund the amount back to user
                        $balance = Balance::firstOrCreate(['user_id' => $userId]);
                        $balance->increment('amount', $amount); // Add money back
                        BalanceTransaction::create([
                            'user_id' => $userId,
                            'balance_id' => $balance->id,
                            'transaction_type' => 'refund',
                            'amount' => $amount,
                            'reference_type' => 'XenditPayoutRefund',
                            'reference_id' => $externalId . '-refund',
                            'metadata' => json_encode([
                                'original_reference' => $externalId,
                                'reason' => 'Payout failed or rejected'
                            ]),
                        ]);
                    }
                });

                // Delete or mark as processed
                $pendingPayout->delete();
            } catch (\Exception $e) {
                \Log::error("Failed to process payout webhook", [
                    'error' => $e->getMessage(),
                    'external_id' => $externalId
                ]);
                return response()->json(['status' => 'error'], 500);
            }
        }
        return response()->json(['status' => 'received']);
    }

    // Create Payout
    public function submitPayouts(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'email' => 'required|email',
        ]);

        $user = Auth::user();

        if (!$user) {
            return redirect()
            ->route('login.view')
            ->response()
            ->json(['error' => 'Unauthorized'], 401);
        }

        $amount = $request->input('amount');
        $email = $user->email;
        $payoutUrl = null; 
        try {
            DB::transaction(function () use ($user, $amount, $request, &$payoutUrl, $email) {
                $balance = Balance::firstOrCreate(['user_id' => $user->id]);

                if ($balance->amount < $amount) {
                    throw new \Exception("Insufficient balance for payout.");
                }

                $balance->decrement('amount', $amount);

                $externalId = 'payout-' . Str::random(8) . '-' . time();

                PendingPayout::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'external_id' => $externalId,
                ]);

                $payload = [
                    'external_id' => $externalId,
                    'amount' => $amount,
                    'email' => $email,
                ];

                $client = new Client();
                $response = $client->post("{$this->baseUrlV2}/payouts", [
                    'auth' => [$this->xenditKey, ''],
                    'json' => $payload,
                ]);

                $body = json_decode($response->getBody(), true);

                // Set the URL to be used outside
                $payoutUrl = $body['payout_url'];
            });

            // Now redirect after transaction
            if ($payoutUrl) {
                return redirect($payoutUrl);
            }

            return back()->withErrors(['error' => 'Failed to get payout URL']);
        } catch (\Exception $e) {
            \Log::error("Xendit Payout Failed", ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to process payout']);
        }
    }
}