<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendis - Controle Financeiro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <!-- Include Custom Stylesheet -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <!-- Include BoxIcons for easy icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>
        const storedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Overlay para mobile -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="{{ asset('images/logo.png') }}" alt="Rendis Logo" style="height: 32px; width: auto; object-fit: contain;">
                Rendis
            </div>
            
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class='bx bx-grid-alt' style="font-size: 20px;"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('transactions.index') }}" class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <i class='bx bx-transfer' style="font-size: 20px;"></i>
                    <span>Transações</span>
                </a>
                <a href="{{ route('subscriptions.index') }}" class="nav-item {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                    <i class='bx bx-calendar-star' style="font-size: 20px;"></i>
                    <span>Mensalidades</span>
                </a>
                <a href="{{ route('investments') }}" class="nav-item {{ request()->routeIs('investments') ? 'active' : '' }}">
                    <i class='bx bx-line-chart' style="font-size: 20px;"></i>
                    <span>Investimentos</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class='bx bx-user-circle' style="font-size: 20px;"></i>
                    <span>Meu Perfil</span>
                </a>

                @if(auth()->check() && auth()->user()->role && auth()->user()->role->slug === 'admin')
                <div style="margin-top: 24px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); padding-left: 16px;">Administração</span>
                </div>
                
                <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class='bx bx-group' style="font-size: 20px;"></i>
                    <span>Gestão de Usuários</span>
                </a>
                <a href="{{ route('admin.users.calendar') }}" class="nav-item {{ request()->routeIs('admin.users.calendar') ? 'active' : '' }}">
                    <i class='bx bx-calendar-event' style="font-size: 20px;"></i>
                    <span>Calendário de Auditoria</span>
                </a>
                <a href="{{ route('admin.roles') }}" class="nav-item {{ request()->routeIs('admin.roles') ? 'active' : '' }}">
                    <i class='bx bx-shield-quarter' style="font-size: 20px;"></i>
                    <span>Gestão de Perfis</span>
                </a>
                <a href="{{ route('admin.investments') }}" class="nav-item {{ request()->routeIs('admin.investments') ? 'active' : '' }}">
                    <i class='bx bx-line-chart' style="font-size: 20px;"></i>
                    <span>Config. Investimentos</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button id="mobile-menu-btn" class="mobile-nav-toggle">
                        <i class='bx bx-menu'></i>
                    </button>
                    <div class="header-search" style="color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                        <i class='bx bx-search'></i> <span style="display: none;" class="d-sm-inline">Buscar...</span>
                    </div>
                </div>
                
                <div class="header-profile" style="display: flex; align-items: center; gap: 16px;">
                    <button id="theme-toggle" style="background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 24px; display: flex; align-items: center;" title="Alternar Tema">
                        <i class='bx bx-moon' id="theme-icon"></i>
                    </button>
                    
                    @auth
                        <div style="display: flex; flex-direction: column; align-items: flex-end;">
                            <span style="font-weight: 600; font-size: 14px;">{{ auth()->user()->name }}</span>
                            <span style="font-size: 11px; color: var(--text-secondary); background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">{{ auth()->user()->role->name ?? 'Usuário' }}</span>
                        </div>
                        <div style="width: 38px; height: 38px; background-color: var(--primary-color); border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 6px rgba(14,165,233, 0.4);">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <a href="{{ route('profile.edit') }}" style="margin-left: 16px; color: var(--text-secondary); font-size: 24px; display: flex; align-items: center; text-decoration: none; transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'" title="Meu Perfil">
                            <i class='bx bx-cog'></i>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="margin-left: 8px;">
                            @csrf
                            <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--danger-color); font-size: 20px; display: flex; align-items: center;" title="Sair">
                                <i class='bx bx-log-out-circle'></i>
                            </button>
                        </form>
                    @endauth
                </div>
            </header>
            
            <div class="page-container animate-fade-in">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Layout Scripts (Responsivity and Theme) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const themeBtn = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');

            // Toggle Mobile Sidebar
            function toggleSidebar() {
                if (sidebar) sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('active');
            }

            if(mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);

            // Toggle Dark Mode
            function updateThemeIcon() {
                if (document.documentElement.getAttribute('data-theme') === 'dark') {
                    themeIcon.classList.replace('bx-moon', 'bx-sun');
                } else {
                    themeIcon.classList.replace('bx-sun', 'bx-moon');
                }
            }
            
            if (themeIcon) updateThemeIcon();

            if (themeBtn) {
                themeBtn.addEventListener('click', () => {
                    let newTheme = 'light';
                    if (document.documentElement.getAttribute('data-theme') !== 'dark') {
                        newTheme = 'dark';
                    }
                    
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcon();
                });
            }
        });
    </script>

    <x-modal />

    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if(window.openModal) {
                window.openModal({
                    type: 'success',
                    title: 'Sucesso',
                    message: '{!! addslashes(session('success')) !!}'
                });
            }
        });
    </script>
    @endif

    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if(window.openModal) {
                window.openModal({
                    type: 'error',
                    title: 'Ops! Algo deu errado',
                    message: `{!! implode('<br>', $errors->all()) !!}`
                });
            }
        });
    </script>
    @endif
</body>
</html>
