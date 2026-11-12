<?php
// Produtos ativos que possuem itens em notas de compra não canceladas.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sql = "SELECT DISTINCT p.Codigo, p.Nome
            FROM Produtos p
            JOIN NFeItens ni ON ni.fk_Produtos_Codigo = p.Codigo
            JOIN NFe n ON n.Id = ni.fk_NFe_Id
            WHERE p.Ativo = 1
              AND n.Status = 1
            ORDER BY p.Nome";
    $produtos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $produtos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao carregar produtos para dashboard: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar os produtos.']);
}
