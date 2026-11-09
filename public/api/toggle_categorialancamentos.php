<?php
// public/api/toggle_categorialancamentos.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['Id'])) {
        throw new Exception('ID não informado.');
    }

    $id = (int)$data['Id'];

    // Alterna o status atual
    $stmt = $db->prepare("UPDATE FinanceiroCategorias SET Ativo = IF(Ativo = 1, 0, 1) WHERE Id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Status da categoria alterado.'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Erro toggle_categorialancamentos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao alterar status da categoria.']);
}
