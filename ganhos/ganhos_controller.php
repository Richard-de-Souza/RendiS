<?php
// ganhos_controller.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../banco.php');

// O header JSON será enviado no final ou em caso de erro
// para evitar output corrompido em funções internas.

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar os ganhos.']);
    exit();
}

$usuario_id = $_SESSION['user_id'];

if (!isset($conn) || $conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.']);
    exit();
}

// Garantir que as tabelas existam
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

/**
 * Funções Auxiliares
 */
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
 * Obtém o saldo disponível do mês anterior para usar como saldo_anterior no mês atual.
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
 * Recalcula e atualiza o resumo mensal, propagando a alteração para os meses seguintes (cascata).
 */
function atualizarResumoMensal($conn, $usuario_id, $ano_inicial, $mes_inicial) {
    try {
        $mes = $mes_inicial;
        $ano = $ano_inicial;
        $mes_atual = (int) date('m');
        $ano_atual = (int) date('Y');

        // Loop de recálculo em cascata
        while (true) {
            
            // 1. Obter o saldo do mês anterior para usar como saldo_anterior
            $saldo_anterior = _obter_saldo_mes_anterior($conn, $usuario_id, $ano, $mes);

            // 2. Calcular o Total de Ganhos do Mês
            $sql_ganhos = "
                SELECT SUM(valor) AS total_ganhos
                FROM ganhos
                WHERE usuario_id = ? 
                AND YEAR(data) = ? 
                AND MONTH(data) = ?";
            
            $stmt_ganhos = $conn->prepare($sql_ganhos);
            if (!$stmt_ganhos) { throw new Exception("Erro (Ganhos SELECT): " . $conn->error); }
            $stmt_ganhos->bind_param('iii', $usuario_id, $ano, $mes);
            $stmt_ganhos->execute();
            $row_ganhos = $stmt_ganhos->get_result()->fetch_assoc();
            $stmt_ganhos->close();

            $novo_total_ganhos = (float) ($row_ganhos['total_ganhos'] ?? 0.00);


            // 3. Buscar Outros Dados Atuais do Resumo (salário, gastos, descontos, etc.)
            // A busca é feita para preservar os dados que não estão sendo recalculados (salário, gastos, etc.)
            $sql_resumo_atual = "
                SELECT salario, descontos_clt, total_mensalidades, total_gastos 
                FROM mes_resumo_financeiro
                WHERE usuario_id = ? AND ano = ? AND mes = ?";
            
            $stmt_resumo = $conn->prepare($sql_resumo_atual);
            $stmt_resumo->bind_param('iii', $usuario_id, $ano, $mes);
            $stmt_resumo->execute();
            $row_resumo = $stmt_resumo->get_result()->fetch_assoc();
            $stmt_resumo->close();

            $salario = (float) ($row_resumo['salario'] ?? 0.00);
            $descontos_clt = (float) ($row_resumo['descontos_clt'] ?? 0.00);
            $total_mensalidades = (float) ($row_resumo['total_mensalidades'] ?? 0.00);
            $total_gastos = (float) ($row_resumo['total_gastos'] ?? 0.00);
            
            
            // 4. Calcular o Novo Saldo Disponível do MÊS ATUAL DO LOOP
            $total_entradas = $salario + $novo_total_ganhos + $saldo_anterior;
            $total_saidas = $descontos_clt + $total_mensalidades + $total_gastos;
            
            $novo_dinheiro_disponivel = $total_entradas - $total_saidas;

            // Formata para string e armazena em variáveis (Necessário para bind_param)
            $salario_str = number_format($salario, 2, '.', '');
            $descontos_clt_str = number_format($descontos_clt, 2, '.', '');
            $total_mensalidades_str = number_format($total_mensalidades, 2, '.', '');
            $total_gastos_str = number_format($total_gastos, 2, '.', ''); 
            $novo_total_ganhos_str = number_format($novo_total_ganhos, 2, '.', ''); 
            $novo_dinheiro_disponivel_str = number_format($novo_dinheiro_disponivel, 2, '.', ''); 
            $saldo_anterior_str = number_format($saldo_anterior, 2, '.', ''); 

            // 5. UPSERT com Atualização Completa do Mês Atual do Loop
            $sql_upsert = "
                INSERT INTO mes_resumo_financeiro 
                (usuario_id, ano, mes, salario, descontos_clt, total_mensalidades, total_gastos, total_ganhos, dinheiro_disponivel, saldo_anterior)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                total_ganhos = VALUES(total_ganhos),
                saldo_anterior = VALUES(saldo_anterior),
                dinheiro_disponivel = VALUES(dinheiro_disponivel)";

            $stmt_upsert = $conn->prepare($sql_upsert);
            if (!$stmt_upsert) { throw new Exception("Erro (UPSERT): " . $conn->error); }

            // Os valores passados no VALUES devem incluir todos os campos (mesmo que não alterados)
            $stmt_upsert->bind_param(
                'iiisssssss', 
                $usuario_id, $ano, $mes, 
                $salario_str, $descontos_clt_str, $total_mensalidades_str, 
                $total_gastos_str, $novo_total_ganhos_str, 
                $novo_dinheiro_disponivel_str, $saldo_anterior_str 
            );

            $stmt_upsert->execute();
            $linhas_afetadas = $stmt_upsert->affected_rows;
            $stmt_upsert->close();

            // 6. Condição de Parada do Loop e Avanço
            
            // Para se for o mês atual E o saldo não mudou (linhas_afetadas == 0), 
            // ou se tentou ir além do mês atual.
            if (($ano == $ano_atual && $mes == $mes_atual) || ($linhas_afetadas == 0 && ($ano != $ano_inicial || $mes != $mes_inicial))) {
                break;
            }

            // Avança para o próximo mês
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $ano++;
            }
            
            // Segurança: Para de ir para o futuro
            if ($ano > $ano_atual || ($ano == $ano_atual && $mes > $mes_atual)) {
                break;
            }

        } // Fim do Loop While

    } catch (Exception $e) {
        error_log("Erro ao atualizar resumo financeiro: " . $e->getMessage());
    }
}


