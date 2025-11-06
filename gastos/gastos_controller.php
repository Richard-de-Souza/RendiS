<?php
// gastos_controller.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../banco.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar os gastos.']);
    exit();
}

$usuario_id = $_SESSION['user_id'];

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.']);
    exit();
}

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

if (!isset($_REQUEST['funcao']) || empty($_REQUEST['funcao'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma função especificada para executar.']);
    exit();
}

$funcao = $_REQUEST['funcao'];

function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

function enviarSucessoJson($mensagem, $dados = []) {
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem, 'dados' => $dados], JSON_UNESCAPED_UNICODE);
    exit();
}

function enviarErroJson($mensagem) {
    echo json_encode(['sucesso' => false, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit();
}

// Função de suporte para buscar o saldo disponível do mês anterior
function _obter_saldo_mes_anterior($conn, $usuario_id, $ano, $mes) {
    $mes_anterior = $mes - 1;
    $ano_anterior = $ano;

    if ($mes_anterior < 1) {
        $mes_anterior = 12;
        $ano_anterior--;
    }

    $sql = "SELECT dinheiro_disponivel FROM mes_resumo_financeiro WHERE usuario_id = ? AND ano = ? AND mes = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Se a consulta falhar, retorna 0.00
        return 0.00;
    }
    
    $stmt->bind_param('iii', $usuario_id, $ano_anterior, $mes_anterior);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $stmt->close();

    // Retorna o saldo disponível do mês anterior, ou 0.00 se não existir
    return (float) ($row['dinheiro_disponivel'] ?? 0.00);
}


function atualizarResumoMensal($conn, $usuario_id, $ano_inicial, $mes_inicial) {
    try {
        // Inicializa as variáveis para o loop de iteração
        $mes = $mes_inicial;
        $ano = $ano_inicial;
        $mes_atual = (int) date('m');
        $ano_atual = (int) date('Y');

        // Loop de recálculo em cascata: avança mês a mês
        // O loop para quando o mês/ano atual for atingido e processado.
        while (true) {
            
            // 1. Obter o saldo do mês anterior para usar como saldo_anterior
            $saldo_anterior = _obter_saldo_mes_anterior($conn, $usuario_id, $ano, $mes);

            // 2. Calcular o Total de Gastos do Mês (do banco 'gastos')
            $sql_gastos = "
                SELECT SUM(valor) AS total_gastos
                FROM gastos
                WHERE usuario_id = ? 
                AND YEAR(data) = ? 
                AND MONTH(data) = ?";
            
            $stmt_gastos = $conn->prepare($sql_gastos);
            if (!$stmt_gastos) { throw new Exception("Erro (Gasto SELECT): " . $conn->error); }
            $stmt_gastos->bind_param('iii', $usuario_id, $ano, $mes);
            $stmt_gastos->execute();
            $row_gastos = $stmt_gastos->get_result()->fetch_assoc();
            $stmt_gastos->close();

            $novo_total_gastos = (float) ($row_gastos['total_gastos'] ?? 0.00);


            // 3. Buscar Outros Dados Atuais do Resumo (salário, ganhos, etc.)
            $sql_resumo_atual = "
                SELECT salario, descontos_clt, total_mensalidades, total_ganhos 
                FROM mes_resumo_financeiro
                WHERE usuario_id = ? AND ano = ? AND mes = ?";
            
            $stmt_resumo = $conn->prepare($sql_resumo_atual);
            $stmt_resumo->bind_param('iii', $usuario_id, $ano, $mes);
            $stmt_resumo->execute();
            $row_resumo = $stmt_resumo->get_result()->fetch_assoc();
            $stmt_resumo->close();

            // Inicializa valores com 0.00 se o resumo ainda não existir
            $salario = (float) ($row_resumo['salario'] ?? 0.00);
            $descontos_clt = (float) ($row_resumo['descontos_clt'] ?? 0.00);
            $total_mensalidades = (float) ($row_resumo['total_mensalidades'] ?? 0.00);
            $total_ganhos = (float) ($row_resumo['total_ganhos'] ?? 0.00);
            
            
            // 4. Calcular o Novo Saldo Disponível do MÊS ATUAL DO LOOP
            $total_entradas = $salario + $total_ganhos + $saldo_anterior;
            $total_saidas = $descontos_clt + $total_mensalidades + $novo_total_gastos;
            
            $novo_dinheiro_disponivel = $total_entradas - $total_saidas;

            // Formata para string e armazena em variáveis (EVITA O NOTICE)
            $salario_str = number_format($salario, 2, '.', '');
            $descontos_clt_str = number_format($descontos_clt, 2, '.', '');
            $total_mensalidades_str = number_format($total_mensalidades, 2, '.', '');
            $novo_total_gastos_str = number_format($novo_total_gastos, 2, '.', ''); 
            $total_ganhos_str = number_format($total_ganhos, 2, '.', '');
            $novo_dinheiro_disponivel_str = number_format($novo_dinheiro_disponivel, 2, '.', ''); 
            $saldo_anterior_str = number_format($saldo_anterior, 2, '.', ''); 

            // 5. UPSERT com Atualização Completa do Mês Atual do Loop
            $sql_upsert = "
                INSERT INTO mes_resumo_financeiro 
                (usuario_id, ano, mes, salario, descontos_clt, total_mensalidades, total_gastos, total_ganhos, dinheiro_disponivel, saldo_anterior)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                total_gastos = VALUES(total_gastos),
                saldo_anterior = VALUES(saldo_anterior),
                dinheiro_disponivel = VALUES(dinheiro_disponivel)";

            $stmt_upsert = $conn->prepare($sql_upsert);
            if (!$stmt_upsert) { throw new Exception("Erro (UPSERT): " . $conn->error); }

            $stmt_upsert->bind_param(
                'iiisssssss', 
                $usuario_id, $ano, $mes, 
                $salario_str, $descontos_clt_str, $total_mensalidades_str, 
                $novo_total_gastos_str, $total_ganhos_str, 
                $novo_dinheiro_disponivel_str, $saldo_anterior_str 
            );

            $stmt_upsert->execute();
            $linhas_afetadas = $stmt_upsert->affected_rows;
            $stmt_upsert->close();

            // 6. Condição de Parada do Loop e Avanço
            
            // Se o mês atual do loop for o último mês do sistema (o mês que estamos agora)
            // OU se o mês já estava atualizado (linhas afetadas 0) e não é o mês inicial,
            // podemos parar.
            if (($ano == $ano_atual && $mes == $mes_atual) || ($linhas_afetadas == 0 && ($ano != $ano_inicial || $mes != $mes_inicial))) {
                break;
            }

            // Avança para o próximo mês
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $ano++;
            }
            
            // Se o loop tentar avançar para o futuro além do mês atual, para.
            if ($ano > $ano_atual || ($ano == $ano_atual && $mes > $mes_atual)) {
                break;
            }

        } // Fim do Loop While

    } catch (Exception $e) {
        error_log("Erro ao atualizar resumo financeiro: " . $e->getMessage());
    }
}


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
        enviarSucessoJson('Gastos carregados com sucesso!', $retorno);
    } catch (Exception $e) {
        enviarErroJson('Erro ao listar gastos: ' . $e->getMessage());
    }
}

