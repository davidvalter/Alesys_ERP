<?php
// public/api/list_pdv.php

header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {

    $db = getDB();

    $stmt = $db->query("
        SELECT 
            Id,
            Nome,
            TipoItem,
            Categoria,
            PrecoVenda,
            Imagem
        FROM Cardapio
        WHERE Ativo = 1
        ORDER BY Categoria, Nome
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}