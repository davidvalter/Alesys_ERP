<?php
// public/api/list_pratos.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $sql = "
        SELECT 
            Id,
            Nome
        FROM Pratos
        WHERE Status = 1
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