function gastosMes($conn, $usuario_id) {
    try {
        $ano = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT);
        $mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
        if (!$ano || !$mes || $mes < 1 || $mes > 12) {
            enviarErroJson('Parâmetros de ano ou mês inválidos.');
        }
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio));
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
        // CORRIGIDO: Agora retorna o JSON padrão de sucesso
        enviarSucessoJson('Gastos do mês carregados com sucesso!', $retorno); 
    } catch (Exception $e) {
        enviarErroJson('Erro ao buscar gastos do mês: ' . $e->getMessage());
    }
}

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
 * Cria ou atualiza um gasto e dispara a atualização do resumo mensal (cache).
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
                atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Substituindo limparResumoCache
                enviarSucessoJson('Gasto adicionado com sucesso!');
            } else {
                enviarErroJson('Erro ao adicionar gasto: ' . $stmt->error);
            }
            $stmt->close();
        } else { // $funcao == 'atualizar'
            if (!$id) {
                enviarErroJson('ID inválido para atualização.');
            }
            
            // Busca data original para atualizar o resumo mensal antigo, se o mês/ano mudou
            $sqlBuscaData = "SELECT data FROM gastos WHERE id = ? AND usuario_id = ?";
            $stmtBuscaData = $conn->prepare($sqlBuscaData);
            $stmtBuscaData->bind_param('ii', $id, $usuario_id);
            $stmtBuscaData->execute();
            $resBuscaData = $stmtBuscaData->get_result();
            
            if ($resBuscaData->num_rows > 0) {
                $dataOriginal = new DateTime($resBuscaData->fetch_assoc()['data']);
                // Atualiza o resumo do mês antigo antes de atualizar o gasto
                if ($dataOriginal->format('Y') != $ano || $dataOriginal->format('n') != $mes) {
                    atualizarResumoMensal($conn, $usuario_id, $dataOriginal->format('Y'), $dataOriginal->format('n')); // Substituindo limparResumoCache
                }
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
                    atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Substituindo limparResumoCache
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
 * Deleta um gasto e dispara a atualização do resumo mensal (cache).
 */
function deletarGasto($conn, $usuario_id) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            enviarErroJson('ID inválido para exclusão.');
        }

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
                atualizarResumoMensal($conn, $usuario_id, $ano, $mes); // Substituindo limparResumoCache
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