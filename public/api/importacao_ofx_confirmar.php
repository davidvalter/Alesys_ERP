<?php
// public/api/importacao_ofx_confirmar.php

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/autenticacao.php';

$moduloAtual = 'financeiro.importacao_ofx';
verificarPermissao($moduloAtual);

try {

    $usuarioId = $_SESSION['Usuario']['id'] ?? null;
    if (!$usuarioId) {
        throw new Exception('Usuário não autenticado');
    }

    $payload = json_decode(file_get_contents('php://input'), true);

    if (
        !$payload ||
        empty($payload['importacao_id']) ||
        empty($payload['lancamentos'])
    ) {
        throw new Exception('Payload inválido');
    }

    $importacaoId = (int) $payload['importacao_id'];
    $lancamentos = $payload['lancamentos'];

    $db = getDB();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    /**
     * =========================
     * Busca dados da importação
     * =========================
     */
    $stmt = $db->prepare("
        SELECT *
        FROM FinanceiroImportacaoOfx
        WHERE Id = :id
        FOR UPDATE
    ");
    $stmt->execute(['id' => $importacaoId]);
    $importacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$importacao) {
        throw new Exception('Importação não encontrada');
    }

    if ($importacao['Status'] !== 'importado') {
        throw new Exception('Importação já processada');
    }

    /**
     * =========================
     * Descobre ContaId pelo banco
     * =========================
     */
    $stmt = $db->prepare("
        SELECT Id
        FROM FinanceiroContasBancarias
        WHERE Banco = :banco
        LIMIT 1
    ");
    $stmt->execute(['banco' => $importacao['Banco']]);
    $contaId = $stmt->fetchColumn();

    if (!$contaId) {
        throw new Exception('Conta bancária não encontrada para o banco informado');
    }

    /**
     * =========================
     * Statements
     * =========================
     */
    $stmtLancOfx = $db->prepare("
        SELECT *
        FROM FinanceiroImportacaoOfxLancamento
        WHERE Id = :id
          AND ImportacaoOfxId = :importacaoId
          AND Status = 'pendente'
        FOR UPDATE
    ");

    $stmtInsertLanc = $db->prepare("
        INSERT INTO FinanceiroLancamentos
        (
            DataLancamento,
            DataPrevista,
            DataBaixa,
            Tipo,
            CategoriaId,
            ContaId,
            FormaPagamentoId,
            ValorBruto,
            Taxas,
            Descricao,
            Origem,
            OrigemId,
            Status,
            ResponsavelId,
            Observacoes
        ) VALUES (
            :DataLancamento,
            NULL,
            :DataBaixa,
            :Tipo,
            :CategoriaId,
            :ContaId,
            :FormaPagamentoId,
            :ValorBruto,
            0,
            :Descricao,
            'arquivoofx',
            :OrigemId,
            'pago',
            :ResponsavelId,
            NULL
        )
    ");

    $stmtUpdateOfxLanc = $db->prepare("
        UPDATE FinanceiroImportacaoOfxLancamento
        SET Status = 'processado',
            FinanceiroLancamentoId = :financeiroLancamentoId
        WHERE Id = :id
    ");

    $totalConfirmados = 0;

    foreach ($lancamentos as $l) {

        if (
            empty($l['id']) ||
            empty($l['categoria_id']) ||
            empty($l['forma_pagamento_id'])
        ) {
            throw new Exception('Existem lançamentos sem categoria ou forma de pagamento');
        }

        $stmtLancOfx->execute([
            'id' => $l['id'],
            'importacaoId' => $importacaoId
        ]);

        $ofxLanc = $stmtLancOfx->fetch(PDO::FETCH_ASSOC);
        if (!$ofxLanc) {
            throw new Exception('Lançamento OFX inválido');
        }

        $stmtInsertLanc->execute([
            'DataLancamento' => $importacao['DataImportacao'],
            'DataBaixa' => $ofxLanc['DataLancamento'],
            'Tipo' => $ofxLanc['Tipo'],
            'CategoriaId' => $l['categoria_id'],
            'ContaId' => $contaId,
            'FormaPagamentoId' => $l['forma_pagamento_id'],
            'ValorBruto' => $ofxLanc['ValorOriginal'],
            'Descricao' => $l['descricao'],
            'OrigemId' => $ofxLanc['Id'],
            'ResponsavelId' => $importacao['UsuarioId']
        ]);

        $financeiroLancamentoId = (int) $db->lastInsertId();

        $stmtUpdateOfxLanc->execute([
            'financeiroLancamentoId' => $financeiroLancamentoId,
            'id' => $ofxLanc['Id']
        ]);

        $totalConfirmados++;
    }

    /**
     * =========================
     * Finaliza importação
     * =========================
     */
    $stmt = $db->prepare("
        UPDATE FinanceiroImportacaoOfx
        SET Status = 'processado'
        WHERE Id = :id
    ");
    $stmt->execute(['id' => $importacaoId]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Importação confirmada com sucesso',
        'importacao_id' => $importacaoId,
        'total' => $totalConfirmados
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