// --- Funções do Controlador de Ganhos ---

function listarGanhos($conn, $usuario_id) {
    try {
        $sql = "SELECT id, descricao, valor, data FROM ganhos WHERE usuario_id = ? ORDER BY data DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'listarGanhos': " . $conn->error); }
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $retorno = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) { $retorno[] = $row; }
        }
        $stmt->close();
        enviarSucessoJson('Ganhos carregados com sucesso!', $retorno);
    } catch (Exception $e) {
        enviarErroJson('Erro ao listar ganhos: ' . $e->getMessage());
    }
}

function criarOuAtualizarGanho($conn, $funcao, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $data = filter_input(INPUT_POST, 'data', FILTER_DEFAULT);

        if (empty($descricao) || $valor === false || $valor < 0 || empty($data) || !validarData($data)) {
            enviarErroJson('Dados inválidos ou incompletos para a operação. Verifique descrição, valor e data (YYYY-MM-DD).');
        }

        $dataObj = new DateTime($data);
        $ano = $dataObj->format('Y');
        $mes = $dataObj->format('n');

        if ($funcao === 'salvar' && empty($id)) {
            $sql = "INSERT INTO ganhos (descricao, valor, data, usuario_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception("Erro na preparação da query 'salvarGanho': " . $conn->error); }
            $stmt->bind_param('sdsi', $descricao, $valor, $data, $usuario_id);
            if ($stmt->execute()) {
                atualizarResumoMensal($conn, $usuario_id, (int)$ano, (int)$mes); // Recalcula a cadeia
                enviarSucessoJson('Ganho adicionado com sucesso!');
            } else {
                enviarErroJson('Erro ao adicionar ganho: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($funcao === 'atualizar' && $id) {
            // Busca data original para recalcular mês anterior se a data mudou
            $sqlBuscaData = "SELECT data FROM ganhos WHERE id = ? AND usuario_id = ?";
            $stmtBuscaData = $conn->prepare($sqlBuscaData);
            $stmtBuscaData->bind_param('ii', $id, $usuario_id);
            $stmtBuscaData->execute();
            $resBuscaData = $stmtBuscaData->get_result();
            $dataOriginal = $resBuscaData->num_rows > 0 ? new DateTime($resBuscaData->fetch_assoc()['data']) : null;
            $stmtBuscaData->close();
            
            $sql = "UPDATE ganhos SET descricao=?, valor=?, data=? WHERE id=? AND usuario_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception("Erro na preparação da query 'atualizarGanho': " . $conn->error); }
            $stmt->bind_param('sdsii', $descricao, $valor, $data, $id, $usuario_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    if ($dataOriginal && ($dataOriginal->format('Y') != $ano || $dataOriginal->format('n') != $mes)) {
                         // Se a data mudou de mês/ano, disparamos o recálculo do mês original TAMBÉM
                        atualizarResumoMensal($conn, $usuario_id, (int)$dataOriginal->format('Y'), (int)$dataOriginal->format('n'));
                    }
                    atualizarResumoMensal($conn, $usuario_id, (int)$ano, (int)$mes); // Recalcula o novo mês e a cadeia
                    enviarSucessoJson('Ganho atualizado com sucesso!');
                } else {
                    enviarSucessoJson('Nenhuma alteração feita no ganho.');
                }
            } else {
                enviarErroJson('Erro ao atualizar ganho: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            enviarErroJson('Parâmetros inválidos para salvar/atualizar.');
        }
    } catch (Exception $e) {
        enviarErroJson('Erro ao processar criação/atualização de ganho: ' . $e->getMessage());
    }
}

function buscarGanho($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) { enviarErroJson('ID do ganho inválido.'); }
        $sql = "SELECT id, descricao, valor, data FROM ganhos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'buscarGanho': " . $conn->error); }
        $stmt->bind_param('ii', $id, $usuario_id);
        if (!$stmt->execute()) { throw new Exception("Erro na execução da query 'buscarGanho': " . $stmt->error); }
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $ganho = $res->fetch_assoc();
            enviarSucessoJson('Ganho encontrado com sucesso.', $ganho);
        } else {
            enviarErroJson('Ganho não encontrado ou não pertence ao usuário logado.');
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao buscar ganho: ' . $e->getMessage());
    }
}

