<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Rendis</title>
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
            overflow-x: hidden;
            position: relative;
        }

        .bg-icons {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.1;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            align-content: space-around;
        }

        .bg-icons i {
            font-size: 48px;
            color: var(--primary-color);
            transform: rotate(-15deg);
            animation: float 25s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-40px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        .register-box {
            width: 100%;
            max-width: 480px;
            padding: 40px;
            text-align: center;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            margin: 20px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .input-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.05em;
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
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="bg-icons">
        <i class='bx bx-trending-up'></i>
        <i class='bx bx-coin-stack'></i>
        <i class='bx bx-line-chart'></i>
        <i class='bx bx-dollar-circle'></i>
        <i class='bx bx-world'></i>
        <i class='bx bxs-bank'></i>
    </div>

    <div class="register-box">
        <div style="width: 80px; height: 80px; margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;">
            <img src="{{ asset('images/logo.png') }}" alt="Rendis Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <h1 style="color: white; font-size: 1.5rem; font-weight: 800; margin-bottom: 8px;">Criar nova conta</h1>
        <p style="color: #94a3b8; margin-bottom: 32px;">Junte-se ao Rendis e controle seu tempo.</p>

        @if($errors->any())
            <div style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; margin-bottom: 24px; border-radius: 12px; font-size: 0.85rem; text-align: left;">
                <i class='bx bx-error-circle'></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="input-group">
                <label>NOME COMPLETO</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Ex: João Silva" required autofocus>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="input-group">
                    <label>E-MAIL</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com" required>
                </div>
                <div class="input-group">
                    <label>CPF</label>
                    <input type="text" name="cpf" id="cpf-input" value="{{ old('cpf') }}" placeholder="000.000.000-00" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="input-group">
                    <label>SENHA</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="input-group">
                    <label>CONFIRMAR SENHA</label>
                    <input type="password" name="password_confirmation" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-auth">
                Finalizar Cadastro
            </button>
        </form>
        
        <div class="auth-footer">
            Já possui uma conta? <a href="{{ route('login') }}">Fazer login</a>
        </div>
    </div>

    <script src="https://unpkg.com/imask"></script>
    <script>
        const cpfElement = document.getElementById('cpf-input');
        const maskOptions = {
            mask: '000.000.000-00'
        };
        IMask(cpfElement, maskOptions);
    </script>
</body>
</html>
