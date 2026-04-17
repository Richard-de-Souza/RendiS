<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rendis</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #0f172a;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* Fundo com Ícones Financeiros */
        .bg-icons {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.15;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            align-content: space-around;
        }

        .bg-icons i {
            font-size: 64px;
            color: var(--primary-color);
            transform: rotate(-15deg);
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-30px) rotate(10deg); }
            50% { transform: translateY(-60px) rotate(0deg); }
            75% { transform: translateY(-30px) rotate(-10deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            text-align: center;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
        }

        .logo-container i {
            font-size: 40px;
            color: white;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #94a3b8;
        }

        .input-group input {
            width: 100%;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            color: white;
            outline: none;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        .btn-auth {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
            background: #0ea5e9;
        }

        .auth-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Ícones de fundo decorativos -->
    <div class="bg-icons">
        <i class='bx bx-trending-up'></i>
        <i class='bx bx-coin-stack'></i>
        <i class='bx bx-line-chart'></i>
        <i class='bx bx-dollar-circle'></i>
        <i class='bx bx-stats'></i>
        <i class='bx bx-pie-chart-alt-2'></i>
        <i class='bx bxs-bank'></i>
        <i class='bx bxs-briefcase'></i>
        <i class='bx bxs-credit-card'></i>
        <i class='bx bx-world'></i>
    </div>

    <div class="login-box">
        <div class="logo-container" style="background: none; box-shadow: none;">
            <img src="{{ asset('images/logo.png') }}" alt="Rendis Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <h1 style="color: white; font-size: 1.75rem; font-weight: 800; margin-bottom: 8px;">Rendis Platform</h1>
        <p style="color: #94a3b8; margin-bottom: 32px;">Onde seu tempo vale ouro.</p>

        @if($errors->any())
            <div style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; margin-bottom: 24px; border-radius: 12px; font-size: 0.85rem; text-align: left;">
                <i class='bx bx-error-circle'></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="input-group">
                <label>E-MAIL CORPORATIVO</label>
                <input type="email" name="email" value="{{ old('email', 'admin@rendis.com') }}" placeholder="seu@email.com" required autofocus>
            </div>
            
            <div class="input-group">
                <label>SENHA DE ACESSO</label>
                <input type="password" name="password" value="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth">
                Acessar Dashboard
            </button>
        </form>
        
        <div class="auth-footer">
            Ainda não tem conta? <a href="{{ route('register') }}">Criar nova conta</a>
        </div>

        <div style="margin-top: 16px; font-size: 0.75rem; color: #475569;">
            Demo: admin@rendis.com (senha: password)
        </div>
    </div>
</body>
</html>
