<?php
// home_controller.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('banco.php');

header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit();
}
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar o resumo financeiro.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$usuario_id = $_SESSION['user_id'];

// Garante que as tabelas essenciais existam
$conn->query("
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        nome VARCHAR(255) NULL,
        nascimento DATE NULL,
        salario DECIMAL(10, 2) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
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
    CREATE TABLE IF NOT EXISTS investimentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticker VARCHAR(10) NOT NULL,
        quantidade INT NOT NULL,
        preco_medio DECIMAL(10,2) NOT NULL,
        id_usuario INT NOT NULL,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
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

function calcularINSS($salario) {
    $tabela = [
        [1320.00, 0.075],
        [2571.29, 0.09],
        [3856.94, 0.12],
        [7507.49, 0.14]
    ];
    $desconto = 0;
    $limiteInferior = 0;
    foreach ($tabela as [$limiteSuperior, $aliquota]) {
        if ($salario > $limiteSuperior) {
            $desconto += ($limiteSuperior - $limiteInferior) * $aliquota;
            $limiteInferior = $limiteSuperior;
        } else {
            $desconto += ($salario - $limiteInferior) * $aliquota;
            break;
        }
    }
    return min($desconto, 876.97);
}

function calcularIRRF($salarioBase) {
    $tabelaIRRF = [
        [1903.98, 0, 0],
        [2826.65, 0.075, 142.80],
        [3751.05, 0.15, 354.80],
        [4664.68, 0.225, 636.13],
        [PHP_FLOAT_MAX, 0.275, 869.36]
    ];
    foreach ($tabelaIRRF as [$limite, $aliquota, $deducao]) {
        if ($salarioBase <= $limite) {
            $irrf = $salarioBase * $aliquota - $deducao;
            return max($irrf, 0);
        }
    }
    return 0;
}

function getTodosResumosMensais($conn, $usuario_id) {
    try {
        $sql = "SELECT * FROM mes_resumo_financeiro WHERE usuario_id = ? ORDER BY ano ASC, mes ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $resumos = [];
        while ($row = $res->fetch_assoc()) {
            $resumos[] = $row;
        }
        $stmt->close();

        $dataAtual = new DateTime();
        $anoAtual = $dataAtual->format('Y');
        $mesAtual = $dataAtual->format('n');

        $mesAtualExiste = false;
        foreach ($resumos as $resumo) {
            if ($resumo['ano'] == $anoAtual && $resumo['mes'] == $mesAtual) {
                $mesAtualExiste = true;
                break;
            }
        }

        if (!$mesAtualExiste) {
            $salario = 0;
            $stmtUsuario = $conn->prepare("SELECT salario FROM usuarios WHERE id = ? LIMIT 1");
            if ($stmtUsuario) {
                $stmtUsuario->bind_param('i', $usuario_id);
                $stmtUsuario->execute();
                $resUsuario = $stmtUsuario->get_result();
                if ($resUsuario && $resUsuario->num_rows > 0) {
                    $usuario = $resUsuario->fetch_assoc();
                    $salario = floatval($usuario['salario']);
                }
                $stmtUsuario->close();
            }

            $inss = calcularINSS($salario);
            $baseIRRF = $salario - $inss;
            $irrf = calcularIRRF($baseIRRF);
            $descontosCLT = $inss + $irrf;

            $saldoAnterior = 0;
            $ultimoResumo = end($resumos);
            if ($ultimoResumo) {
                $saldoAnterior = $ultimoResumo['dinheiro_disponivel'];
            }
            
            // Novos cálculos para ganhos, gastos e mensalidades do mês atual
            $totalMensalidades = 0;
            $stmtMensalidades = $conn->prepare("SELECT SUM(valor) AS total FROM mensalidades WHERE usuario_id = ? AND inicio <= CONCAT(?, '-', ?, '-31')");
            if ($stmtMensalidades) {
                $stmtMensalidades->bind_param('iss', $usuario_id, $anoAtual, $mesAtual);
                $stmtMensalidades->execute();
                $resMensalidades = $stmtMensalidades->get_result();
                $rowMensalidades = $resMensalidades->fetch_assoc();
                $totalMensalidades = floatval($rowMensalidades['total']);
                $stmtMensalidades->close();
            }

            $totalGastos = 0;
            $stmtGastos = $conn->prepare("SELECT SUM(valor) AS total FROM gastos WHERE usuario_id = ? AND YEAR(data) = ? AND MONTH(data) = ?");
            if ($stmtGastos) {
                $stmtGastos->bind_param('iii', $usuario_id, $anoAtual, $mesAtual);
                $stmtGastos->execute();
                $resGastos = $stmtGastos->get_result();
                $rowGastos = $resGastos->fetch_assoc();
                $totalGastos = floatval($rowGastos['total']);
                $stmtGastos->close();
            }

            $totalGanhos = 0;
            $stmtGanhos = $conn->prepare("SELECT SUM(valor) AS total FROM ganhos WHERE usuario_id = ? AND YEAR(data) = ? AND MONTH(data) = ?");
            if ($stmtGanhos) {
                $stmtGanhos->bind_param('iii', $usuario_id, $anoAtual, $mesAtual);
                $stmtGanhos->execute();
                $resGanhos = $stmtGanhos->get_result();
                $rowGanhos = $resGanhos->fetch_assoc();
                $totalGanhos = floatval($rowGanhos['total']);
                $stmtGanhos->close();
            }

            $dinheiroDisponivel = $salario + $saldoAnterior - $descontosCLT - $totalMensalidades - $totalGastos + $totalGanhos;

            $novoResumo = [
                'usuario_id' => $usuario_id,
                'ano' => $anoAtual,
                'mes' => $mesAtual,
                'salario' => round($salario, 2),
                'descontos_clt' => round($descontosCLT, 2),
                'total_mensalidades' => round($totalMensalidades, 2),
                'total_gastos' => round($totalGastos, 2),
                'total_ganhos' => round($totalGanhos, 2),
                'dinheiro_disponivel' => round($dinheiroDisponivel, 2),
                'saldo_anterior' => round($saldoAnterior, 2)
            ];

            $sqlInsert = "INSERT INTO mes_resumo_financeiro (usuario_id, ano, mes, salario, descontos_clt, total_mensalidades, total_gastos, total_ganhos, dinheiro_disponivel, saldo_anterior) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            if ($stmtInsert) {
                $stmtInsert->bind_param(
                    'iiiddddddd',
                    $novoResumo['usuario_id'],
                    $novoResumo['ano'],
                    $novoResumo['mes'],
                    $novoResumo['salario'],
                    $novoResumo['descontos_clt'],
                    $novoResumo['total_mensalidades'],
                    $novoResumo['total_gastos'],
                    $novoResumo['total_ganhos'],
                    $novoResumo['dinheiro_disponivel'],
                    $novoResumo['saldo_anterior']
                );
                $stmtInsert->execute();
                $stmtInsert->close();
                $resumos[] = $novoResumo;
            }
        }
        
        echo json_encode(['sucesso' => true, 'resumos' => utf8ize($resumos)], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar resumos financeiros: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}


/**
 * Obtém o resumo financeiro para o usuário logado no mês/ano especificado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 * @param int $ano O ano de referência.
 * @param int $mes O mês de referência.
 */
function getResumoFinanceiro($conn, $usuario_id, $ano, $mes) {
    try {
        $sql = "SELECT * FROM mes_resumo_financeiro WHERE usuario_id = ? AND ano = ? AND mes = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query 'getResumoFinanceiro': " . $conn->error);
        }
        $stmt->bind_param('iii', $usuario_id, $ano, $mes);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $resumo = $res->fetch_assoc();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Resumo financeiro carregado com sucesso.', 'dados' => $resumo], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Resumo financeiro não encontrado.'], JSON_UNESCAPED_UNICODE);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar resumo financeiro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}


