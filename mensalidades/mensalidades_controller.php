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

// Garantir que as tabelas existam, incluindo as colunas e FOREIGN KEYs
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

// Obtém a função da requisição
$funcao = $_REQUEST['funcao'];

/**
 * Funções Auxiliares
 */

/**
 * Garante que os dados sejam UTF-8.
 * @param mixed $mixed O dado a ser convertido.
 * @return mixed O dado convertido para UTF-8.
 */
function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, 'UTF-8', 'auto');
    }
    return $mixed;
}

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
 * Funções para o CRUD de Mensalidades.
 * A partir de agora, a lógica de geração de resumos futuros será feita no front-end.
 * Este controlador apenas limpa o cache dos meses relevantes.
 */

/**
 * Limpa o resumo financeiro em cache para o mês da mensalidade e todos os meses futuros.
 *
 * Esta função é crucial para manter a integridade dos dados. Sempre que uma
 * mensalidade é criada, atualizada ou excluída, o resumo em cache para o
 * mês da alteração e todos os meses subsequentes é removido. Isso força o
 * sistema a recalcular os resumos na próxima vez que forem acessados,
 * garantindo que as mensalidades fixas sejam refletidas corretamente nos
 * balanços futuros.
 *
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 * @param string $dataString Data de referência (YYYY-MM-DD).
 */
function limparResumoCache($conn, $usuario_id, $dataString) {
    try {
        $dataObj = new DateTime($dataString);
        $ano = $dataObj->format('Y');
        $mes = $dataObj->format('n');

        $sql = "DELETE FROM mes_resumo_financeiro WHERE usuario_id = ? AND (ano > ? OR (ano = ? AND mes >= ?))";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iiii', $usuario_id, $ano, $ano, $mes);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Logar o erro, mas não interromper a execução, pois é uma ação secundária
        error_log("Erro ao limpar cache do resumo financeiro: " . $e->getMessage());
    }
}

/**
 * Cria ou atualiza uma mensalidade.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 * @param string $funcao 'criar' ou 'atualizar'.
 */
