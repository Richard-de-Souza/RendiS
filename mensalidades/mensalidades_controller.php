<?php
// mensalidades_controller.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../banco.php');

$usuario_id = $_SESSION['user_id'] ?? null;

if (!$usuario_id) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar as mensalidades.']);
    exit();
}

if (!isset($conn) || $conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.']);
    exit();
}

// Garantir que as tabelas existam
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

$funcao = $_REQUEST['funcao'] ?? '';
if (empty($funcao)) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma função especificada para executar.']);
    exit();
}

// Funções Auxiliares
function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

function enviarSucessoJson($mensagem, $dados = []) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem, 'dados' => $dados], JSON_UNESCAPED_UNICODE);
    exit();
}

function enviarErroJson($mensagem) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Obtém o saldo disponível do mês anterior.
 */
function _obter_saldo_mes_anterior($conn, $usuario_id, $ano, $mes) {
    $mes_anterior = $mes - 1;
    $ano_anterior = $ano;

    if ($mes_anterior < 1) {
        $mes_anterior = 12;
        $ano_anterior--;
    }

    $sql = "SELECT dinheiro_disponivel FROM mes_resumo_financeiro WHERE usuario_id = ? AND ano = ? AND mes = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) return 0.00;
    
    $stmt->bind_param('iii', $usuario_id, $ano_anterior, $mes_anterior);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $stmt->close();

    return (float) ($row['dinheiro_disponivel'] ?? 0.00);
}

/**
 * Calcula o TOTAL de mensalidades ATIVAS para um dado mês/ano.
 */
function _calcular_total_mensalidades($conn, $usuario_id, $ano, $mes) {
    
    // Calcula o primeiro dia do mês atual
    $data_mes = sprintf('%04d-%02d-01', $ano, $mes);
    
    // SQL: Soma o valor de mensalidades que:
    // 1. Pertencem ao usuário
    // 2. Têm o início ANTES ou IGUAL ao mês/ano atual
    // 3. Têm a duração ATIVA no mês/ano atual (inicio + duracao meses)
    $sql = "
        SELECT SUM(valor) AS total_mensalidades
        FROM mensalidades
        WHERE usuario_id = ? 
        AND inicio <= ?
        AND DATE_ADD(inicio, INTERVAL duracao MONTH) > ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0.00;
    
    $stmt->bind_param('iss', $usuario_id, $data_mes, $data_mes);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (float) ($row['total_mensalidades'] ?? 0.00);
}


/**
 * Recalcula e atualiza o resumo mensal, propagando a alteração para os meses seguintes (cascata).
 * Esta versão é otimizada para Mensalidades.
 */
function atualizarResumoMensal($conn, $usuario_id, $ano_inicial, $mes_inicial) {
    try {
        $mes = $mes_inicial;
        $ano = $ano_inicial;
        $mes_atual = (int) date('m');
        $ano_atual = (int) date('Y');

        // Loop de recálculo em cascata: avança mês a mês
        while (true) {
            
            // 1. Obter o saldo do mês anterior
            $saldo_anterior = _obter_saldo_mes_anterior($conn, $usuario_id, $ano, $mes);

            // 2. Calcular o NOVO Total de Mensalidades para o mês do loop
            $novo_total_mensalidades = _calcular_total_mensalidades($conn, $usuario_id, $ano, $mes);

            // 3. Buscar Outros Dados Atuais do Resumo (salário, gastos, ganhos, descontos)
            $sql_resumo_atual = "
                SELECT salario, descontos_clt, total_gastos, total_ganhos 
                FROM mes_resumo_financeiro
                WHERE usuario_id = ? AND ano = ? AND mes = ?";
            
            $stmt_resumo = $conn->prepare($sql_resumo_atual);
            $stmt_resumo->bind_param('iii', $usuario_id, $ano, $mes);
            $stmt_resumo->execute();
            $row_resumo = $stmt_resumo->get_result()->fetch_assoc();
            $stmt_resumo->close();

            // Inicializa valores
            $salario = (float) ($row_resumo['salario'] ?? 0.00);
            $descontos_clt = (float) ($row_resumo['descontos_clt'] ?? 0.00);
            $total_gastos = (float) ($row_resumo['total_gastos'] ?? 0.00);
            $total_ganhos = (float) ($row_resumo['total_ganhos'] ?? 0.00);
            
            
            // 4. Calcular o Novo Saldo Disponível
            $total_entradas = $salario + $total_ganhos + $saldo_anterior;
            $total_saidas = $descontos_clt + $novo_total_mensalidades + $total_gastos;
            
            $novo_dinheiro_disponivel = $total_entradas - $total_saidas;

            // Formata para string e armazena em variáveis (Necessário para bind_param)
            $salario_str = number_format($salario, 2, '.', '');
            $descontos_clt_str = number_format($descontos_clt, 2, '.', '');
            $novo_total_mensalidades_str = number_format($novo_total_mensalidades, 2, '.', '');
            $total_gastos_str = number_format($total_gastos, 2, '.', ''); 
            $total_ganhos_str = number_format($total_ganhos, 2, '.', ''); 
            $novo_dinheiro_disponivel_str = number_format($novo_dinheiro_disponivel, 2, '.', ''); 
            $saldo_anterior_str = number_format($saldo_anterior, 2, '.', ''); 

            // 5. UPSERT
            $sql_upsert = "
                INSERT INTO mes_resumo_financeiro 
                (usuario_id, ano, mes, salario, descontos_clt, total_mensalidades, total_gastos, total_ganhos, dinheiro_disponivel, saldo_anterior)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                total_mensalidades = VALUES(total_mensalidades),
                saldo_anterior = VALUES(saldo_anterior),
                dinheiro_disponivel = VALUES(dinheiro_disponivel)";

            $stmt_upsert = $conn->prepare($sql_upsert);
            if (!$stmt_upsert) { throw new Exception("Erro (UPSERT): " . $conn->error); }

            $stmt_upsert->bind_param(
                'iiisssssss', 
                $usuario_id, $ano, $mes, 
                $salario_str, $descontos_clt_str, $novo_total_mensalidades_str, // MENSALIDADES É O VALOR NOVO
                $total_gastos_str, $total_ganhos_str, 
                $novo_dinheiro_disponivel_str, $saldo_anterior_str 
            );

            $stmt_upsert->execute();
            $linhas_afetadas = $stmt_upsert->affected_rows;
            $stmt_upsert->close();

            // 6. Condição de Parada do Loop e Avanço
            
            // Para se for o mês atual E a alteração não se propagou mais.
            if (($ano == $ano_atual && $mes == $mes_atual) || ($linhas_afetadas == 0 && ($ano != $ano_inicial || $mes != $mes_inicial))) {
                break;
            }

            // Avança para o próximo mês
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $ano++;
            }
            
            if ($ano > $ano_atual || ($ano == $ano_atual && $mes > $mes_atual)) {
                break;
            }

        } // Fim do Loop While

    } catch (Exception $e) {
        error_log("Erro ao atualizar resumo financeiro: " . $e->getMessage());
    }
}


