<?php
// public/api/save_formapagamento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['nome']) || empty($input['tipo'])) {
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos.']);
    exit;
}

try {
    $db = getDB();

    if (!empty($input['id'])) {
        $stmt = $db->prepare("UPDATE FinanceiroFormasPagamento SET Nome = ?, Tipo = ? WHERE Id = ?");
        $stmt->execute([$input['nome'], $input['tipo'], $input['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO FinanceiroFormasPagamento (Nome, Tipo, Ativo) VALUES (?, ?, 1)");
        $stmt->execute([$input['nome'], $input['tipo']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Erro save_formapagamento: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar forma de pagamento.']);
}
