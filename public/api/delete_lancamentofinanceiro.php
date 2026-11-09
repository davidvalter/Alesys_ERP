<?php
// public/api/delete_lancamentofinanceiro.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['lancId'])) {
        throw new Exception('ID do lançamento não informado.');
    }

    $lancId = (int)$input['lancId'];

    // Verifica se o lançamento existe
    $stmt = $pdo->prepare("SELECT Id, Origem, OrigemId FROM FinanceiroLancamentos WHERE Id = ?");
    $stmt->execute([$lancId]);
    $lanc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lanc) {
        throw new Exception("Lançamento #$lancId não encontrado.");
    }

    // Exclui o lançamento
    $stmtDel = $pdo->prepare("DELETE FROM FinanceiroLancamentos WHERE Id = ?");
    $stmtDel->execute([$lancId]);

    // Se for origem de compra, limpa a flag no sistema (ex: marcar nota sem vínculo)
    if ($lanc['Origem'] === 'compra' && $lanc['OrigemId']) {
        $stmtUpdate = $pdo->prepare("
            UPDATE NFe SET Observacoes = CONCAT(IFNULL(Observacoes, ''), '\n[Excluído lançamento financeiro em ' , NOW(), ']')
            WHERE Id = ?
        ");
        $stmtUpdate->execute([$lanc['OrigemId']]);
    }

    echo json_encode([
        'success' => true,
        'message' => "Lançamento #$lancId excluído com sucesso."
    ]);

} catch (Exception $e) {
    error_log("Erro ao excluir lançamento: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
