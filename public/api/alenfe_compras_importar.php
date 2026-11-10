<?php
require_once __DIR__ . '/alenfe_compras_helpers.php';
$pdo = alenfeComprasAccess();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alenfeComprasJson(['success' => false, 'message' => 'Método não permitido.'], 405);
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) alenfeComprasJson(['success' => false, 'message' => 'Corpo JSON inválido.'], 400);
$importId = filter_var($input['importacaoId'] ?? null, FILTER_VALIDATE_INT);
$marketId = filter_var($input['mercadoCodigo'] ?? null, FILTER_VALIDATE_INT);
$mapping = $input['itens'] ?? [];
if (!$importId || !$marketId || !is_array($mapping)) {
    alenfeComprasJson(['success' => false, 'message' => 'Selecione o fornecedor e vincule todos os itens a produtos cadastrados.'], 400);
}
$noteStmt = $pdo->prepare('SELECT * FROM AleNFEComprasImportacao WHERE Id=? FOR UPDATE');
$pdo->beginTransaction();
try {
    $noteStmt->execute([$importId]);
    $note = $noteStmt->fetch(PDO::FETCH_ASSOC);
    if (!$note) throw new RuntimeException('Nota não encontrada na caixa de entrada.');
    if ($note['StatusImportacao'] === 'importada' || $note['NFeId']) throw new RuntimeException('Esta nota já foi importada.');
    if (empty($note['XMLCompleto']) || $note['TipoXML'] === null || trim((string)$note['NaturezaOperacaoXML']) === '') {
        throw new RuntimeException('O XML completo precisa ser carregado e validado antes da importação.');
    }
    $duplicate = $pdo->prepare('SELECT Id FROM NFe WHERE ChaveAcesso=? LIMIT 1');
    $duplicate->execute([$note['ChaveAcesso']]);
    if ($duplicate->fetchColumn()) throw new RuntimeException('Já existe uma nota Alesys com esta chave de acesso.');

    $marketStmt = $pdo->prepare('SELECT Codigo, CNPJ FROM Mercados WHERE Codigo=? AND Ativo=1');
    $marketStmt->execute([$marketId]);
    $market = $marketStmt->fetch(PDO::FETCH_ASSOC);
    if (!$market) throw new RuntimeException('Fornecedor selecionado não encontrado ou inativo.');

    $itemsStmt = $pdo->prepare('SELECT * FROM AleNFEComprasImportacaoItens WHERE ImportacaoId=? ORDER BY NumeroItem FOR UPDATE');
    $itemsStmt->execute([$importId]);
    $sourceItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$sourceItems) throw new RuntimeException('A nota não contém itens para importar.');

    $selectedProducts = [];
    foreach ($sourceItems as $item) {
        $productId = filter_var($mapping[(string)$item['Id']] ?? null, FILTER_VALIDATE_INT);
        if (!$productId) throw new RuntimeException('Vincule o item ' . (int)$item['NumeroItem'] . ' a um produto antes de continuar.');
        $productStmt = $pdo->prepare('SELECT Codigo, UnidadeMedida FROM Produtos WHERE Codigo=? AND Ativo=1');
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new RuntimeException('Um dos produtos selecionados não está ativo.');
        $selectedProducts[(int)$item['Id']] = $product;
    }

    $issued = $note['DataEmissao'] ?: date('Y-m-d H:i:s');
    $insert = $pdo->prepare("
        INSERT INTO NFe
          (Numero, Serie, Tipo, DataEmissao, DataRecebimento, NaturezaOperacao, ChaveAcesso, ValorTotal, Desconto, fk_Mercados_Codigo, Observacoes, Status)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 0, ?, ?, 1)
    ");
    $observations = 'Importada manualmente do AleNFE. Chave: ' . $note['ChaveAcesso'];
    $insert->execute([
        $note['Numero'], $note['Serie'], (int)$note['TipoXML'], $issued,
        $note['NaturezaOperacaoXML'], $note['ChaveAcesso'], $note['ValorTotal'],
        $marketId, $observations
    ]);
    $nfeId = (int)$pdo->lastInsertId();
    $insertItem = $pdo->prepare('INSERT INTO NFeItens (fk_NFe_Id, fk_Produtos_Codigo, Quantidade, ValorUnitario, UnidadeMedida, DescricaoComplementar) VALUES (?, ?, ?, ?, ?, ?)');
    $saveMapping = $pdo->prepare("
        INSERT INTO AleNFEComprasProdutoMapeamento (FornecedorCNPJ, CodigoFornecedor, NCM, ProdutoCodigo)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE ProdutoCodigo=VALUES(ProdutoCodigo)
    ");
    $updateLine = $pdo->prepare('UPDATE AleNFEComprasImportacaoItens SET ProdutoCodigo=? WHERE Id=? AND ImportacaoId=?');
    $supplierCnpj = preg_replace('/\D+/', '', (string)$note['FornecedorCNPJ']);
    foreach ($sourceItems as $item) {
        $product = $selectedProducts[(int)$item['Id']];
        $insertItem->execute([
            $nfeId, $product['Codigo'], $item['Quantidade'], $item['ValorUnitario'],
            $item['UnidadeMedida'] ?: ($product['UnidadeMedida'] ?: 'UN'),
            mb_substr((string)$item['Descricao'], 0, 255)
        ]);
        $updateLine->execute([$product['Codigo'], $item['Id'], $importId]);
        if ($supplierCnpj !== '' && trim((string)$item['CodigoFornecedor']) !== '') {
            $saveMapping->execute([
                $supplierCnpj, (string)$item['CodigoFornecedor'],
                (string)($item['NCM'] ?? ''), $product['Codigo']
            ]);
        }
    }
    $finish = $pdo->prepare("UPDATE AleNFEComprasImportacao SET StatusImportacao='importada', MercadoCodigo=?, NFeId=? WHERE Id=?");
    $finish->execute([$marketId, $nfeId, $importId]);
    $pdo->commit();
    alenfeComprasJson([
        'success' => true,
        'notaId' => $nfeId,
        'message' => 'Nota importada. O estoque não foi alterado.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    alenfeComprasJson(['success' => false, 'message' => $e->getMessage()], 422);
}
