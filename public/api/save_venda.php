<?php
// public/api/save_venda.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();
    $db->beginTransaction();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $itens = $input['itens'] ?? [];
    $pagamentos = $input['pagamentos'] ?? [];
    $tipo = $input['tipo'] ?? 'balcao';
    $cliente = $input['cliente'] ?? null;
    $mesa = $input['mesa'] ?? null;
    $responsavelId = $input['responsavelId'] ?? 1;

    if (empty($itens)) {
        throw new Exception('Venda sem itens');
    }

    if (empty($pagamentos)) {
        throw new Exception('Venda sem pagamentos');
    }

    // =============================
    // CALCULA TOTAL
    // =============================
    $total = 0;
    foreach ($itens as $item) {
        $total += $item['preco'] * $item['qtd'];
    }

    // =============================
    // CALCULA TOTAL PAGO
    // =============================
    $totalPago = 0;
    foreach ($pagamentos as $p) {
        $totalPago += $p['valor'];
    }

    if ($totalPago < $total) {
        throw new Exception('Pagamento insuficiente');
    }

    // =============================
    // CRIA PEDIDO
    // =============================
    $stmt = $db->prepare("
        INSERT INTO Pedidos 
        (Tipo, Cliente, Mesa, ValorTotal, ResponsavelId, Status, DataFechamento, StatusNFe)
        VALUES 
        (:tipo, :cliente, :mesa, :total, :responsavel, 'fechado', NOW(), 'pendente')
    ");

    $stmt->execute([
        ':tipo' => $tipo,
        ':cliente' => $cliente,
        ':mesa' => $mesa,
        ':total' => $total,
        ':responsavel' => $responsavelId
    ]);

    $pedidoId = $db->lastInsertId();

    // =============================
    // INSERE ITENS
    // =============================
    $stmtItem = $db->prepare("
        INSERT INTO ItensPedido 
        (PedidoId, CardapioId, TipoItem, Quantidade, ValorUnitario)
        VALUES 
        (:pedidoId, :cardapioId, :tipoItem, :qtd, :valor)
    ");

    foreach ($itens as $item) {
        $stmtItem->execute([
            ':pedidoId' => $pedidoId,
            ':cardapioId' => $item['id'],
            ':tipoItem' => $item['tipo'],
            ':qtd' => $item['qtd'],
            ':valor' => $item['preco']
        ]);
    }

    // =============================
    // INSERE PAGAMENTOS
    // =============================
    $stmtPagamento = $db->prepare("
        INSERT INTO Pagamentos 
        (PedidoId, FormaPagamento, Valor, Troco, Status)
        VALUES 
        (:pedidoId, :forma, :valor, :troco, 'confirmado')
    ");

    $trocoTotal = $totalPago - $total;
    $trocoRestante = $trocoTotal;

    foreach ($pagamentos as $p) {

        $forma = mapFormaPagamento($p['tipo']);
        $valor = $p['valor'];

        $troco = 0;

        if ($forma === 'dinheiro' && $trocoRestante > 0) {
            $troco = $trocoRestante;
            $trocoRestante = 0;
        }

        $stmtPagamento->execute([
            ':pedidoId' => $pedidoId,
            ':forma' => $forma,
            ':valor' => $valor,
            ':troco' => $troco
        ]);
    }

    // =============================
    // GERAR PAYLOAD NFE
    // =============================
    $payloadNFe = montarPayloadNFe($db, $pedidoId);

    $stmtUpdate = $db->prepare("
        UPDATE Pedidos 
        SET XMLNFe = :payload 
        WHERE Id = :id
    ");

    $stmtUpdate->execute([
        ':payload' => json_encode($payloadNFe),
        ':id' => $pedidoId
    ]);

    // 🔥 commit só depois de tudo
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Venda registrada com sucesso',
        'pedidoId' => $pedidoId
    ]);

} catch (Exception $e) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}


// =============================
// FUNÇÕES AUXILIARES
// =============================

function mapFormaPagamento($tipo)
{
    $map = [
        'dinheiro' => 'dinheiro',
        'credito' => 'cartao_credito',
        'debito' => 'cartao_debito',
        'pix' => 'pix',
        'vale' => 'vale',
        'fidelidade' => 'outros'
    ];

    return $map[$tipo] ?? 'outros';
}


function montarPayloadNFe($db, $pedidoId)
{
    $pedido = $db->query("
        SELECT * FROM Pedidos WHERE Id = {$pedidoId}
    ")->fetch(PDO::FETCH_ASSOC);

    $itens = $db->query("
        SELECT 
            i.*,
            p.Nome,
            p.NCM,
            p.CFOP,
            p.CSOSN,
            p.UnidadeMedida
        FROM ItensPedido i
        JOIN Produtos p ON p.Codigo = i.CardapioId
        WHERE i.PedidoId = {$pedidoId}
    ")->fetchAll(PDO::FETCH_ASSOC);

    $pagamentos = $db->query("
        SELECT * FROM Pagamentos 
        WHERE PedidoId = {$pedidoId}
    ")->fetchAll(PDO::FETCH_ASSOC);

    $itensNFe = [];

    foreach ($itens as $i) {
        $itensNFe[] = [
            "descricao" => $i['Nome'],
            "quantidade" => (float) $i['Quantidade'],
            "valor_unitario" => (float) $i['ValorUnitario'],
            "ncm" => $i['NCM'] ?? '00000000',
            "cfop" => $i['CFOP'] ?? '5102',
            "csosn" => $i['CSOSN'] ?? '102',
            "unidade" => $i['UnidadeMedida'] ?? 'UN'
        ];
    }

    $pagNFe = [];

    foreach ($pagamentos as $p) {

        $map = [
            'dinheiro' => '01',
            'cartao_credito' => '03',
            'cartao_debito' => '04',
            'pix' => '17',
            'vale' => '10',
            'outros' => '99'
        ];

        $pagNFe[] = [
            "forma" => $map[$p['FormaPagamento']] ?? '99',
            "valor" => (float) $p['Valor']
        ];
    }

    $cliente = null;

    if (!empty($pedido['Cliente'])) {
        $cliente = [
            "cpf_cnpj" => preg_replace('/\D/', '', $pedido['Cliente'])
        ];
    }

    return [
        "pedido_id" => $pedidoId,
        "valor_total" => (float) $pedido['ValorTotal'],
        "itens" => $itensNFe,
        "pagamentos" => $pagNFe,
        "cliente" => $cliente
    ];
}