<?php
// login_controller.php

// Inicia a sessão PHP para armazenar o estado do usuário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
include("banco.php"); // Certifique-se de que este caminho está correto para o seu ambiente

// Define o cabeçalho para que a resposta seja JSON
header('Content-Type: application/json');

// Verifica se a conexão com o banco de dados é válida
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// --- Função: Verificar e Criar Tabela ---
function checkAndCreateUsersTable($conn) {
    $tableName = "usuarios";
    $checkTableSql = "SHOW TABLES LIKE '$tableName'";
    $result = $conn->query($checkTableSql);

    if ($result && $result->num_rows == 0) {
        $createTableSql = "
            CREATE TABLE $tableName (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL, -- Armazena o hash da senha
                nome VARCHAR(255) NULL,
                nascimento DATE NULL,
                salario DECIMAL(10, 2) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($conn->query($createTableSql) === TRUE) {
            error_log("Tabela '$tableName' criada com sucesso.");
        } else {
            error_log("Erro ao criar tabela '$tableName': " . $conn->error);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico: Não foi possível criar a tabela de usuários.']);
            exit();
        }
    }
}

// Chama a função para verificar e criar a tabela ao iniciar o script
checkAndCreateUsersTable($conn);
// --- Fim da Função de Criação de Tabela ---


// Verifica se a função foi especificada na requisição
if (!isset($_REQUEST['funcao']) || empty($_REQUEST['funcao'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma função especificada.']);
    exit();
}

$funcao = $_REQUEST['funcao'];

// Redireciona para a função apropriada
if ($funcao === 'login') {
    loginUsuario($conn);
} else if ($funcao === 'registrar') {
    registrarUsuario($conn);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida: ' . htmlspecialchars($funcao)]);
    exit();
}

/**
 * Função para autenticar um usuário no banco de dados.
 * Recebe email e senha via POST.
 */
function loginUsuario($conn) {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['password'] ?? '';

        if (!$email || empty($senha)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail e senha são obrigatórios.']);
            return;
        }

        $stmt = $conn->prepare("SELECT id, email, senha FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            if (password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido: Define a sessão do usuário
                $_SESSION['user_id'] = $usuario['id'];
                echo json_encode(['sucesso' => true, 'mensagem' => 'Login bem-sucedido!', 'usuario_id' => $usuario['id']]);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Credenciais inválidas.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Credenciais inválidas.']);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao fazer login: ' . $e->getMessage()]);
    }
}

/**
 * Função para registrar um novo usuário no banco de dados.
 * Recebe email e senha via POST.
 */
function registrarUsuario($conn) {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['password'] ?? '';

        if (!$email || empty($senha)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail e senha são obrigatórios.']);
            return;
        }

        $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Este e-mail já está em uso.']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO usuarios (email, senha) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $senhaHash);

        if ($stmt->execute()) {
            // Registro bem-sucedido: Opcionalmente, você pode logar o usuário automaticamente aqui
            // $_SESSION['user_id'] = $conn->insert_id; // Pega o ID do usuário recém-criado
            echo json_encode(['sucesso' => true, 'mensagem' => 'Registro bem-sucedido!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registrar: ' . $stmt->error]);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
    }
}

?>
