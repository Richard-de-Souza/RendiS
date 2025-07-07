<?php
// ganhos_controller.php

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
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar os ganhos.']);
    exit();
}

$usuario_id = $_SESSION['user_id']; // Obtém o ID do usuário logado

// Garantir que a tabela 'ganhos' exista e tenha a coluna 'usuario_id'
$conn->query("
    CREATE TABLE IF NOT EXISTS ganhos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        descricao VARCHAR(255) NOT NULL,
        valor DECIMAL(12,2) NOT NULL,
        data DATE NOT NULL,
        usuario_id INT NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$funcao = $_REQUEST['funcao'] ?? '';

// Lógica para salvar ou atualizar um ganho
if ($funcao == 'salvar' || $funcao == 'atualizar') {
    $id = $_POST['id'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? 0;
    $data = $_POST['data'] ?? date('Y-m-d');

    if (empty($descricao) || empty($valor) || empty($data)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Todos os campos (descrição, valor, data) são obrigatórios.']);
        exit;
    }

    if ($funcao == 'salvar' && empty($id)) {
        // Insere um novo ganho associado ao usuario_id
        $stmt = $conn->prepare("INSERT INTO ganhos (descricao, valor, data, usuario_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $descricao, $valor, $data, $usuario_id);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Ganho cadastrado com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar ganho: ' . $stmt->error]);
        }
        exit;
    } elseif ($funcao == 'atualizar' && $id) {
        // Atualiza um ganho existente, garantindo que pertença ao usuario_id
        $stmt = $conn->prepare("UPDATE ganhos SET descricao=?, valor=?, data=? WHERE id=? AND usuario_id=?");
        $stmt->bind_param("sdsii", $descricao, $valor, $data, $id, $usuario_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Ganho atualizado com sucesso!']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Ganho não encontrado ou não pertence ao usuário logado.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar ganho: ' . $stmt->error]);
        }
        exit;
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetros inválidos para salvar/atualizar.']);
        exit;
    }
}

// Lógica para buscar um ganho específico
if ($funcao == 'buscar') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID não informado.']);
        exit;
    }

    // Busca um ganho específico, garantindo que pertença ao usuario_id
    $stmt = $conn->prepare("SELECT * FROM ganhos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $dados = $res->fetch_assoc();
        echo json_encode(['sucesso' => true, 'dados' => $dados]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ganho não encontrado ou não pertence ao usuário logado.']);
    }
    $stmt->close();
    exit;
}

// Lógica para excluir um ganho
if ($funcao == 'excluir') {
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID não informado.']);
        exit;
    }

    // Exclui um ganho, garantindo que pertença ao usuario_id
    $stmt = $conn->prepare("DELETE FROM ganhos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Ganho excluído com sucesso.']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ganho não encontrado ou não pertence ao usuário logado.']);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir ganho: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Lógica para listar ganhos do mês
if ($funcao == 'ganhos_mes') {
    $ano = $_GET['ano'] ?? date('Y');
    $mes = $_GET['mes'] ?? date('m');

    $inicio = "$ano-$mes-01";
    $fim = date("Y-m-t", strtotime($inicio));

    // Lista ganhos do mês, filtrando por usuario_id
    $stmt = $conn->prepare("SELECT id, descricao, valor, DATE_FORMAT(data, '%d/%m/%Y') as data FROM ganhos WHERE data BETWEEN ? AND ? AND usuario_id = ? ORDER BY data DESC");
    $stmt->bind_param("ssi", $inicio, $fim, $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $ganhos = [];
    while ($row = $res->fetch_assoc()) {
        $ganhos[] = $row;
    }

    echo json_encode($ganhos);
    $stmt->close();
    exit;
}

// Resposta padrão para função inválida ou não especificada
echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida ou não especificada.']);

?>
