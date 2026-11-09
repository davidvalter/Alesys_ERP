<?php
// public/api/list_notas_fiscais.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    // ============================
    // Parâmetros do DataTables
    // ============================
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = trim($_GET['search']['value'] ?? '');

    // ============================
    // Colunas permitidas para ordenação
    // ============================
    $columns = [
        0 => 'n.Numero',
        1 => 'n.Serie',
        2 => 'n.Tipo',
        3 => 'n.DataEmissao',
        4 => 'n.DataRecebimento',
        5 => 'm.Nome',
        6 => 'n.ValorTotal',
        7 => 'n.Status'
    ];

    $orderColumnIndex = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = ($_GET['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
    $orderColumn = $columns[$orderColumnIndex] ?? 'n.Numero';

    // ============================
    // Query base
    // ============================
    $query = "
        SELECT SQL_CALC_FOUND_ROWS
            n.Id,
            n.Numero,
            n.Serie,
            CASE 
                WHEN n.Tipo = 0 THEN 'Entrada'
                ELSE 'Saída'
            END AS Tipo,
            DATE_FORMAT(n.DataEmissao, '%d/%m/%Y %H:%i') AS DataEmissao,
            DATE_FORMAT(n.DataRecebimento, '%d/%m/%Y %H:%i') AS DataRecebimento,
            m.Nome AS MercadoNome,
            n.ValorTotal,
            n.Status,
            CASE 
                WHEN n.Status = 1 THEN 'Ativa'
                ELSE 'Desativada'
            END AS StatusTexto
        FROM NFe n
        INNER JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
        WHERE 1 = 1
    ";

    $params = [];

    // ============================
    // Busca global (DataTables)
    // ============================
    if ($searchValue !== '') {
        $query .= "
            AND (
                n.Numero       LIKE :search_numero
                OR n.Serie     LIKE :search_serie
                OR n.ChaveAcesso LIKE :search_chave
                OR m.Nome      LIKE :search_mercado
                OR n.ValorTotal LIKE :search_valor
            )
        ";

        $params[':search_numero'] = "%{$searchValue}%";
        $params[':search_serie'] = "%{$searchValue}%";
        $params[':search_chave'] = "%{$searchValue}%";
        $params[':search_mercado'] = "%{$searchValue}%";
        $params[':search_valor'] = "%{$searchValue}%";
    }

    // ============================
    // Ordenação e paginação
    // ============================
    $query .= " ORDER BY {$orderColumn} {$orderDir}";
    $query .= " LIMIT :start, :length";

    $stmt = $pdo->prepare($query);

    // Bind parâmetros de busca
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind paginação
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ============================
    // Total de registros filtrados
    // ============================
    $totalRecords = (int) $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

    // ============================
    // Formatação final
    // ============================
    foreach ($data as &$row) {
        $row['ValorTotal'] = 'R$ ' . number_format((float) $row['ValorTotal'], 2, ',', '.');
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Erro ao carregar notas fiscais: ' . $e->getMessage(),
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
