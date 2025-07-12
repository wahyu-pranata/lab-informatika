<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\BalanceTransaction;
use App\Models\PurchasedProject;

class PurchaseController extends Controller
{
    public function purchaseProject(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'amount' => 'required|numeric|min:1',
        ]);

        // Check if the user is authenticated
        $user = Auth::user();
        $amount = $request->amount;

        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to view this page.');
        }

        $balance = Balance::where('user_id', $user->id)->first();

        if ($balance && $balance->amount >= $request->amount) {
            // Deduct the amount from the user's balance
            $balance->decrement('amount', $amount);

            BalanceTransaction::create([
                'user_id' => $user->id,
                'balance_id' => $balance->id,
                'transaction_type' => 'purchase',
                'amount' => -$amount,
                'reference_type' => 'ProjectPurchase',
                'reference_id' => $request->project_id,
                'metadata' => json_encode(['project_id' => $request->project_id]),
            ]);
            // TODO: Tambah Logic buat nambah user id ke table purchased project

            PurchasedProject::create([
                'user_id' => $user->id,
                'project_id' => $request->project_id,
            ]);


            return redirect()->back()->with('success', 'Project purchased successfully.');
        } else {
            return redirect('topup.view')->back()->with('error', 'Insufficient balance to purchase this project.');
        }
        
    }
}
