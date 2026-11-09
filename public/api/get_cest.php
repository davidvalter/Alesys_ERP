<?php
// public/api/get_cest.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    $ncm = trim($_GET['ncm'] ?? '');

    if ($ncm === '') {
        echo json_encode(['success' => true, 'dados' => []]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT c.Codigo, c.Descricao
        FROM ProdutosCEST c
        INNER JOIN ProdutosCESTNCM cn ON cn.CEST = c.Codigo
        WHERE cn.NCM = ?
        ORDER BY c.Codigo
    ");
    $stmt->execute([$ncm]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'dados' => $dados]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>