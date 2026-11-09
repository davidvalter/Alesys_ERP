<?php
session_start();

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';

header('Content-Type: application/json');

try {

    $usuarioId = $_SESSION['Usuario']['id'] ?? null;
    if (!$usuarioId)
        throw new Exception('Usuário não autenticado');

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['itens'])) {
        throw new Exception('Dados inválidos');
    }

    $data = $payload['data'];
    $obs = trim($payload['observacoes'] ?? '');
    $itens = $payload['itens'];

    $db = getDB();
    $db->beginTransaction();

    // Verifica se já existe fechamento
    $stmt = $db->prepare("SELECT Id FROM FechamentosCaixa WHERE Data=?");
    $stmt->execute([$data]);

    if ($stmt->fetch()) {
        throw new Exception('Já existe fechamento para esta data.');
    }

    $valorTotal = array_sum(array_column($itens, 'valor'));

    // Cria fechamento
    $stmt = $db->prepare("
        INSERT INTO FechamentosCaixa
            (Data, ValorTotal, Observacoes, ResponsavelId)
        VALUES
            (?, ?, ?, ?)
    ");
    $stmt->execute([$data, $valorTotal, $obs, $usuarioId]);

    $fechamentoId = $db->lastInsertId();

    // Itens
    foreach ($itens as $i) {

        $stmt = $db->prepare("
            INSERT INTO FechamentosCaixaItens
                (FechamentoId, Operadora, MeioPagamento, Valor)
            VALUES
                (?, ?, ?, ?)
        ");
        $stmt->execute([
            $fechamentoId,
            $i['operadora'],
            $i['meio'],
            $i['valor']
        ]);
    }

    // Verifica parâmetro financeiro
    if (paramAtivo($db, 'financeiro_auto_fechamento_caixa')) {
        registrarFinanceiroFechamento($db, $fechamentoId, $itens, $usuarioId, $data);
    }

    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    if (isset($db) && $db->inTransaction())
        $db->rollBack();

    logError("Erro fechamento caixa", [
        'erro' => $e->getMessage(),
        'payload' => $payload ?? null
    ]);

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


function paramAtivo($db, $chave)
{
    $stmt = $db->prepare("SELECT Valor FROM Parametros WHERE Chave=?");
    $stmt->execute([$chave]);
    return $stmt->fetchColumn() == '1';
}


function registrarFinanceiroFechamento($db, $fechamentoId, $itens, $usuarioId, $data)
{

    foreach ($itens as $i) {

        $stmt = $db->prepare("
            INSERT INTO FinanceiroLancamentos
                (DataLancamento, Tipo, ValorBruto, FormaPagamentoId, Origem, OrigemId, Status, ResponsavelId, Observacoes)
            VALUES
                (?, 'entrada', ?, NULL, 'venda', ?, 'pago', ?, ?)
        ");

        $stmt->execute([
            $data,
            $i['valor'],
            $fechamentoId,
            $usuarioId,
            $i['operadora'] . ' - ' . strtoupper($i['meio'])
        ]);
    }
}
