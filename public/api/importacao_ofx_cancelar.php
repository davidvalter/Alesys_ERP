<?php
// public/api/importacao_ofx_cancelar.php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

verificarPermissao('financeiro.importacao_ofx');

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$importacaoId = (int) ($input['importacao_id'] ?? 0);

if (!$importacaoId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Importação inválida'
    ]);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    /**
     * =========================
     * Valida importação
     * =========================
     */
    $stmt = $db->prepare("
        SELECT Id
        FROM FinanceiroImportacaoOfx
        WHERE Id = :id
    ");
    $stmt->execute(['id' => $importacaoId]);

    if (!$stmt->fetch()) {
        throw new Exception('Importação não encontrada');
    }

    /**
     * =========================
     * Remove lançamentos OFX
     * =========================
     */
    $stmt = $db->prepare("
        DELETE FROM FinanceiroImportacaoOfxLancamento
        WHERE ImportacaoOfxId = :id
    ");
    $stmt->execute(['id' => $importacaoId]);

    /**
     * =========================
     * Remove cabeçalho OFX
     * =========================
     */
    $stmt = $db->prepare("
        DELETE FROM FinanceiroImportacaoOfx
        WHERE Id = :id
    ");
    $stmt->execute(['id' => $importacaoId]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Importação OFX cancelada com sucesso'
    ]);

} catch (Throwable $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
