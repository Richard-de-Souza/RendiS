<?php
// resumo_financeiro_controller.php

// Inicia a sessão PHP para acessar o ID do usuário logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
include('banco.php'); // Assumindo que 'banco.php' está no mesmo nível ou o caminho é relativo

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Verifica se a conexão com o banco de dados é válida
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na conexão com o banco: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit();
}
$conn->set_charset("utf8mb4");

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) { // Usando 'user_id' conforme definido nos outros controladores
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado. Faça login para acessar o resumo financeiro.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$usuario_id = $_SESSION['user_id']; // Obtém o ID do usuário logado

// --- INÍCIO: Adição das instruções CREATE TABLE IF NOT EXISTS (por garantia) ---
// Estas instruções garantem que as tabelas existam, mesmo se este controller for acessado diretamente.
// Elas são idempotentes, ou seja, não causam erro se a tabela já existe.

// Tabela 'usuarios' (fundamental para as FKs)
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

// Tabela 'ganhos'
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

// Tabela 'gastos'
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

// Tabela 'investimentos'
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

// Tabela 'mensalidades'
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
// --- FIM: Adição das instruções CREATE TABLE IF NOT EXISTS ---


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

/**
 * Obtém o resumo financeiro para o usuário logado.
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function getResumoFinanceiro($conn, $usuario_id) {
    try {
        $salario = 0;
        // Busca o salário do usuário logado
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

        $hoje = date('Y-m-d');

        $totalMensalidades = 0;
        // Soma mensalidades ativas para o usuário logado
        $sqlMensal = "SELECT SUM(valor) FROM mensalidades WHERE DATE_ADD(inicio, INTERVAL duracao MONTH) >= ? AND inicio <= ? AND usuario_id = ?";
        $stmtMensal = $conn->prepare($sqlMensal);
        if ($stmtMensal) {
            $stmtMensal->bind_param('ssi', $hoje, $hoje, $usuario_id);
            $stmtMensal->execute();
            $resMensal = $stmtMensal->get_result();
            $totalMensalidades = floatval($resMensal->fetch_assoc()['SUM(valor)'] ?? 0);
            $stmtMensal->close();
        }

        $mesInicio = date('Y-m-01');
        $mesFim = date('Y-m-t');

        $totalGastos = 0;
        // Soma gastos do mês para o usuário logado
        $stmtGastos = $conn->prepare("SELECT SUM(valor) FROM gastos WHERE data BETWEEN ? AND ? AND usuario_id = ?");
        if ($stmtGastos) {
            $stmtGastos->bind_param('ssi', $mesInicio, $mesFim, $usuario_id);
            $stmtGastos->execute();
            $resGastos = $stmtGastos->get_result();
            $totalGastos = floatval($resGastos->fetch_assoc()['SUM(valor)'] ?? 0);
            $stmtGastos->close();
        }

        // Soma ganhos do mês para o usuário logado
        $totalGanhos = 0;
        $stmtGanhos = $conn->prepare("SELECT SUM(valor) FROM ganhos WHERE data BETWEEN ? AND ? AND usuario_id = ?");
        if ($stmtGanhos) {
            $stmtGanhos->bind_param('ssi', $mesInicio, $mesFim, $usuario_id);
            $stmtGanhos->execute();
            $resGanhos = $stmtGanhos->get_result();
            $totalGanhos = floatval($resGanhos->fetch_assoc()['SUM(valor)'] ?? 0);
            $stmtGanhos->close();
        }

        $dinheiroDisponivel = $salario - $descontosCLT - $totalMensalidades - $totalGastos + $totalGanhos;

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Resumo financeiro carregado com sucesso.',
            'dados' => [
                'salario' => round($salario, 2),
                'descontosCLT' => round($descontosCLT, 2),
                'totalMensalidades' => round($totalMensalidades, 2),
                'totalGastos' => round($totalGastos, 2),
                'totalGanhos' => round($totalGanhos, 2),
                'dinheiroDisponivel' => round($dinheiroDisponivel, 2)
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar resumo financeiro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Obtém os gastos do mês para o usuário logado (limitado a 5).
 * @param mysqli $conn Objeto de conexão com o banco de dados.
 * @param int $usuario_id ID do usuário logado.
 */
function getGastosMes($conn, $usuario_id) {
    try {
        $ano = filter_input(INPUT_GET, 'ano');
        $mes = filter_input(INPUT_GET, 'mes');

        if (!$ano || !$mes || $mes < 1 || $mes > 12) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetros inválidos.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date("Y-m-t", strtotime($inicio));

        // Filtra gastos por usuário logado
        $sql = "SELECT id, descricao, valor, data FROM gastos WHERE data BETWEEN ? AND ? AND usuario_id = ? ORDER BY valor DESC LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $inicio, $fim, $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $gastos = [];

        while ($row = $res->fetch_assoc()) {
            $gastos[] = utf8ize([
                'id' => $row['id'],
                'descricao' => $row['descricao'],
                'valor' => round(floatval($row['valor']), 2),
                'data' => $row['data']
            ]);
        }
        $stmt->close();

        echo json_encode($gastos, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar gastos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Roteador principal de funções
if (!isset($_REQUEST['funcao']) || empty($_REQUEST['funcao'])) {
    getResumoFinanceiro($conn, $usuario_id); // Chama com o ID do usuário
} else {
    $funcao = $_REQUEST['funcao'];
    if ($funcao === 'gastos_mes') {
        getGastosMes($conn, $usuario_id); // Chama com o ID do usuário
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Função inválida: ' . htmlspecialchars($funcao)], JSON_UNESCAPED_UNICODE);
    }
}
exit();
?>
