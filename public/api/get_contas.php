<?php
// public/api/get_contas.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $stmt = $db->query("SELECT Id, Nome, Tipo, Banco, Agencia, Conta, SaldoInicial, SaldoAtual, Ativo, CriadoEm FROM FinanceiroContasBancarias ORDER BY Nome ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // normaliza valores numéricos
    foreach ($rows as &$r) {
        $r['SaldoInicial'] = number_format((float)$r['SaldoInicial'], 2, '.', '');
        $r['SaldoAtual'] = number_format((float)$r['SaldoAtual'], 2, '.', '');
    }
    unset($r);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    error_log('Erro get_contas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar contas bancárias.']);
}