function deletarGanho($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) { enviarErroJson('ID inválido para exclusão.'); }

        // Busca a data do ganho para recálculo em cascata
        $sqlBuscaData = "SELECT data FROM ganhos WHERE id = ? AND usuario_id = ?";
        $stmtBuscaData = $conn->prepare($sqlBuscaData);
        $stmtBuscaData->bind_param('ii', $id, $usuario_id);
        $stmtBuscaData->execute();
        $resBuscaData = $stmtBuscaData->get_result();

        if ($resBuscaData->num_rows > 0) {
            $data = new DateTime($resBuscaData->fetch_assoc()['data']);
            $ano = (int)$data->format('Y');
            $mes = (int)$data->format('n');
        } else {
            enviarErroJson('Ganho não encontrado para exclusão.');
        }
        $stmtBuscaData->close();
        
        $sql = "DELETE FROM ganhos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'deletarGanho': " . $conn->error); }
        $stmt->bind_param('ii', $id, $usuario_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Recalcula a cadeia
                enviarSucessoJson('Ganho excluído com sucesso!');
            } else {
                enviarErroJson('Ganho não encontrado ou não pertence ao usuário logado para exclusão.');
            }
        } else {
            enviarErroJson('Erro ao excluir ganho: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        enviarErroJson('Erro ao processar exclusão de ganho: ' . $e->getMessage());
    }
}

function ganhosMes($conn, $usuario_id) {
    try {
        $ano = filter_input(INPUT_GET, 'ano');
        $mes = filter_input(INPUT_GET, 'mes');
        if (!$ano || !$mes || $mes < 1 || $mes > 12) { enviarErroJson('Parâmetros de ano ou mês inválidos.'); }
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        $sql = "SELECT id, descricao, valor, DATE_FORMAT(data, '%d/%m/%Y') as data FROM ganhos WHERE data BETWEEN ? AND ? AND usuario_id = ? ORDER BY data DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Erro na preparação da query 'ganhosMes': " . $conn->error); }
        $stmt->bind_param('ssi', $dataInicio, $dataFim, $usuario_id);
        if (!$stmt->execute()) { throw new Exception("Erro na execução da query 'ganhosMes': " . $stmt->error); }
        $res = $stmt->get_result();
        $retorno = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) { $retorno[] = $row; }
        }
        $stmt->close();
        enviarSucessoJson('Ganhos do mês carregados com sucesso.', $retorno);
    } catch (Exception $e) {
        enviarErroJson('Erro ao buscar ganhos do mês: ' . $e->getMessage());
    }
}


// --- Roteador Principal ---
if ($funcao === 'listar') {
    listarGanhos($conn, $usuario_id);
} elseif ($funcao === 'salvar' || $funcao === 'atualizar') {
    criarOuAtualizarGanho($conn, $funcao, $usuario_id);
} elseif ($funcao === 'buscar') {
    buscarGanho($conn, $usuario_id);
} elseif ($funcao === 'excluir') {
    deletarGanho($conn, $usuario_id);
} elseif ($funcao === 'ganhos_mes') {
    ganhosMes($conn, $usuario_id);
} else {
    enviarErroJson('Função inválida ou não especificada.');
}
exit();
?>