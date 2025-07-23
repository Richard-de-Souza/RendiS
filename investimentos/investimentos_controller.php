<?php
// investimentos_controller.php

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
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar os investimentos.']);
    exit();
}

$usuario_id = $_SESSION['user_id']; // Obtém o ID do usuário logado

// Verifica se a conexão com o banco de dados é válida
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados.']);
    exit();
}

// Criar tabela 'investimentos' se não existir, incluindo a coluna id_usuario
$conn->query("
    CREATE TABLE IF NOT EXISTS investimentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticker VARCHAR(10) NOT NULL,
        quantidade INT NOT NULL,
        preco_medio DECIMAL(10,2) NOT NULL,
        id_usuario INT NOT NULL,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$funcao = $_REQUEST['funcao'] ?? '';

// Roteador principal de funções
if ($funcao == 'listar') {
    listar($conn, $usuario_id);
}
else if ($funcao == 'criar') { // Função específica para CRIAR
    criarInvestimento($conn, $usuario_id);
}
else if ($funcao == 'atualizar') { // Função específica para ATUALIZAR
    atualizarInvestimento($conn, $usuario_id);
}
else if ($funcao == 'deletar') {
    deletarInvestimento($conn, $usuario_id);
}
else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida ou não especificada.']);
}

/**
 * Lista todos os investimentos do usuário logado diretamente do banco de dados.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function listar($conn, $usuario_id) {
    $dados = [];
    $stmt = $conn->prepare("SELECT id, ticker, quantidade, preco_medio FROM investimentos WHERE id_usuario = ? ORDER BY ticker ASC");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        // preco_atual será sempre igual ao preco_medio para fins de exibição simulada, já que não há API externa.
        $row['preco_atual'] = $row['preco_medio']; 
        $dados[] = $row;
    }
    $stmt->close();

    echo json_encode($dados);
}

/**
 * Cria um novo investimento para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function criarInvestimento($conn, $usuario_id) {
    $ticker = strtoupper(trim($_POST['ticker'] ?? ''));
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $preco_medio = filter_input(INPUT_POST, 'preco_medio', FILTER_VALIDATE_FLOAT);

    if (!$ticker || $quantidade === false || $quantidade < 0 || $preco_medio === false || $preco_medio < 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos. Verifique ticker, quantidade e preço médio.']);
        return;
    }

    // Verifica se já existe um investimento com este ticker para ESTE USUÁRIO antes de criar
    $stmt = $conn->prepare("SELECT id FROM investimentos WHERE ticker = ? AND id_usuario = ?");
    $stmt->bind_param("si", $ticker, $usuario_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Já existe um investimento com este ticker. Use a função de edição para atualizá-lo.']);
    } else {
        $stmt->close(); // Fecha o statement anterior

        $stmt = $conn->prepare("INSERT INTO investimentos (ticker, quantidade, preco_medio, id_usuario) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sidi", $ticker, $quantidade, $preco_medio, $usuario_id);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento cadastrado com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar investimento: ' . $stmt->error]);
        }
    }
    $stmt->close();
}

/**
 * Atualiza um investimento existente para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function atualizarInvestimento($conn, $usuario_id) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $ticker = strtoupper(trim($_POST['ticker'] ?? '')); // Ticker pode ser enviado para validação, mas não para atualização
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $preco_medio = filter_input(INPUT_POST, 'preco_medio', FILTER_VALIDATE_FLOAT);

    if (!$id || !$ticker || $quantidade === false || $quantidade < 0 || $preco_medio === false || $preco_medio < 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos para atualização.']);
        return;
    }

    // A atualização não muda o ticker, apenas quantidade e preco_medio para o ID e usuário corretos
    $stmt = $conn->prepare("UPDATE investimentos SET quantidade=?, preco_medio=? WHERE id=? AND id_usuario=?");
    $stmt->bind_param("dsii", $quantidade, $preco_medio, $id, $usuario_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento atualizado com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma alteração feita ou investimento não encontrado.']);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar investimento: ' . $stmt->error]);
    }
    $stmt->close();
}

/**
 * Deleta um investimento específico por ID para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function deletarInvestimento($conn, $usuario_id) { // Renomeada para clareza
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido para exclusão.']);
        return;
    }

    // Deleta o investimento, garantindo que pertença ao id_usuario
    $stmt = $conn->prepare("DELETE FROM investimentos WHERE id=? AND id_usuario=?");
    $stmt->bind_param("ii", $id, $usuario_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento excluído com sucesso.']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Investimento não encontrado ou não pertence ao usuário logado.']);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir investimento: ' . $stmt->error]);
    }
    $stmt->close();
}

?>