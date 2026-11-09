<?php
// public/api/financeiro/baixar_lancamento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

try {
    $db = getDB();

    // suporta JSON ou form-data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;

    $lancId = isset($data['lancId']) ? (int)$data['lancId'] : 0;
    $dataBaixa = $data['dataBaixa'] ?? null;
    $observacoes = $data['observacoes'] ?? null;
    $responsavel = $_SESSION['user_id'] ?? 1;

    if (!$lancId) throw new Exception('ID do lançamento não informado.');
    if (!$dataBaixa) throw new Exception('Data da baixa é obrigatória.');

    // valida formato da data (YYYY-MM-DD)
    $d = DateTime::createFromFormat('Y-m-d', $dataBaixa);
    if (!$d || $d->format('Y-m-d') !== $dataBaixa) {
        throw new Exception('Formato da data inválido. Utilize AAAA-MM-DD.');
    }

    // buscar lançamento
    $stmt = $db->prepare("SELECT Id, ContaId, ValorBruto, Status FROM FinanceiroLancamentos WHERE Id = ? FOR UPDATE");
    $stmt->execute([$lancId]);
    $lanc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lanc) throw new Exception('Lançamento não encontrado.');
    if ($lanc['Status'] === 'pago') throw new Exception('Lançamento já está com status PAGO.');

    $contaId = (int)$lanc['ContaId'];
    $valor = (float)$lanc['ValorBruto'];

    $db->beginTransaction();

    // atualiza lançamento: set DataBaixa, Status = 'pago'
    $stmtUpd = $db->prepare("UPDATE FinanceiroLancamentos SET DataBaixa = :DataBaixa, Status = 'pago' WHERE Id = :Id");
    $stmtUpd->execute([':DataBaixa' => $dataBaixa, ':Id' => $lancId]);

    // atualiza saldo da conta: SaldoAtual = SaldoAtual + ValorBruto
    $stmtSaldo = $db->prepare("SELECT SaldoAtual FROM FinanceiroContasBancarias WHERE Id = ? FOR UPDATE");
    $stmtSaldo->execute([$contaId]);
    $curSaldo = $stmtSaldo->fetchColumn();
    if ($curSaldo === false) throw new Exception('Conta vinculada ao lançamento não encontrada.');

    $novoSaldo = (float)$curSaldo + (float)$valor;

    $stmtUpdSaldo = $db->prepare("UPDATE FinanceiroContasBancarias SET SaldoAtual = :SaldoAtual WHERE Id = :Id");
    $stmtUpdSaldo->execute([':SaldoAtual' => $novoSaldo, ':Id' => $contaId]);

    // gravar log
    $stmtLog = $db->prepare("INSERT INTO FinanceiroLogMovimentacoes (LancamentoId, ContaId, Valor, Acao, Observacoes) VALUES (:LancamentoId, :ContaId, :Valor, :Acao, :Observacoes)");
    $stmtLog->execute([
        ':LancamentoId' => $lancId,
        ':ContaId' => $contaId,
        ':Valor' => $valor,
        ':Acao' => 'ajuste',
        ':Observacoes' => $observacoes ? $observacoes : "Baixa manual por usuário {$responsavel}"
    ]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Baixa registrada com sucesso.', 'novoSaldo' => $novoSaldo]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    logError('Erro ao baixar lancamento: ' . $e->getMessage(), ['post' => $_POST, 'input' => $input ?? null]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falha ao registrar baixa: ' . $e->getMessage()]);
}
