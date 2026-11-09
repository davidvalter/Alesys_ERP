<?php
// public/api/importar_ofx_processar.php

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../repositories/FinanceiroImportacaoOfxRepository.php';
require_once __DIR__ . '/../repositories/FinanceiroImportacaoOfxLancamento.php';
require_once __DIR__ . '/../repositories/FinanceiroContaBancariaRepository.php';
require_once __DIR__ . '/../services/FinanceiroOfxProcessarService.php';

try {

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['transacoes'])) {
        throw new Exception('Payload inválido');
    }

    // 🔐 Ajuste conforme seu sistema de autenticação
    if (
        !isset($_SESSION['Usuario']) ||
        empty($_SESSION['Usuario']['id'])
    ) {
        throw new Exception('Usuário não autenticado');
    }

    $usuarioId = (int) $_SESSION['Usuario']['id'];


    $db = getDB();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $service = new FinanceiroOfxProcessarService(
        $db,
        new FinanceiroImportacoesOfxRepository($db),
        new FinanceiroImportacaoOfxLancamentoRepository($db),
        new FinanceiroContaBancariaRepository($db)
    );

    $resultado = $service->processar($payload, (int) $usuarioId);

    echo json_encode([
        'success' => true,
        'resumo' => $resultado
    ]);

} catch (Throwable $e) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
