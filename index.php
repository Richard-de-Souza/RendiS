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
<body class="bg-gradient-to-r from-blue-500 to-purple-600 min-h-screen flex items-center justify-center font-sans p-4">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <h1 id="authTitle" class="text-3xl font-bold text-center text-gray-800 mb-8">Fazer Login</h1>

        <!-- Formulário de Login -->
        <form id="loginForm" class="space-y-6">
            <div>
                <label for="loginEmail" class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                <input type="email" id="loginEmail" name="email" class="shadow appearance-none border rounded-xl w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="seuemail@exemplo.com" required>
            </div>
            <div>
                <label for="loginPassword" class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
                <input type="password" id="loginPassword" name="password" class="shadow appearance-none border rounded-xl w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="********" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl focus:outline-none focus:shadow-outline transition duration-200 ease-in-out transform hover:scale-105 w-full">Entrar</button>
            </div>
            <p class="text-center text-gray-600 text-sm mt-4">
                Não tem uma conta? <a href="#" id="toggleToRegister" class="text-blue-600 hover:text-blue-800 font-bold">Crie uma aqui!</a>
            </p>
        </form>

        <!-- Formulário de Registro (inicialmente oculto) -->
        <form id="registerForm" class="space-y-6 hidden">
            <div>
                <label for="registerEmail" class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                <input type="email" id="registerEmail" name="email" class="shadow appearance-none border rounded-xl w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="seuemail@exemplo.com" required>
            </div>
            <div>
                <label for="registerPassword" class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
                <input type="password" id="registerPassword" name="password" class="shadow appearance-none border rounded-xl w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="********" required>
                <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres.</p>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl focus:outline-none focus:shadow-outline transition duration-200 ease-in-out transform hover:scale-105 w-full">Registrar</button>
            </div>
            <p class="text-center text-gray-600 text-sm mt-4">
                Já tem uma conta? <a href="#" id="toggleToLogin" class="text-purple-600 hover:text-purple-800 font-bold">Faça login aqui!</a>
            </p>
        </form>

        <div id="authStatus" class="mt-6 p-4 bg-gray-100 rounded-lg text-center text-sm">
            <p class="text-gray-600 font-semibold">Status: Não autenticado (via banco de dados).</p>
        </div>
    </div>
</body>
</html>
