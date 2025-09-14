<?php
// gastos_controller.php

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
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar os gastos.']);
    exit();
}

$usuario_id = $_SESSION['user_id']; // Obtém o ID do usuário logado

// Verifica se a conexão com o banco de dados é válida
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.']);
    exit();
}

// Garantir que as tabelas 'gastos' e 'mes_resumo_financeiro' existam
$conn->query("
    CREATE TABLE IF NOT EXISTS gastos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        descricao VARCHAR(255) NOT NULL,
        valor DECIMAL(12,2) NOT NULL,
        data DATE NOT NULL,
        usuario_id INT NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
$conn->query("
    CREATE TABLE IF NOT EXISTS mes_resumo_financeiro (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        ano INT NOT NULL,
        mes INT NOT NULL,
        salario DECIMAL(10, 2) NOT NULL,
        descontos_clt DECIMAL(10, 2) NOT NULL,
        total_mensalidades DECIMAL(12, 2) NOT NULL,
        total_gastos DECIMAL(12, 2) NOT NULL,
        total_ganhos DECIMAL(12, 2) NOT NULL,
        dinheiro_disponivel DECIMAL(12, 2) NOT NULL,
        saldo_anterior DECIMAL(12, 2) NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY idx_unique_mes (usuario_id, ano, mes)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");


// Verifica se a variável 'funcao' foi passada na requisição
if (!isset($_REQUEST['funcao']) || empty($_REQUEST['funcao'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma função especificada para executar.']);
    exit();
}

$funcao = $_REQUEST['funcao'];

/**
 * Funções Auxiliares
 */

/**
 * Valida se uma string é uma data no formato 'YYYY-MM-DD'.
 * @param string $data A string da data.
 * @return bool True se a data for válida, False caso contrário.
 */
function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

/**
 * Envia uma resposta JSON de sucesso.
 * @param string $mensagem A mensagem de sucesso.
 * @param array $dados Opcional. Dados a serem incluídos na resposta.
 */
function enviarSucessoJson($mensagem, $dados = []) {
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem, 'dados' => $dados], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Envia uma resposta JSON de erro.
 * @param string $mensagem A mensagem de erro.
 */
function enviarErroJson($mensagem) {
    echo json_encode(['sucesso' => false, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Limpa o resumo financeiro em cache para um mês e ano específicos.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 * @param int $ano Ano da data.
 * @param int $mes Mês da data.
 */
function limparResumoCache($conn, $usuario_id, $ano, $mes) {
    try {
        $sql = "DELETE FROM mes_resumo_financeiro WHERE usuario_id = ? AND ano = ? AND mes = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iii', $usuario_id, $ano, $mes);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Logar o erro, mas não interromper a execução, pois é uma ação secundária
        error_log("Erro ao limpar cache do resumo financeiro: " . $e->getMessage());
    }
}


/**
 * Lista todos os gastos do usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function listarGastos($conn, $usuario_id) {
    try {
        $sql = "SELECT id, descricao, valor, data FROM gastos WHERE usuario_id = ? ORDER BY data DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'listarGastos': " . $conn->error);
        }
        $stmt->bind_param('i', $usuario_id);
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução da query 'listarGastos': " . $stmt->error);
        }
        $res = $stmt->get_result();
        $retorno = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $retorno[] = $row; 
            }
        }
        $stmt->close();
        // CORRIGIDO: Agora retorna um JSON com a chave 'sucesso' e os dados
        enviarSucessoJson('Gastos carregados com sucesso!', $retorno);
    } catch (Exception $e) {
        enviarErroJson('Erro ao listar gastos: ' . $e->getMessage());
    }
}

/**
 * Busca gastos por mês e ano para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function gastosMes($conn, $usuario_id) {
    try {
        $ano = filter_input(INPUT_GET, 'ano');
        $mes = filter_input(INPUT_GET, 'mes');
        if (!$ano || !$mes || $mes < 1 || $mes > 12) {
            enviarErroJson('Parâmetros de ano ou mês inválidos.');
        }
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio)); // Último dia do mês
        $sql = "SELECT id, descricao, valor, DATE_FORMAT(data, '%d/%m/%Y') as data FROM gastos WHERE data BETWEEN ? AND ? AND usuario_id = ? ORDER BY data DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'gastosMes': " . $conn->error);
        }
        $stmt->bind_param('ssi', $dataInicio, $dataFim, $usuario_id);
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução da query 'gastosMes': " . $stmt->error);
        }
        $res = $stmt->get_result();
        $retorno = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $retorno[] = $row; 
            }
        }
        $stmt->close();
        echo json_encode($retorno);
        exit();
    } catch (Exception $e) {
        enviarErroJson('Erro ao buscar gastos do mês: ' . $e->getMessage());
    }
}

/**
 * Busca um gasto específico por ID para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function buscarGasto($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            enviarErroJson('ID do gasto inválido.');
        }
        $sql = "SELECT id, descricao, valor, data FROM gastos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'buscarGasto': " . $conn->error);
        }
        $stmt->bind_param('ii', $id, $usuario_id);
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução da query 'buscarGasto': " . $stmt->error);
        }
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $gasto = $res->fetch_assoc();
            enviarSucessoJson('Gasto encontrado com sucesso.', $gasto);
        } else {
            enviarErroJson('Gasto não encontrado ou não pertence ao usuário logado.');
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao buscar gasto: ' . $e->getMessage());
    }
}

/**
 * Cria ou atualiza um gasto para o usuário logado e limpa o cache do mês.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param string $funcao 'criar' ou 'atualizar'.
 * @param int $usuario_id ID do usuário logado.
 */
function criarOuAtualizarGasto($conn, $funcao, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $data = filter_input(INPUT_POST, 'data', FILTER_DEFAULT);
        if (empty($descricao) || $valor === false || $valor < 0 || empty($data) || !validarData($data)) {
            enviarErroJson('Dados inválidos ou incompletos para a operação. Verifique descrição, valor e data (YYYY-MM-DD).');
        }
        // Extrai o ano e o mês da data para limpar o cache
        $dataObj = new DateTime($data);
        $ano = $dataObj->format('Y');
        $mes = $dataObj->format('n');
        
        if ($funcao == 'criar') {
            $sql = "INSERT INTO gastos (descricao, valor, data, usuario_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query 'criarGasto': " . $conn->error);
            }
            $stmt->bind_param('sdsi', $descricao, $valor, $data, $usuario_id);
            if ($stmt->execute()) {
                limparResumoCache($conn, $usuario_id, $ano, $mes);
                enviarSucessoJson('Gasto adicionado com sucesso!');
            } else {
                enviarErroJson('Erro ao adicionar gasto: ' . $stmt->error);
            }
            $stmt->close();
        } else { // $funcao == 'atualizar'
            if (!$id) {
                enviarErroJson('ID inválido para atualização.');
            }
            // Antes de atualizar, pegamos a data original para limpar o cache do mês anterior também
            $sqlBuscaData = "SELECT data FROM gastos WHERE id = ? AND usuario_id = ?";
            $stmtBuscaData = $conn->prepare($sqlBuscaData);
            $stmtBuscaData->bind_param('ii', $id, $usuario_id);
            $stmtBuscaData->execute();
            $resBuscaData = $stmtBuscaData->get_result();
            if ($resBuscaData->num_rows > 0) {
                $dataOriginal = new DateTime($resBuscaData->fetch_assoc()['data']);
                limparResumoCache($conn, $usuario_id, $dataOriginal->format('Y'), $dataOriginal->format('n'));
            }
            $stmtBuscaData->close();

            $sql = "UPDATE gastos SET descricao=?, valor=?, data=? WHERE id=? AND usuario_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query 'atualizarGasto': " . $conn->error);
            }
            $stmt->bind_param('sdsii', $descricao, $valor, $data, $id, $usuario_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    limparResumoCache($conn, $usuario_id, $ano, $mes);
                    enviarSucessoJson('Gasto atualizado com sucesso!');
                } else {
                    enviarErroJson('Nenhuma alteração feita ou gasto não encontrado ou não pertence ao usuário logado.');
                }
            } else {
                enviarErroJson('Erro ao atualizar gasto: ' . $stmt->error);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        enviarErroJson('Erro ao processar criação/atualização de gasto: ' . $e->getMessage());
    }
}

/**
 * Deleta um gasto específico por ID para o usuário logado e limpa o cache do mês.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function deletarGasto($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            enviarErroJson('ID inválido para exclusão.');
        }

        // Antes de deletar, buscamos a data do gasto para limpar o cache
        $sqlBuscaData = "SELECT data FROM gastos WHERE id = ? AND usuario_id = ?";
        $stmtBuscaData = $conn->prepare($sqlBuscaData);
        $stmtBuscaData->bind_param('ii', $id, $usuario_id);
        $stmtBuscaData->execute();
        $resBuscaData = $stmtBuscaData->get_result();

        if ($resBuscaData->num_rows > 0) {
            $data = new DateTime($resBuscaData->fetch_assoc()['data']);
            $ano = $data->format('Y');
            $mes = $data->format('n');
        } else {
            enviarErroJson('Gasto não encontrado para exclusão.');
        }
        $stmtBuscaData->close();
        
        $sql = "DELETE FROM gastos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'deletarGasto': " . $conn->error);
        }
        $stmt->bind_param('ii', $id, $usuario_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Deleta o resumo do mês do cache
                limparResumoCache($conn, $usuario_id, $ano, $mes);
                enviarSucessoJson('Gasto excluído com sucesso!');
            } else {
                enviarErroJson('Gasto não encontrado ou não pertence ao usuário logado para exclusão.');
            }
        } else {
            enviarErroJson('Erro ao excluir gasto: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao processar exclusão de gasto: ' . $e->getMessage());
    }
}

// --- Roteador Principal ---
if ($funcao == 'listar') {
    listarGastos($conn, $usuario_id);
} else if ($funcao == 'gastos_mes') {
    gastosMes($conn, $usuario_id);
} else if ($funcao == 'buscar') {
    buscarGasto($conn, $usuario_id);
} else if ($funcao == 'criar' || $funcao == 'atualizar') {
    criarOuAtualizarGasto($conn, $funcao, $usuario_id);
} else if ($funcao == 'deletar') {
    deletarGasto($conn, $usuario_id);
} else {
    enviarErroJson('Função inválida ou não implementada: ' . htmlspecialchars($funcao));
}
exit();
?>
