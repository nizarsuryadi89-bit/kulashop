<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KULASHOP</title>
    @php
        $company = \App\Models\Company::first();
        $bgUrl = $company && $company->login_background ? asset('storage/' . $company->login_background) : asset('img/login-bg.jpg');
        $logoUrl = $company && $company->logo ? asset('storage/' . $company->logo) : asset('img/logo-kulashop.jpg');
    @endphp
    <link rel="icon" href="{{ $logoUrl }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        
        /* LEFT SIDE - FORM */
        .login-sidebar {
            flex: 0 0 450px;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 3rem;
            position: relative;
            z-index: 10;
            box-shadow: 10px 0 30px rgba(0,0,0,0.05);
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 4rem;
        }
        
        .brand-logo img {
            height: 40px;
            object-fit: contain;
        }
        
        .brand-logo-text {
            font-weight: 800;
            font-size: 1.25rem;
            color: #1e293b;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .brand-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .welcome-title {
            font-weight: 800;
            font-size: 2.5rem;
            color: #0f172a;
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            background-color: #f8fafc;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background-color: #ffffff;
        }
        
        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #94a3b8;
            border-radius: 10px 0 0 10px;
        }
        
        .form-control.border-start-0 {
            border-left: none;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            border: none;
            padding: 0.85rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.2px;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .footer-credit {
            margin-top: auto;
            text-align: center;
            padding-top: 2rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .footer-credit a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        /* RIGHT SIDE - GRAPHIC */
        .login-banner {
            flex: 1;
            background: url('{{ $bgUrl }}') center center no-repeat;
            background-size: cover;
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            padding: 4rem;
        }
        
        .login-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.2) 0%, rgba(15, 23, 42, 0.8) 100%);
        }
        
        .glass-card {
            position: relative;
            z-index: 2;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            max-width: 500px;
            color: white;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .quote-icon {
            color: #f59e0b;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .glass-title {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 1rem;
        }
        
        .glass-subtitle {
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        @media (max-width: 991px) {
            .login-sidebar {
                flex: 1;
                max-width: 100%;
                justify-content: center;
            }
            .login-banner {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- LEFT PANEL -->
    <div class="login-sidebar">
        <!-- Brand Logo -->
        <div class="brand-logo">
            <div class="bg-white rounded-3 shadow-sm p-2 border border-light">
                <img src="{{ $logoUrl }}" alt="Kulashop Logo" style="height: 32px; width: 32px; object-fit: contain;">
            </div>
            <div>
                <h1 class="brand-logo-text">KULASHOP</h1>
                <div class="brand-subtitle">Integrated Management Core</div>
            </div>
        </div>

        <!-- Welcome Text -->
        <h2 class="welcome-title">Welcome back!</h2>
        <p class="welcome-subtitle">👋 Selamat Datang, silakan masuk untuk melanjutkan.</p>

        <!-- Error Alert -->
        @if(session('error'))
            <div class="alert alert-danger rounded-3 border-0 shadow-sm py-2 px-3 mb-4">
                <span class="text-sm" style="font-size: 0.85rem;">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger rounded-3 border-0 shadow-sm py-2 px-3 mb-4">
                <ul class="mb-0 text-sm ps-3" style="font-size: 0.85rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="fas fa-envelope fs-6"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="Masukkan alamat email" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="fas fa-lock fs-6"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Masukkan password" required>
                    <span class="input-group-text bg-transparent border-start-0" style="cursor: pointer;"><i class="fas fa-eye-slash fs-6"></i></span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label text-muted" style="font-size: 0.85rem;" for="remember">Remember me</label>
                </div>
                <a href="#" class="text-decoration-none text-primary fw-medium" style="font-size:0.85rem;">Lupa sandi?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow">
                Sign in to your account <i class="fas fa-arrow-right ms-2 fs-7"></i>
            </button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">Butuh panduan sistem? <a href="#" class="text-decoration-none fw-medium">Baca Disini</a></small>
        </div>

        <!-- Footer Credit -->
        <div class="footer-credit border-top mt-auto pt-3">
            <p class="mb-1 fw-bold text-dark">KULASHOP &copy; {{ date('Y') }}</p>
            <p class="mb-0">Designed & Powered by <a href="https://zarnizar.com" target="_blank">zarnizar.com</a></p>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-banner">
        <div class="glass-card">
            <i class="fas fa-quote-left quote-icon"></i>
            <h3 class="glass-title">Membangun ekosistem bisnis digital yang lebih bermakna bersama.</h3>
            <p class="glass-subtitle mb-0">Kulashop Core System &bull; Next Generation E-commerce ERP Management & Reconciliation</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
