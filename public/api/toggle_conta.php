<?php
// public/api/toggle_conta.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

try {
    $db = getDB();
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!$id) throw new Exception('Id da conta não informado.');

    // busca atual
    $stmt = $db->prepare("SELECT Ativo FROM FinanceiroContasBancarias WHERE Id = ?");
    $stmt->execute([$id]);
    $cur = $stmt->fetchColumn();
    if ($cur === false) throw new Exception('Conta não encontrada.');

    $novo = $cur ? 0 : 1;
    $stmt = $db->prepare("UPDATE FinanceiroContasBancarias SET Ativo = ? WHERE Id = ?");
    $stmt->execute([$novo, $id]);

    echo json_encode(['success' => true, 'message' => 'Status atualizado.', 'ativo' => (int)$novo]);

} catch (Exception $e) {
    logError('Erro toggle_conta.php: ' . $e->getMessage(), ['post' => $_POST]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao alterar status da conta.']);
}
