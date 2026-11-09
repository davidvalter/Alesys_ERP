<?php
// public/api/get_registro_maquininhas_matriz.php

file_put_contents('/tmp/debug_api.log', print_r([
    'GET' => $_GET,
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'SCRIPT_FILENAME' => __FILE__,
    'TIME' => date('Y-m-d H:i:s')
], true) . PHP_EOL, FILE_APPEND);

require_once __DIR__ . '/../../configs/database.php';
header('Content-Type: application/json');

try {
    $db = getDB();

    $dataInicial = $_GET['dataInicial'] ?? null;
    $dataFinal   = $_GET['dataFinal']   ?? null;
    $operadora   = $_GET['operadora']   ?? '';
    $statusCaixa = $_GET['statusCaixa'] ?? 'fechado';
    $meio        = $_GET['meioPagamento'] ?? '';
    $responsavel = $_GET['responsavelId'] ?? '';

    if (!$dataInicial || !$dataFinal) {
        throw new Exception('Datas inválidas');
    }

    // Monta WHERE dinâmico com prepared statements
    $where = "f.Data BETWEEN ? AND ?";
    $params = [$dataInicial, $dataFinal];

    if ($operadora) {
        $where .= " AND i.Operadora = ?";
        $params[] = $operadora;
    }
    if ($statusCaixa) {
        $where .= " AND f.Status = ?";
        $params[] = $statusCaixa;
    }
    if ($meio) {
        $where .= " AND i.MeioPagamento = ?";
        $params[] = $meio;
    }
    if ($responsavel) {
        $where .= " AND f.ResponsavelId = ?";
        $params[] = $responsavel;
    }

    $stmt = $db->prepare("
        SELECT f.Data, i.Operadora, i.MeioPagamento, SUM(i.Valor) AS Total
        FROM FechamentosCaixaItens i
        JOIN FechamentosCaixa f ON f.Id = i.FechamentoId
        WHERE $where
        GROUP BY f.Data, i.Operadora, i.MeioPagamento
        ORDER BY f.Data ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode([
            'success' => true,
            'datas' => [],
            'linhas' => [],
            'totais_operadora' => [],
            'total_geral' => []
        ]);
        exit;
    }

    // Montagem das datas (ordenadas)
    $datas = array_values(array_unique(array_column($rows, 'Data')));
    sort($datas);

    // Monta matriz
    $estrutura = [];
    $totaisOperadora = [];
    $totalGeral = array_fill(0, count($datas), 0);

    foreach ($rows as $r) {
        $op    = $r['Operadora'];
        $meio  = ucfirst($r['MeioPagamento']);
        $data  = $r['Data'];
        $valor = floatval($r['Total']);
        $idx   = array_search($data, $datas);

        if (!isset($estrutura[$op][$meio])) {
            $estrutura[$op][$meio] = array_fill(0, count($datas), 0);
        }
        $estrutura[$op][$meio][$idx] += $valor;

        if (!isset($totaisOperadora[$op])) {
            $totaisOperadora[$op] = array_fill(0, count($datas), 0);
        }
        $totaisOperadora[$op][$idx] += $valor;
        $totalGeral[$idx] += $valor;
    }

    // Normaliza linhas
    $linhas = [];
    foreach ($estrutura as $op => $meios) {
        foreach ($meios as $meioNome => $valores) {
            $linhas[] = [
                'operadora' => $op,
                'meio' => $meioNome,
                'valores' => $valores
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'datas' => $datas,
        'linhas' => $linhas,
        'totais_operadora' => $totaisOperadora,
        'total_geral' => $totalGeral
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}