/**
 * Obtém os maiores gastos do mês para o usuário logado (limitado a 5).
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function getMaioresGastosMes($conn, $usuario_id) {
    try {
        $ano = filter_input(INPUT_GET, 'ano');
        $mes = filter_input(INPUT_GET, 'mes');

        if (!$ano || !$mes || $mes < 1 || $mes > 12) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetros inválidos.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date("Y-m-t", strtotime($inicio));

        $sql = "SELECT id, descricao, valor, data FROM gastos WHERE data BETWEEN ? AND ? AND usuario_id = ? ORDER BY valor DESC LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $inicio, $fim, $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $gastos = [];

        while ($row = $res->fetch_assoc()) {
            $gastos[] = $row;
        }
        $stmt->close();

        echo json_encode(['sucesso' => true, 'gastos' => utf8ize($gastos)], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar maiores gastos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}


// Roteador principal de funções
$funcao = $_REQUEST['funcao'] ?? 'resumo';
$ano = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT);
$mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);

// Se o ano e o mês não foram fornecidos, usa a data atual
if (!$ano || !$mes) {
    $dataAtual = new DateTime();
    $ano = $dataAtual->format('Y');
    $mes = $dataAtual->format('n');
}

if ($funcao === 'todos_resumos') {
    getTodosResumosMensais($conn, $usuario_id);
} elseif ($funcao === 'resumo') {
    getResumoFinanceiro($conn, $usuario_id, $ano, $mes);
} elseif ($funcao === 'maiores_gastos_mes') {
    getMaioresGastosMes($conn, $usuario_id);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida: ' . htmlspecialchars($funcao)], JSON_UNESCAPED_UNICODE);
}
exit();
?>