function criarOuAtualizarMensalidade($conn, $usuario_id, $funcao) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $inicio = filter_input(INPUT_POST, 'inicio', FILTER_SANITIZE_STRING);
        $duracao = filter_input(INPUT_POST, 'duracao', FILTER_VALIDATE_INT);

        if (empty($descricao) || $valor === false || empty($inicio) || $duracao === false || $duracao < 0 || !validarData($inicio)) {
            enviarErroJson('Dados inválidos ou incompletos. Verifique descrição, valor, data de início e duração.');
        }

        if ($funcao === 'criar') {
            $sql = "INSERT INTO mensalidades (descricao, valor, inicio, duracao, usuario_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query 'criarMensalidade': " . $conn->error);
            }
            $stmt->bind_param("sdsii", $descricao, $valor, $inicio, $duracao, $usuario_id);

            if ($stmt->execute()) {
                limparResumoCache($conn, $usuario_id, $inicio);
                enviarSucessoJson('Mensalidade adicionada com sucesso!');
            } else {
                enviarErroJson('Erro ao adicionar mensalidade: ' . $stmt->error);
            }
        } elseif ($funcao === 'atualizar') {
            if (!$id) {
                enviarErroJson('ID inválido para atualização.');
            }
            // Primeiro, pegamos a data original para limpar o cache do mês anterior também
            $sqlBuscaData = "SELECT inicio FROM mensalidades WHERE id = ? AND usuario_id = ?";
            $stmtBuscaData = $conn->prepare($sqlBuscaData);
            $stmtBuscaData->bind_param('ii', $id, $usuario_id);
            $stmtBuscaData->execute();
            $resBuscaData = $stmtBuscaData->get_result();
            if ($resBuscaData->num_rows > 0) {
                limparResumoCache($conn, $usuario_id, $resBuscaData->fetch_assoc()['inicio']);
            }
            $stmtBuscaData->close();
            
            $sql = "UPDATE mensalidades SET descricao=?, valor=?, inicio=?, duracao=? WHERE id=? AND usuario_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query 'atualizarMensalidade': " . $conn->error);
            }
            $stmt->bind_param("sdsiii", $descricao, $valor, $inicio, $duracao, $id, $usuario_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    limparResumoCache($conn, $usuario_id, $inicio);
                    enviarSucessoJson('Mensalidade atualizada com sucesso!');
                } else {
                    enviarSucessoJson('Nenhuma alteração feita ou mensalidade não encontrada.');
                }
            } else {
                enviarErroJson('Erro ao atualizar mensalidade: ' . $stmt->error);
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao processar mensalidade: ' . $e->getMessage());
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
            enviarErroJson('ID inválido para exclusão.');
        }

        // Primeiro, buscamos a data de início da mensalidade para limpar o cache
        $sqlBuscaData = "SELECT inicio FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmtBuscaData = $conn->prepare($sqlBuscaData);
        $stmtBuscaData->bind_param('ii', $id, $usuario_id);
        $stmtBuscaData->execute();
        $resBuscaData = $stmtBuscaData->get_result();
        if ($resBuscaData->num_rows > 0) {
            $inicioMensalidade = $resBuscaData->fetch_assoc()['inicio'];
            limparResumoCache($conn, $usuario_id, $inicioMensalidade);
        } else {
            enviarErroJson('Mensalidade não encontrada ou não pertence ao usuário logado.');
        }
        $stmtBuscaData->close();

        // Agora, podemos deletar
        $sql = "DELETE FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'deletarMensalidade': " . $conn->error);
        }

        $stmt->bind_param("ii", $id, $usuario_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                enviarSucessoJson('Mensalidade excluída com sucesso!');
            } else {
                enviarErroJson('Mensalidade não encontrada ou não pertence ao usuário logado.');
            }
        } else {
            enviarErroJson('Erro ao excluir mensalidade: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao processar exclusão de mensalidade: ' . $e->getMessage());
    }
}

/**
 * Lista todas as mensalidades do usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function listarMensalidades($conn, $usuario_id) {
    try {
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
            while ($linha = $resultado->fetch_assoc()) {
                $retorno[] = utf8ize($linha);
            }
        }
        $stmt->close();
        enviarSucessoJson('Mensalidades carregadas com sucesso.', $retorno);
    } catch (Exception $e) {
        enviarErroJson('Erro ao listar mensalidades: ' . $e->getMessage());
    }
}

/**
 * Busca uma mensalidade específica por ID para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function buscarMensalidade($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            enviarErroJson('ID da mensalidade inválido.');
        }
        $sql = "SELECT id, descricao, valor, inicio, duracao FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'buscarMensalidade': " . $conn->error);
        }
        $stmt->bind_param('ii', $id, $usuario_id);
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução da query 'buscarMensalidade': " . $stmt->error);
        }
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $mensalidade = $res->fetch_assoc();
            enviarSucessoJson('Mensalidade encontrada com sucesso.', utf8ize($mensalidade));
        } else {
            enviarErroJson('Mensalidade não encontrada ou não pertence ao usuário logado.');
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao buscar mensalidade: ' . $e->getMessage());
    }
}


// --- Roteador Principal ---
if ($funcao === 'listar') {
    listarMensalidades($conn, $usuario_id);
} elseif ($funcao === 'criar' || $funcao === 'atualizar') {
    criarOuAtualizarMensalidade($conn, $usuario_id, $funcao);
} elseif ($funcao === 'deletar') {
    deletarMensalidade($conn, $usuario_id);
} elseif ($funcao === 'buscar') {
    buscarMensalidade($conn, $usuario_id);
} else {
    enviarErroJson('Função inválida ou não implementada: ' . htmlspecialchars($funcao));
}
exit();
?>
