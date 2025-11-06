<?php

if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
    $ambiente = 'local';
} else {
    $ambiente = 'producao';
}

if ($ambiente === 'local') {
    $base = '/rendis/';
} else {
    $base = '/';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_page !== 'login.php') { 
    header('Location: ' . $base . 'login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Investimentos</title>

    <link rel="icon" type="image/png" href="<?= $base ?>img/iconeRendis.png">
    
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9O9FvRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0QxZhpcwz/SS0s/j+Q/8i6I/fRPJ+r5z3q+V+L+j+R+r+R+g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Variáveis de cores para consistência */
        :root {
            --primary-color: #007bff; /* Azul padrão do Bootstrap */
            --secondary-color: #6c757d; /* Cinza padrão do Bootstrap */
            --light-bg: #f8f9fa; /* Fundo mais claro para elementos */
            --dark-text: #343a40; /* Texto escuro padrão */
            --main-bg-color: #e9ecef; /* Um cinza bem clarinho para o body */
            --danger-color: #dc3545; /* Cor padrão de perigo do Bootstrap */
        }

        /* Estilos do body */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: row;
            background-color: var(--main-bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
        }

        /* Sidebar para Desktop (visível apenas em telas maiores que md) */
        .sidebar-desktop {
            min-width: 220px;
            max-width: 220px;
            background-color: var(--dark-text);
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            display: none; /* Ocultar por padrão */
            flex-direction: column; /* Para os links ficarem em coluna */
        }
        @media (min-width: 768px) { /* Visível a partir de telas médias (md) */
            .sidebar-desktop {
                display: flex; /* Volta a exibir como flex column */
            }
        }

        .sidebar-desktop .app-title {
            color: white;
            font-size: 1.3rem;
            font-weight: bold;
            padding: 10px 20px 25px;
        }
        .sidebar-desktop .app-title i { /* Estilo para o ícone no título */
            margin-right: 8px;
            color: var(--primary-color); /* Cor do ícone no título */
        }
        .sidebar-desktop a {
            color: #ffffff;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-desktop a:last-child {
            border-bottom: none;
        }
        .sidebar-desktop a:hover, .sidebar-desktop a.active {
            background-color: #495057;
            border-left: 5px solid var(--primary-color);
            padding-left: 15px;
        }

        /* Toggler para Mobile (visível apenas em telas menores que md) */
        .navbar-toggler-mobile {
            display: block; /* Visível por padrão em mobile */
            margin: 10px;
            align-self: flex-start;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 1.2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1050;
        }
        @media (min-width: 768px) { /* Ocultar em telas médias (md) e maiores */
            .navbar-toggler-mobile {
                display: none;
            }
        }

        /* Offcanvas Menu para Mobile */
        .offcanvas-mobile {
            background-color: var(--dark-text);
            color: white;
            width: 80% !important;
            max-width: 300px;
        }
        .offcanvas-mobile .offcanvas-header {
            background-color: #343a40;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .offcanvas-mobile .offcanvas-title {
            color: white;
            font-weight: bold;
        }
        .offcanvas-mobile .offcanvas-title i { /* Estilo para o ícone no título do offcanvas */
            margin-right: 8px;
            color: var(--primary-color);
        }
        .offcanvas-mobile .offcanvas-body a {
            color: #ffffff;
            padding: 15px 20px;
            display: block;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .offcanvas-mobile .offcanvas-body a:last-child {
            border-bottom: none;
        }
        .offcanvas-mobile .offcanvas-body a:hover, .offcanvas-mobile .offcanvas-body a.active {
            background-color: #495057;
            border-left: 5px solid var(--primary-color);
            padding-left: 15px;
        }

        /* Conteúdo principal */
        .content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }
        @media (max-width: 767.98px) {
            body {
                flex-direction: column;
            }
            .content {
                padding: 20px;
                padding-top: 60px; /* Adiciona um padding superior para não ficar colado no toggler */
            }
        }

        /* Estilos de elementos específicos do conteúdo */
        .container {
            margin-top: 0px;
            margin-bottom: 0px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        h2 {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2.5rem !important;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        h4 {
            color: var(--dark-text);
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 12px;
            margin-top: 3rem !important;
            margin-bottom: 2rem !important;
            text-align: center;
        }

        form.bg-white, .card {
            transition: all 0.3s ease-in-out;
            border-radius: 8px;
            border: 1px solid #e2e6ea;
            background-color: white;
        }

        form.bg-white:hover, .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.2) !important;
        }

        table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table-dark thead th {
            background-color: var(--primary-color) !important;
            color: white !important;
            font-size: 0.95rem;
            padding: 1.2rem 1rem;
            border-bottom: none;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
            cursor: pointer;
        }

        .table tbody td {
            vertical-align: middle;
            padding: 0.8rem 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .table .btn-sm {
            padding: .3rem .6rem;
            font-size: .75rem;
            line-height: 1;
            border-radius: .2rem;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-outline-danger {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }
        .btn-outline-danger:hover {
            background-color: var(--danger-color);
            color: white;
        }

        /* REMOVIDO: Esta regra causava problemas de largura */
        /* .w-100 {
            width: 110% !important;
        } */
    </style>
</head>
<body>
    <!-- Botão Toggler para Mobile -->
    <button class="navbar-toggler-mobile d-md-none fixed-top" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
        ☰ Menu
    </button>

    <!-- Sidebar para Desktop (oculto em mobile) -->
    <div class="sidebar-desktop d-none d-md-flex flex-column">
        <div class="app-title"><img src="<?= $base ?>img/iconeRendis.png" style="width: 30px"></> Rendis</div> <!-- Ícone atualizado -->
        <a href="<?= $base ?>home.php">🏠 Início</a>
        <a href="<?= $base ?>investimentos/investimentos.php">📈 Investimentos</a>
        <a href="<?= $base ?>perfil/perfil.php">👤 Perfil</a>
        <a href="#">📊 Simulações</a>
        <a href="<?= $base ?>mensalidades/mensalidades.php">📄 Mensalidades</a>
        <a href="<?= $base ?>gastos/gastos.php">💸 Gastos</a>
        <a href="<?= $base ?>ganhos/ganhos.php">💰 Ganhos</a>
        <a href="<?= $base ?>logout.php" class="text-danger">🚪 Sair</a>
    </div>

    <!-- Offcanvas Menu para Mobile -->
    <div class="offcanvas offcanvas-start offcanvas-mobile d-md-none" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel" style=" padding-top: 50px;"><img src="<?= $base ?>img/iconeRendis.png" style="width: 30px"> Rendis Menu</h5> <!-- Ícone atualizado -->
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <a href="<?= $base ?>home.php">🏠 Início</a>
            <a href="<?= $base ?>investimentos/investimentos.php">📈 Investimentos</a>
            <a href="<?= $base ?>perfil/perfil.php">👤 Perfil</a>
            <a href="#">📊 Simulações</a>
            <a href="<?= $base ?>mensalidades/mensalidades.php">📄 Mensalidades</a>
            <a href="<?= $base ?>gastos/gastos.php">💸 Gastos</a>
            <a href="<?= $base ?>ganhos/ganhos.php">💰 Ganhos</a>
            <a href="<?= $base ?>logout.php" class="text-danger">🚪 Sair</a>
        </div>
    </div>

    <div class="content">
        <!-- jQuery CDN -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
        <!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- Bootstrap JS Bundle CDN -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <!-- Importação de chart.js para gráficos -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
