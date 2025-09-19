<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Seu App</title>
    <!-- icone da pagina -->
    <link rel="shortcut icon" href="img/iconeRendis.png" type="image/x-icon">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <!-- SweetAlert2 CDN para alertas amigáveis -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery CDN para facilitar requisições AJAX -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script type="module">
        // Referências aos elementos do DOM
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const toggleToRegisterBtn = document.getElementById('toggleToRegister');
        const toggleToLoginBtn = document.getElementById('toggleToLogin');
        const authStatusDiv = document.getElementById('authStatus');

        // Função para mostrar mensagens usando SweetAlert2
        const showMessage = (icon, title, text) => {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'OK',
                customClass: {
                    container: 'font-sans',
                    popup: 'rounded-lg shadow-lg',
                    header: 'border-b-2 border-gray-200',
                    title: 'text-2xl font-bold text-gray-800',
                    content: 'text-gray-600',
                    confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline',
                }
            });
        };

        // Simula o status de autenticação (pode ser ajustado para verificar um cookie de sessão PHP)
        authStatusDiv.innerHTML = `<p class="text-gray-600 font-semibold">Status: Não autenticado (via banco de dados).</p>`;


        // Lógica de Login (agora via index_controller.php)
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = loginForm.email.value;
            const password = loginForm.password.value;

            const formData = {
                funcao: 'login',
                email: email,
                password: password
            };

            try {
                const data = await $.post('index_controller.php', formData, null, 'json'); // Alterado para 'index_controller.php'

                if (data.sucesso) {
                    showMessage('success', 'Login Bem-Sucedido!', data.mensagem);
                    authStatusDiv.innerHTML = `<p class="text-green-600 font-semibold">Autenticado com sucesso! ID do Usuário: ${data.usuario_id}</p>`;
                    // Redirecionamento para o index.php após o login
                    window.location.href = 'home.php';
                } else {
                    showMessage('error', 'Erro de Login', data.mensagem);
                }
            } catch (error) {
                showMessage('error', 'Erro de Conexão', 'Não foi possível conectar ao servidor. Verifique sua rede e o controller PHP.');
                console.error("Erro na requisição de login:", error);
            }
        });

        // Lógica de Registro (agora via index_controller.php)
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = registerForm.email.value;
            const password = registerForm.password.value;

            const formData = {
                funcao: 'registrar',
                email: email,
                password: password
            };

            try {
                const data = await $.post('index_controller.php', formData, null, 'json'); // Alterado para 'index_controller.php'

                if (data.sucesso) {
                    showMessage('success', 'Registro Bem-Sucedido!', data.mensagem + ' Você já pode fazer login.');
                    toggleToLoginBtn.click();
                } else {
                    showMessage('error', 'Erro de Registro', data.mensagem);
                }
            } catch (error) {
                showMessage('error', 'Erro de Conexão', 'Não foi possível conectar ao servidor. Verifique sua rede e o controller PHP.');
                console.error("Erro na requisição de registro:", error);
            }
        });

        // Alternar entre formulários de Login e Registro
        toggleToRegisterBtn.addEventListener('click', () => {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            document.getElementById('authTitle').innerText = 'Criar Conta';
            loginForm.reset();
            registerForm.reset();
        });

        toggleToLoginBtn.addEventListener('click', () => {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            document.getElementById('authTitle').innerText = 'Fazer Login';
            loginForm.reset();
            registerForm.reset();
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl w-full max-w-sm border border-gray-200 dark:border-gray-700">
        <h1 id="authTitle" class="text-3xl font-extrabold text-center text-gray-900 dark:text-white mb-8">
            Fazer Login
        </h1>

        <form id="loginForm" class="space-y-6" aria-labelledby="authTitle">
            <div>
                <label for="loginEmail" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">E-mail</label>
                <input type="email" id="loginEmail" name="email" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150" placeholder="seuemail@exemplo.com" required>
            </div>
            <div>
                <label for="loginPassword" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Senha</label>
                <input type="password" id="loginPassword" name="password" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150" placeholder="********" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transition duration-200 ease-in-out">
                    Entrar
                </button>
            </div>
            <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
                Não tem uma conta? <a href="#" id="toggleToRegister" class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-400 font-bold">Crie uma aqui!</a>
            </p>
        </form>

        <form id="registerForm" class="space-y-6 hidden" aria-labelledby="authTitle">
            <div>
                <label for="registerEmail" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">E-mail</label>
                <input type="email" id="registerEmail" name="email" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150" placeholder="seuemail@exemplo.com" required>
            </div>
            <div>
                <label for="registerPassword" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Senha</label>
                <input type="password" id="registerPassword" name="password" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150" placeholder="********" required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mínimo de 6 caracteres.</p>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transition duration-200 ease-in-out">
                    Registrar
                </button>
            </div>
            <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
                Já tem uma conta? <a href="#" id="toggleToLogin" class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-400 font-bold">Faça login aqui!</a>
            </p>
        </form>

        <div id="authStatus" class="mt-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-xl text-center text-sm border border-gray-200 dark:border-gray-600">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">Status: Não autenticado (via banco de dados).</p>
        </div>
    </div>
</body>
</html>