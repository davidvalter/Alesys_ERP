<?php
require_once __DIR__ . '/alenfe_compras_helpers.php';
$pdo = alenfeComprasAccess();
$query = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) {
    alenfeComprasJson(['success' => true, 'produtos' => []]);
}
$like = '%' . mb_substr($query, 0, 80) . '%';
$stmt = $pdo->prepare("
    SELECT Codigo, Nome, UnidadeMedida, NCM, CodigoBarras
    FROM Produtos
    WHERE Ativo=1 AND (Nome LIKE ? OR CodigoBarras LIKE ? OR NCM LIKE ?)
    ORDER BY CASE WHEN Nome LIKE ? THEN 0 ELSE 1 END, Nome
    LIMIT 30
");
$stmt->execute([$like, $like, $like, $like]);
alenfeComprasJson(['success' => true, 'produtos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
