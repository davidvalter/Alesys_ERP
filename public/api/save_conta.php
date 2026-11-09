<?php
// public/api/save_conta.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

try {
    $db = getDB();

    // suporta JSON e form-data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        $data = $_POST;
    }

    // validação
    $nome = trim($data['Nome'] ?? '');
    $tipo = $data['Tipo'] ?? 'caixa';
    $banco = $data['Banco'] ?? null;
    $agencia = $data['Agencia'] ?? null;
    $conta = $data['Conta'] ?? null;
    $saldoInicial = isset($data['SaldoInicial']) ? (float)$data['SaldoInicial'] : 0.00;
    $ativo = isset($data['Ativo']) ? (int)$data['Ativo'] : 1;

    if ($nome === '') {
        throw new Exception('Nome da conta é obrigatório.');
    }

    $id = isset($data['Id']) && $data['Id'] ? (int)$data['Id'] : null;

    if ($id) {
        // update
        $sql = "UPDATE FinanceiroContasBancarias
                SET Nome = :Nome, Tipo = :Tipo, Banco = :Banco, Agencia = :Agencia, Conta = :Conta, SaldoInicial = :SaldoInicial, Ativo = :Ativo
                WHERE Id = :Id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':Nome' => $nome,
            ':Tipo' => $tipo,
            ':Banco' => $banco,
            ':Agencia' => $agencia,
            ':Conta' => $conta,
            ':SaldoInicial' => $saldoInicial,
            ':Ativo' => $ativo,
            ':Id' => $id
        ]);

        echo json_encode(['success' => true, 'message' => 'Conta atualizada com sucesso.', 'id' => $id]);
        exit;
    } else {
        // insert: SaldoAtual inicia em 0.00 (ignora default 1.00 da tabela)
        $sql = "INSERT INTO FinanceiroContasBancarias
                (Nome, Tipo, Banco, Agencia, Conta, SaldoInicial, SaldoAtual, Ativo)
                VALUES (:Nome, :Tipo, :Banco, :Agencia, :Conta, :SaldoInicial, :SaldoAtual, :Ativo)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':Nome' => $nome,
            ':Tipo' => $tipo,
            ':Banco' => $banco,
            ':Agencia' => $agencia,
            ':Conta' => $conta,
            ':SaldoInicial' => $saldoInicial,
            ':SaldoAtual' => 0.00,
            ':Ativo' => $ativo
        ]);

        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Conta criada com sucesso.', 'id' => (int)$newId]);
        exit;
    }

} catch (Exception $e) {
    logError('Erro save_conta.php: ' . $e->getMessage(), ['post' => $_POST, 'input' => $input ?? null]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar conta: ' . $e->getMessage()]);
}
