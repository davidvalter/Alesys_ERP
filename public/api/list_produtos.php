<?php
// public/api/list_produtos.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $sql = "
        SELECT 
            Codigo,
            Nome
        FROM Produtos
        WHERE Ativo = 1
          AND TipoProduto = 'vendavel'
        ORDER BY Nome
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}