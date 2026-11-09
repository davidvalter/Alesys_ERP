<?php
// public/api/get_formaspagamento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT Id, Nome, Tipo, Ativo FROM FinanceiroFormasPagamento ORDER BY Nome ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log('Erro get_formaspagamento: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar formas de pagamento.']);
}
