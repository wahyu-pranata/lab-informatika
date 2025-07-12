<!DOCTYPE html>
<html lang="id"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/payment.css') }}">
</head>
<body data-new-gr-c-s-check-loaded="14.1242.0" data-gr-ext-installed="">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Lab-Informatika</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="/about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Account</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h1 class="display-4">Top Up</h1>
                    <p class="lead">Top Up balance to your account</p>
                    <p class="lead">Jumlah Saldo akun anda: @if($balance)
                        {{ $balance->amount }}
                    @else
                            0
                        @endif
                    </p>
                </div>
            </div>
    
            <!-- Form for Withdraw -->
            <form id="form" method="POST" enctype="multipart/form-data" action="{{ route('payment.create') }}">
                @csrf
    
                <!-- Quick Select Dropdown -->
                <div class="mt-2 text-start">
                    <label for="quick_amount" class="form-label">Pilih Jumlah</label>
                    <select id="quick_amount" class="form-control" onchange="updateCustomAmount()">
                        <option value="">-- Pilih --</option>
                        <option value="10000">Rp 10.000</option>
                        <option value="25000">Rp 25.000</option>
                        <option value="50000">Rp 50.000</option>
                        <option value="100000">Rp 100.000</option>
                        <option value="manual">→ Masukkan jumlah manual</option> <!-- Manual Option -->
                    </select>
                </div>
    
                <!-- Manual Amount Input (Hidden by Default) -->
                <div id="manual_amount_container" class="mt-3 text-start d-none">
                    <label for="manual_amount" class="form-label">Jumlah Manual</label>
                    <input type="number" id="manual_amount" min="10000" placeholder="ex: 69000" class="form-control" />
                </div>
    
                <!-- Final Hidden Input to Submit Selected Amount -->
                <input type="hidden" name="amount" id="amount" />
    
                <!-- Submit Button -->
                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-sky-500 hover:bg-sky-600">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </section>
    
    
    <!-- Back to top button -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    
    <script src="{{ asset('js/topup.js') }}"></script> 
    
</body><grammarly-desktop-integration data-grammarly-shadow-root="true"></grammarly-desktop-integration></html>