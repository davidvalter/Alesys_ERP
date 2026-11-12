<?php
// public/api/list_produtos.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    $sql = "SELECT p.Codigo, p.Nome, m.CaminhoMiniatura AS Imagem
            FROM Produtos p
            LEFT JOIN Midias m ON m.Id = p.ImagemId
            WHERE p.Ativo = 1
              AND p.TipoProduto = 'vendavel'
            ORDER BY p.Nome";
    $stmt = $pdo->query($sql);
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao listar produtos vendáveis: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar os produtos.'], JSON_UNESCAPED_UNICODE);
}
