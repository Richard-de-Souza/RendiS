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

$API_KEY = 'be9f9538dfca404bbd4620b1f2e34d27'; // Nota: Em produção, a API Key deve ser armazenada de forma mais segura.

$funcao = $_REQUEST['funcao'] ?? '';

// Roteador principal de funções
if ($funcao == 'listar') {
    listar($conn, $API_KEY, $usuario_id);
} 
else if ($funcao == 'criar' || $funcao == 'atualizar') { // Adicionado 'atualizar' aqui, caso o frontend use essa função
    criarOuAtualizar($conn, $usuario_id);
} 
else if ($funcao == 'deletar') {
    deletar($conn, $usuario_id);
} 
else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida ou não especificada.']);
}

/**
 * Lista todos os investimentos do usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param string $apiKey Chave da API para obter preços atuais.
 * @param int $usuario_id ID do usuário logado.
 */
function listar($conn, $apiKey, $usuario_id) {
    $dados = [];
    // Filtra investimentos pelo id_usuario
    $stmt = $conn->prepare("SELECT id, ticker, quantidade, preco_medio FROM investimentos WHERE id_usuario = ? ORDER BY ticker ASC");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $precoAtual = obterPrecoAtual($row['ticker'], $apiKey);
        $row['preco_atual'] = $precoAtual !== null ? $precoAtual : 0;
        $dados[] = $row;
    }
    $stmt->close();

    echo json_encode($dados);
}

/**
 * Obtém o preço atual de um ticker de uma API externa.
 * @param string $ticker Símbolo da ação (ex: PETR4).
 * @param string $apiKey Chave da API Twelve Data.
 * @return float|null Preço atual da ação ou null em caso de erro.
 */
function obterPrecoAtual($ticker, $apiKey) {
    $ticker = strtoupper(trim($ticker));

    // Ajuste para tickers brasileiros (adiciona .SA para B3)
    if (preg_match('/^[A-Z0-9]{4}[0-9]$/', $ticker)) { // Ex: ABCD3, ABCD4
        $ticker .= '.SA';
    }

    $url = "https://api.twelvedata.com/time_series?symbol={$ticker}&interval=1min&apikey={$apiKey}&outputsize=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos
    $resultado = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($resultado === FALSE) {
        error_log("Erro cURL ao obter preço para {$ticker}: " . $curl_error);
        return null;
    }

    $dados = json_decode($resultado, true);

    if ($http_code !== 200) {
        error_log("Erro HTTP {$http_code} da API Twelve Data para {$ticker}: " . json_encode($dados));
        return null;
    }

    if (isset($dados['values'][0]['close'])) {
        return floatval($dados['values'][0]['close']);
    } else {
        error_log("Erro API Twelve Data: Dados de preço 'close' não encontrados para {$ticker}. Resposta: " . json_encode($dados));
        return null;
    }
}

/**
 * Cria ou atualiza um investimento para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function criarOuAtualizar($conn, $usuario_id) {
    $ticker = strtoupper(trim($_POST['ticker'] ?? ''));
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $preco_medio = filter_input(INPUT_POST, 'preco_medio', FILTER_VALIDATE_FLOAT);

    if (!$ticker || $quantidade === false || $quantidade < 0 || $preco_medio === false || $preco_medio < 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos. Verifique ticker, quantidade e preço médio.']);
        return;
    }

    // Verifica se já existe um investimento com este ticker para ESTE USUÁRIO
    $stmt = $conn->prepare("SELECT id FROM investimentos WHERE ticker = ? AND id_usuario = ?");
    $stmt->bind_param("si", $ticker, $usuario_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Atualiza o investimento existente
        $stmt->bind_result($investimento_id);
        $stmt->fetch();
        $stmt->close(); // Fecha o statement anterior

        $stmt = $conn->prepare("UPDATE investimentos SET quantidade=?, preco_medio=? WHERE id=? AND id_usuario=?");
        $stmt->bind_param("dsii", $quantidade, $preco_medio, $investimento_id, $usuario_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento atualizado com sucesso!']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma alteração feita ou investimento não encontrado.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar investimento: ' . $stmt->error]);
        }
    } else {
        // Cria um novo investimento
        $stmt->close(); // Fecha o statement anterior caso não tenha encontrado

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
 * Deleta um investimento específico por ID para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function deletar($conn, $usuario_id) {
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
