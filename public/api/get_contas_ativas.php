<?php
// public/api/get_contas_ativas.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $stmt = $db->query("
        SELECT Id, Nome 
        FROM FinanceiroContasBancarias 
        WHERE Ativo = 1 
        ORDER BY Nome ASC
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    error_log('Erro get_contas_ativas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar contas bancárias.']);
}
?>
