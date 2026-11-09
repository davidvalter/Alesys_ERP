<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';
session_start();

try {
    $db = getDB();

    $produtoId = $_POST['produtoId'] ?? null;
    $quantidade = $_POST['quantidade'] ?? 0;
    $observacoes = $_POST['observacoes'] ?? null;
    $responsavelId = $_SESSION['user_id'] ?? 1;

    if (!$produtoId || $quantidade <= 0) {
        throw new Exception("Produto ou quantidade inválida.");
    }

    $sql = "INSERT INTO MovimentacoesEstoque (ProdutoId, Quantidade, Tipo, Origem, OrigemId, ResponsavelId, Observacoes)
            VALUES (?, ?, 'saida', 'ajuste', NULL, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$produtoId, $quantidade, $responsavelId, $observacoes]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
