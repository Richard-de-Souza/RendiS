<?php
// mensalidades_controller.php

// Inicia a sessão PHP para acessar o ID do usuário logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
include('../banco.php');

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar as mensalidades.']);
    exit();
}

$usuario_id = $_SESSION['user_id']; // Obtém o ID do usuário logado

// Verifica se a conexão com o banco de dados é válida
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.']);
    exit();
}

// Garantir que a tabela exista, incluindo a coluna usuario_id e FOREIGN KEY
$conn->query("
    CREATE TABLE IF NOT EXISTS mensalidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        descricao VARCHAR(255) NOT NULL,
        valor DECIMAL(12,2) NOT NULL,
        inicio DATE NOT NULL,
        duracao INT NOT NULL,
        usuario_id INT NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");


// Verifica se a variável 'funcao' foi passada na requisição
if(!isset($_REQUEST['funcao']) || empty($_REQUEST['funcao'])){
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma função especificada para executar.']);
    exit(); 
}

// Obtém a função da requisição
$funcao = $_REQUEST['funcao'];

// Bloco if/else if para chamar as funções, mantendo seu estilo
// Passa o $usuario_id para as funções que precisam dele
if($funcao == 'listar'){
    listarMensalidades($conn, $usuario_id);
} 
else if($funcao == 'criar'){
    criarMensalidade($conn, $usuario_id);
} 
else if($funcao == 'deletar'){
    deletarMensalidade($conn, $usuario_id);
} 
else {
    // Caso a função não seja reconhecida
    echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida ou não implementada: ' . htmlspecialchars($funcao)]);
    exit();
}

// --- Funções para o CRUD de Mensalidades ---

// Função para garantir que os dados sejam UTF-8 (mantive a sua função)
function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, 'UTF-8', 'auto'); // 'auto' é mais robusto
    }
    return $mixed;
}

/**
 * Lista todas as mensalidades do usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function listarMensalidades($conn, $usuario_id) {
    try {
        // Adicionado filtro por usuario_id
        $sql = "SELECT id, descricao, valor, inicio, duracao FROM mensalidades WHERE usuario_id = ? ORDER BY inicio DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'listarMensalidades': " . $conn->error);
        }
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $retorno = [];

        if ($resultado->num_rows > 0) {
            while($linha = $resultado->fetch_assoc()) {
                $retorno[] = utf8ize($linha);
            }
            echo json_encode($retorno);
        } else {
            echo json_encode([]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao listar mensalidades: ' . $e->getMessage()]);
    }
}

/**
 * Cria uma nova mensalidade para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function criarMensalidade($conn, $usuario_id) {
    try {
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $inicio = filter_input(INPUT_POST, 'inicio', FILTER_SANITIZE_STRING); // YYYY-MM-DD
        $duracao = filter_input(INPUT_POST, 'duracao', FILTER_VALIDATE_INT);

        if (empty($descricao) || $valor === false || empty($inicio) || $duracao === false || $duracao <= 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos para a criação da mensalidade.']);
            return;
        }

        // Adicionado usuario_id na inserção
        $sql = "INSERT INTO mensalidades (descricao, valor, inicio, duracao, usuario_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'criarMensalidade': " . $conn->error);
        }

        $stmt->bind_param("sdsii", $descricao, $valor, $inicio, $duracao, $usuario_id);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Mensalidade adicionada com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao adicionar mensalidade: ' . $stmt->error]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar criação de mensalidade: ' . $e->getMessage()]);
    }
}

/**
 * Deleta uma mensalidade específica por ID para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function deletarMensalidade($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido para exclusão.']);
            return;
        }

        // Adicionado filtro por usuario_id na exclusão
        $sql = "DELETE FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'deletarMensalidade': " . $conn->error);
        }

        $stmt->bind_param("ii", $id, $usuario_id); // 'i' indica que é um inteiro
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Mensalidade excluída com sucesso!']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Mensalidade não encontrada ou não pertence ao usuário logado.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir mensalidade: ' . $stmt->error]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar exclusão de mensalidade: ' . $e->getMessage()]);
    }
}

?>
