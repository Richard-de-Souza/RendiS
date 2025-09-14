<?php
// perfil_controller.php

// Inicia a sessão PHP para acessar o ID do usuário logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
include('../banco.php');

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar o perfil.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$usuario_id = $_SESSION['user_id']; // Obtém o ID do usuário logado

// Verifica se a conexão com o banco de dados é válida
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit();
}
$conn->set_charset("utf8mb4");

// Verifica se a função foi especificada na requisição
if (!isset($_REQUEST['funcao']) || empty($_REQUEST['funcao'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma função especificada.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$funcao = $_REQUEST['funcao'];

// Roteador de funções
if ($funcao === 'carregar') {
    carregarUsuario($conn, $usuario_id);
} else if ($funcao === 'salvar') {
    salvarUsuario($conn, $usuario_id);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida: ' . htmlspecialchars($funcao)], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Carrega os dados do perfil do usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function carregarUsuario($conn, $usuario_id) {
    try {
        // Busca o usuário pelo ID da sessão
        $stmt = $conn->prepare("SELECT id, nome, email, nascimento, salario FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $dados = $result->fetch_assoc();
            echo json_encode(['sucesso' => true, 'dados' => utf8ize($dados)], JSON_UNESCAPED_UNICODE);
        } else {
            // Se o usuário não for encontrado (o que não deveria acontecer se ele está logado),
            // retorna null ou um erro.
            echo json_encode(['sucesso' => false, 'mensagem' => 'Perfil do usuário não encontrado.'], JSON_UNESCAPED_UNICODE);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar usuário: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Salva (atualiza) os dados do perfil do usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function salvarUsuario($conn, $usuario_id) {
    try {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $nascimento = $_POST['nascimento'] ?? null; // Data no formato YYYY-MM-DD
        $salario = filter_input(INPUT_POST, 'salario', FILTER_VALIDATE_FLOAT);

        // Validação básica dos dados
        if (empty($nome) || !$email || $salario === false || $salario < 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos. Verifique nome, e-mail e salário.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validação do formato da data de nascimento (opcional, mas recomendado)
        if ($nascimento && !DateTime::createFromFormat('Y-m-d', $nascimento)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de data de nascimento inválido. Use YYYY-MM-DD.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Antes de atualizar, busca o salário atual do usuário para verificar se houve mudança
        $stmtSalario = $conn->prepare("SELECT salario FROM usuarios WHERE id = ?");
        $stmtSalario->bind_param("i", $usuario_id);
        $stmtSalario->execute();
        $resultSalario = $stmtSalario->get_result();
        $salarioAtual = $resultSalario->fetch_assoc()['salario'];
        $stmtSalario->close();


        // Atualiza o perfil do usuário logado
        $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, nascimento=?, salario=? WHERE id=?");
        // 'ssdsi' -> string, string, date(string), double, integer
        $stmt->bind_param("ssdsi", $nome, $email, $nascimento, $salario, $usuario_id);

        if ($stmt->execute()) {
            // Se o salário mudou, deleta o registro do mês atual da tabela de cache para forçar recálculo
            if (round($salario, 2) != round($salarioAtual, 2)) {
                $dataAtual = new DateTime();
                $ano = $dataAtual->format('Y');
                $mes = $dataAtual->format('m');
                
                $stmtDelete = $conn->prepare("DELETE FROM mes_resumo_financeiro WHERE usuario_id = ? AND ano = ? AND mes = ?");
                $stmtDelete->bind_param('iii', $usuario_id, $ano, $mes);
                $stmtDelete->execute();
                $stmtDelete->close();
            }

            if ($stmt->affected_rows > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil salvo com sucesso.'], JSON_UNESCAPED_UNICODE);
            } else {
                // Isso pode acontecer se os dados enviados forem idênticos aos já existentes
                echo json_encode(['sucesso' => true, 'mensagem' => 'Nenhuma alteração feita no perfil.'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar perfil: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Garante que todos os dados em um array (ou string) estejam em UTF-8.
 * @param mixed $data O dado a ser convertido.
 * @return mixed O dado convertido para UTF-8.
 */
function utf8ize($data) {
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $data[$k] = utf8ize($v);
        }
    } elseif (is_string($data)) {
        return mb_convert_encoding($data, 'UTF-8', 'auto');
    }
    return $data;
}

?>
