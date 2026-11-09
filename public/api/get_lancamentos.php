<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

$logFile = __DIR__ . '/../logs/get_lancamentos_debug.log';

try {
    $db = getDB();

    $conta = isset($_GET['conta']) ? trim($_GET['conta']) : null;
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;

    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;

    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $use_last_30 = (!$start_date || !$end_date);

    $where = [];
    $paramsCount = [];   // <--- PARAMS PARA COUNT (SEM LIMIT/OFFSET)
    $paramsSelect = [];  // <--- PARAMS PARA SELECT (COM LIMIT/OFFSET)

    if (!empty($conta)) {
        $where[] = "f.ContaId = :conta";
        $paramsCount[':conta'] = (int) $conta;
        $paramsSelect[':conta'] = (int) $conta;
    }

    if (!empty($status)) {
        $where[] = "f.Status = :status";
        $paramsCount[':status'] = $status;
        $paramsSelect[':status'] = $status;
    }

    if ($use_last_30) {
        $where[] = "(
            CASE WHEN f.DataBaixa IS NOT NULL THEN DATE(f.DataBaixa)
                 ELSE DATE(f.DataLancamento)
            END >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        )";
    } else {
        $start = date('Y-m-d', strtotime($start_date));
        $end = date('Y-m-d', strtotime($end_date));

        $where[] = "(
            (f.DataBaixa IS NOT NULL AND DATE(f.DataBaixa) BETWEEN :start1 AND :end1)
            OR
            (f.DataBaixa IS NULL AND DATE(f.DataLancamento) BETWEEN :start2 AND :end2)
            )";


        $paramsCount[':start1'] = $start;
        $paramsCount[':end1'] = $end;
        $paramsCount[':start2'] = $start;
        $paramsCount[':end2'] = $end;

        $paramsSelect[':start1'] = $start;
        $paramsSelect[':end1'] = $end;
        $paramsSelect[':start2'] = $start;
        $paramsSelect[':end2'] = $end;

    }

    $whereSql = count($where) ? " WHERE " . implode(" AND ", $where) : "";

    // ---------------------------
    // COUNT (sem limit/offset!)
    // ---------------------------
    $sqlCount = "
        SELECT COUNT(*) AS total
        FROM FinanceiroLancamentos f
        LEFT JOIN FinanceiroContasBancarias cb ON cb.Id = f.ContaId
        LEFT JOIN FinanceiroCategorias c ON c.Id = f.CategoriaId
        $whereSql
    ";

    $stmtCount = $db->prepare($sqlCount);
    foreach ($paramsCount as $k => $v) {
        $stmtCount->bindValue($k, $v);
    }
    $stmtCount->execute();
    $total = (int) $stmtCount->fetchColumn();

    // ---------------------------
    // SELECT PAGINADO
    // ---------------------------

    $sql = "
        SELECT f.Id,
               DATE_FORMAT(f.DataLancamento, '%Y-%m-%d %H:%i:%s') AS DataLancamento,
               DATE_FORMAT(f.DataBaixa, '%Y-%m-%d') AS DataBaixa,
               cb.Nome AS Conta,
               c.Nome AS Categoria,
               f.Descricao,
               f.Tipo,
               f.ValorBruto,
               f.Taxas,
               f.ValorLiquido,
               f.Status,
               f.ContaId
        FROM FinanceiroLancamentos f
        LEFT JOIN FinanceiroContasBancarias cb ON cb.Id = f.ContaId
        LEFT JOIN FinanceiroCategorias c ON c.Id = f.CategoriaId
        $whereSql
        ORDER BY COALESCE(f.DataBaixa, f.DataLancamento) DESC, f.Id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);

    foreach ($paramsSelect as $k => $v) {
        $stmt->bindValue($k, $v);
    }

    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    // LOG COMPLETO
    /* $debug = "---- DEBUG " . date('c') . " ----\nSQL COUNT:\n$sqlCount\nPARAMS COUNT:\n" .
        print_r($paramsCount, true) .
        "\n\nSQL SELECT:\n$sql\nPARAMS SELECT:\n" .
        print_r($paramsSelect + [':limit' => $limit, ':offset' => $offset], true) .
        "\n\n";
    @file_put_contents($logFile, $debug, FILE_APPEND); */

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);

} catch (Exception $e) {
    @file_put_contents($logFile, "ERRO FINAL: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar lançamentos.',
        'error' => $e->getMessage()
    ]);
}
