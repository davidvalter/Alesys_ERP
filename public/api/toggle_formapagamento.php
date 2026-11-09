<?php
// public/api/toggle_formapagamento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE FinanceiroFormasPagamento SET Ativo = ? WHERE Id = ?");
    $stmt->execute([$input['ativo'], $input['id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Erro toggle_formapagamento: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status.']);
}