// Funções CRUD

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

        $dataObj = new DateTime($inicio);
        $ano = (int)$dataObj->format('Y');
        $mes = (int)$dataObj->format('n');

        if ($funcao === 'criar') {
            $sql = "INSERT INTO mensalidades (descricao, valor, inicio, duracao, usuario_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception("Erro na preparação da query 'criarMensalidade': " . $conn->error); }
            $stmt->bind_param("sdsii", $descricao, $valor, $inicio, $duracao, $usuario_id);

            if ($stmt->execute()) {
                atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Recálculo em cascata
                enviarSucessoJson('Mensalidade adicionada com sucesso!');
            } else {
                enviarErroJson('Erro ao adicionar mensalidade: ' . $stmt->error);
            }
        } elseif ($funcao === 'atualizar') {
            if (!$id) { enviarErroJson('ID inválido para atualização.'); }
            
            // 1. Busca data original para disparar recálculo do mês antigo (se mudou)
            $sqlBuscaData = "SELECT inicio FROM mensalidades WHERE id = ? AND usuario_id = ?";
            $stmtBuscaData = $conn->prepare($sqlBuscaData);
            $stmtBuscaData->bind_param('ii', $id, $usuario_id);
            $stmtBuscaData->execute();
            $resBuscaData = $stmtBuscaData->get_result();
            $dataOriginal = $resBuscaData->num_rows > 0 ? new DateTime($resBuscaData->fetch_assoc()['inicio']) : null;
            $stmtBuscaData->close();
            
            $sql = "UPDATE mensalidades SET descricao=?, valor=?, inicio=?, duracao=? WHERE id=? AND usuario_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception("Erro na preparação da query 'atualizarMensalidade': " . $conn->error); }
            $stmt->bind_param("sdsiii", $descricao, $valor, $inicio, $duracao, $id, $usuario_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    if ($dataOriginal && $dataOriginal->format('Y-m') !== $dataObj->format('Y-m')) {
                        atualizarResumoMensal($conn, $usuario_id, (int)$dataOriginal->format('Y'), (int)$dataOriginal->format('n')); // Mês de início original
                    }
                    atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Novo mês de início
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

function deletarMensalidade($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) { enviarErroJson('ID inválido para exclusão.'); }

        // Busca a data de início para recálculo em cascata
        $sqlBuscaData = "SELECT inicio FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmtBuscaData = $conn->prepare($sqlBuscaData);
        $stmtBuscaData->bind_param('ii', $id, $usuario_id);
        $stmtBuscaData->execute();
        $resBuscaData = $stmtBuscaData->get_result();
        
        if ($resBuscaData->num_rows > 0) {
            $inicioMensalidade = $resBuscaData->fetch_assoc()['inicio'];
            $data = new DateTime($inicioMensalidade);
            $ano = (int)$data->format('Y');
            $mes = (int)$data->format('n');
        } else {
            enviarErroJson('Mensalidade não encontrada ou não pertence ao usuário logado.');
        }
        $stmtBuscaData->close();

        // Deleta
        $sql = "DELETE FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'deletarMensalidade': " . $conn->error); }

        $stmt->bind_param("ii", $id, $usuario_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Recálculo em cascata
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

function listarMensalidades($conn, $usuario_id) {
    try {
        $sql = "SELECT id, descricao, valor, inicio, duracao FROM mensalidades WHERE usuario_id = ? ORDER BY inicio DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'listarMensalidades': " . $conn->error); }
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $retorno = [];

        if ($resultado->num_rows > 0) {
            while ($linha = $resultado->fetch_assoc()) {
                $retorno[] = $linha;
            }
        }
        $stmt->close();
        enviarSucessoJson('Mensalidades carregadas com sucesso.', $retorno);
    } catch (Exception $e) {
        enviarErroJson('Erro ao listar mensalidades: ' . $e->getMessage());
    }
}

function buscarMensalidade($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) { enviarErroJson('ID da mensalidade inválido.'); }
        $sql = "SELECT id, descricao, valor, inicio, duracao FROM mensalidades WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'buscarMensalidade': " . $conn->error); }
        $stmt->bind_param('ii', $id, $usuario_id);
        if (!$stmt->execute()) { throw new Exception("Erro na execução da query 'buscarMensalidade': " . $stmt->error); }
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $mensalidade = $res->fetch_assoc();
            enviarSucessoJson('Mensalidade encontrada com sucesso.', $mensalidade);
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