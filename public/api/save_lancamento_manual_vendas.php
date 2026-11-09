<?php
// public/api/save_lancamento_manual_vendas.php

session_start();

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';

header('Content-Type: application/json');

try {

    $usuarioId = $_SESSION['Usuario']['id'] ?? null;
    if (!$usuarioId) {
        throw new Exception('Usuário não autenticado');
    }

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['itens']) || !is_array($payload['itens'])) {
        throw new Exception('Dados inválidos');
    }

    $formaPagamento = intval($payload['formaPagamento'] ?? 0);
    $dataVenda = str_replace('T', ' ', $payload['dataVenda'] ?? date('Y-m-d H:i:s'));
    $observacoes = trim($payload['observacoes'] ?? '');
    $itens = $payload['itens'];

    if (!$formaPagamento) {
        throw new Exception('Forma de pagamento inválida');
    }

    $db = getDB();
    $db->beginTransaction();

    // =========================
    // Carrega parâmetros
    // =========================
    $autoFinanceiro = parametroAtivo($db, 'financeiro_auto_venda_manual', true);
    $autoEstoque = parametroAtivo($db, 'estoque_auto_venda_manual', true);

    // =========================
    // Valida forma pagamento
    // =========================
    $stmt = $db->prepare("
        SELECT 1 
        FROM FinanceiroFormasPagamento
        WHERE Id = ? AND Tipo = 'entrada' AND Ativo = 1
    ");
    $stmt->execute([$formaPagamento]);

    if (!$stmt->fetch()) {
        throw new Exception('Forma de pagamento não encontrada ou inválida');
    }

    // =========================
    // Cria pedido
    // =========================
    $stmt = $db->prepare("
        INSERT INTO Pedidos
            (DataAbertura, DataFechamento, Status, Tipo, ValorTotal, Observacoes, ResponsavelId, FormaPagamentoId)
        VALUES
            (?, ?, 'fechado', 'manual', 0, ?, ?, ?)
    ");
    $stmt->execute([
        $dataVenda,
        $dataVenda,
        $observacoes,
        $usuarioId,
        $formaPagamento
    ]);

    $pedidoId = $db->lastInsertId();
    $valorTotal = 0;

    // =========================
    // Itens do pedido
    // =========================
    foreach ($itens as $item) {

        $produtoId = intval($item['produtoId'] ?? 0);
        $quantidade = floatval($item['quantidade'] ?? 0);
        $valorUnit = floatval($item['valorUnitario'] ?? 0);

        if ($produtoId <= 0 || $quantidade <= 0 || $valorUnit <= 0) {
            throw new Exception('Item inválido');
        }

        $stmt = $db->prepare("
            INSERT INTO ItensPedido
                (PedidoId, PratoId, Quantidade, ValorUnitario, Status)
            VALUES
                (?, ?, ?, ?, 'entregue')
        ");
        $stmt->execute([
            $pedidoId,
            $produtoId,
            $quantidade,
            $valorUnit
        ]);

        $itemPedidoId = $db->lastInsertId();

        $subtotal = $quantidade * $valorUnit;
        $valorTotal += $subtotal;

        // =========================
        // Baixa automática estoque
        // =========================
        if ($autoEstoque) {
            processarBaixaEstoque(
                $db,
                $produtoId,
                $quantidade,
                $itemPedidoId,
                $usuarioId
            );
        }
    }

    // =========================
    // Atualiza total pedido
    // =========================
    $stmt = $db->prepare("UPDATE Pedidos SET ValorTotal = ? WHERE Id = ?");
    $stmt->execute([$valorTotal, $pedidoId]);

    // =========================
    // Lançamento financeiro
    // =========================
    if ($autoFinanceiro) {
        registrarFinanceiro(
            $db,
            $pedidoId,
            $valorTotal,
            $formaPagamento,
            $usuarioId,
            $observacoes
        );
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'pedidoId' => $pedidoId
    ]);

} catch (Exception $e) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    logError("Erro ao salvar venda manual", [
        'erro' => $e->getMessage(),
        'payload' => $payload ?? null
    ]);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


// =======================================================
// FUNÇÕES AUXILIARES
// =======================================================

function parametroAtivo($db, $chave, $default = true)
{
    $stmt = $db->prepare("
        SELECT Valor 
        FROM Parametros 
        WHERE Chave = ?
        LIMIT 1
    ");
    $stmt->execute([$chave]);

    $valor = $stmt->fetchColumn();

    if ($valor === false)
        return $default;

    return $valor == '1';
}


function processarBaixaEstoque($db, $produtoId, $quantidade, $itemPedidoId, $usuarioId)
{
    $stmt = $db->prepare("
        SELECT IngredienteId, Quantidade, UnidadeMedida
        FROM Receitas
        WHERE PratoId = ? 
          AND Ativo = 1 
          AND MetodoBaixa = 'auto'
    ");
    $stmt->execute([$produtoId]);

    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$receitas)
        return;

    foreach ($receitas as $r) {

        $qtdBaixa = $r['Quantidade'] * $quantidade;

        // Baixa produção
        $stmt = $db->prepare("
            INSERT INTO BaixasProducao
                (ProdutoId, Quantidade, UnidadeMedida, Motivo, ResponsavelId, ItemPedidoId)
            VALUES
                (?, ?, ?, 'preparo', ?, ?)
        ");
        $stmt->execute([
            $r['IngredienteId'],
            $qtdBaixa,
            $r['UnidadeMedida'],
            $usuarioId,
            $itemPedidoId
        ]);

        // Movimentação estoque
        $stmt = $db->prepare("
            INSERT INTO MovimentacoesEstoque
                (ProdutoId, Quantidade, Tipo, Origem, OrigemId, ResponsavelId)
            VALUES
                (?, ?, 'saida', 'venda', ?, ?)
        ");
        $stmt->execute([
            $r['IngredienteId'],
            $qtdBaixa,
            $itemPedidoId,
            $usuarioId
        ]);
    }
}


function registrarFinanceiro($db, $pedidoId, $valor, $formaPagamento, $usuarioId, $obs)
{
    $stmt = $db->prepare("
        INSERT INTO FinanceiroLancamentos
            (
                DataLancamento,
                Tipo,
                FormaPagamentoId,
                ValorBruto,
                Taxas,
                Origem,
                OrigemId,
                Status,
                ResponsavelId,
                Observacoes
            )
        VALUES
            (
                NOW(),
                'entrada',
                ?,
                ?,
                0,
                'venda',
                ?,
                'pago',
                ?,
                ?
            )
    ");

    $stmt->execute([
        $formaPagamento,
        $valor,
        $pedidoId,
        $usuarioId,
        $obs
    ]);
}
