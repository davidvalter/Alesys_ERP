<?php
// public/api/list_pratos.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    $sql = "SELECT pr.Id, pr.Nome, m.CaminhoMiniatura AS Imagem
            FROM Pratos pr
            LEFT JOIN Midias m ON m.Id = pr.ImagemId
            WHERE pr.Status = 1
            ORDER BY pr.Nome";
    $stmt = $pdo->query($sql);
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao listar pratos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar os pratos.'], JSON_UNESCAPED_UNICODE);
}
