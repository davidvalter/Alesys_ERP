<?php
// public/api/importar_ofx_detalhes.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

$importacaoId = (int) ($_GET['id'] ?? 0);
if (!$importacaoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$db = getDB();

// Cabeçalho
$importacao = $db->prepare("
    SELECT *
    FROM FinanceiroImportacoesOfx
    WHERE Id = :id
");
$importacao->execute(['id' => $importacaoId]);
$header = $importacao->fetch(PDO::FETCH_ASSOC);

// Linhas
$lancamentos = $db->prepare("
    SELECT *
    FROM FinanceiroImportacaoOfxLancamento
    WHERE ImportacaoOfxId = :id
    ORDER BY DataLancamento DESC
");
$lancamentos->execute(['id' => $importacaoId]);

echo json_encode([
    'success' => true,
    'importacao' => $header,
    'lancamentos' => $lancamentos->fetchAll(PDO::FETCH_ASSOC)
]);
