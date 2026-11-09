<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

try {
    $db = getDB();
    $db->beginTransaction();

    // Captura dados POST (permite JSON e form-data)
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    if (!$data)
        $data = $_POST;

    $id = $data['id'] ?? null;
    $tipo = $data['tipo'] ?? 'entrada';
    $categoriaId = $data['categoriaId'] ?? null;
    $contaId = $data['contaId'] ?? null;
    $formaPagamentoId = $data['formaPagamentoId'] ?? null;
    $valorBruto = $data['valorBruto'] ?? 0;
    $taxas = $data['taxas'] ?? 0;
    $descricao = trim($data['descricao'] ?? '');
    $dataLancamento = $data['dataLancamento'] ?? date('Y-m-d H:i:s');
    $dataPrevista = $data['dataPrevista'] ?? null;
    $dataBaixa = $data['dataBaixa'] ?? null;
    $status = $data['status'] ?? 'pendente';
    $origem = $data['origem'] ?? 'manual';
    $origemId = $data['origemId'] ?? null;
    $responsavelId = $_SESSION['user_id'] ?? 1;

    // Aplica sinal baseado no tipo
    if (strtolower($tipo) === 'saída' || strtolower($tipo) === 'saida') {
        $valorBruto = -abs($valorBruto);
    } else {
        $valorBruto = abs($valorBruto);
    }


    // Validação mínima
    if (empty($descricao) || $valorBruto == 0) {
        throw new Exception("Descrição e valor são obrigatórios.");
    }

    if ($id) {
        // Atualiza
        $sql = "
            UPDATE FinanceiroLancamentos
            SET Tipo = :Tipo,
                CategoriaId = :CategoriaId,
                ContaId = :ContaId,
                FormaPagamentoId = :FormaPagamentoId,
                ValorBruto = :ValorBruto,
                Taxas = :Taxas,
                Descricao = :Descricao,
                DataLancamento = :DataLancamento,
                DataPrevista = :DataPrevista,
                DataBaixa = :DataBaixa,
                Status = :Status,
                Origem = :Origem,
                OrigemId = :OrigemId,
                ResponsavelId = :ResponsavelId
            WHERE Id = :Id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':Tipo' => $tipo,
            ':CategoriaId' => $categoriaId,
            ':ContaId' => $contaId,
            ':FormaPagamentoId' => $formaPagamentoId,
            ':ValorBruto' => $valorBruto,
            ':Taxas' => $taxas,
            ':Descricao' => $descricao,
            ':DataLancamento' => $dataLancamento,
            ':DataPrevista' => $dataPrevista,
            ':DataBaixa' => $dataBaixa,
            ':Status' => $status,
            ':Origem' => $origem,
            ':OrigemId' => $origemId,
            ':ResponsavelId' => $responsavelId,
            ':Id' => $id
        ]);
    } else {
        // Insere novo
        $sql = "
            INSERT INTO FinanceiroLancamentos
            (Tipo, CategoriaId, ContaId, FormaPagamentoId, ValorBruto, Taxas, Descricao,
             DataLancamento, DataPrevista, DataBaixa, Status, Origem, OrigemId, ResponsavelId)
            VALUES
            (:Tipo, :CategoriaId, :ContaId, :FormaPagamentoId, :ValorBruto, :Taxas, :Descricao,
             :DataLancamento, :DataPrevista, :DataBaixa, :Status, :Origem, :OrigemId, :ResponsavelId)
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':Tipo' => $tipo,
            ':CategoriaId' => $categoriaId,
            ':ContaId' => $contaId,
            ':FormaPagamentoId' => $formaPagamentoId,
            ':ValorBruto' => $valorBruto,
            ':Taxas' => $taxas,
            ':Descricao' => $descricao,
            ':DataLancamento' => $dataLancamento,
            ':DataPrevista' => $dataPrevista,
            ':DataBaixa' => $dataBaixa,
            ':Status' => $status,
            ':Origem' => $origem,
            ':OrigemId' => $origemId,
            ':ResponsavelId' => $responsavelId
        ]);

        $id = $db->lastInsertId();
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $id ? 'Lançamento salvo com sucesso.' : 'Lançamento criado com sucesso.',
        'id' => $id
    ]);

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    logError("Erro ao salvar lançamento financeiro: " . $e->getMessage(), [
        'post' => $_POST,
        'json' => $data ?? null
    ]);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
