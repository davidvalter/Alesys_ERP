<?php
require_once __DIR__ . '/alenfe_compras_helpers.php';
$pdo = alenfeComprasAccess();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) alenfeComprasJson(['success' => false, 'message' => 'Nota de entrada inválida.'], 400);

$stmt = $pdo->prepare('SELECT * FROM AleNFEComprasImportacao WHERE Id=?');
$stmt->execute([$id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$note) alenfeComprasJson(['success' => false, 'message' => 'Nota não encontrada na caixa de entrada.'], 404);

if (!$note['XMLCompleto'] || $note['TipoXML'] === null || !$note['NaturezaOperacaoXML']) {
    $externalId = rawurlencode((string)$note['AleNFEId']);
    $response = alenfeHttp($pdo, 'GET', '/api/notas/' . $externalId . '/xml');
    if ((int)$response['status'] < 200 || (int)$response['status'] >= 300) {
        alenfeComprasJson([
            'success' => false,
            'xmlDisponivel' => false,
            'message' => alenfeComprasApiError($response, 'O XML completo não está disponível. A nota permanece na caixa de entrada e não pode ser importada.')
        ], 502);
    }
    $xml = alenfeComprasExtractXmlPayload($response);
    $parsed = alenfeComprasParseXml($xml);
    if (!$parsed) {
        alenfeComprasJson(['success' => false, 'xmlDisponivel' => false, 'message' => 'Não foi possível ler do XML completo os campos ide/tpNF e ide/natOp. A nota não pode ser importada.'], 422);
    }
    $update = $pdo->prepare('UPDATE AleNFEComprasImportacao SET XMLCompleto=?, TipoXML=?, NaturezaOperacaoXML=?, FornecedorCNPJ=COALESCE(?,FornecedorCNPJ), FornecedorNome=COALESCE(?,FornecedorNome) WHERE Id=?');
    $update->execute([$xml, $parsed['tipo'], $parsed['natureza'], $parsed['emitCnpj'], $parsed['emitNome'], $id]);
    $note['XMLCompleto'] = $xml;
    $note['TipoXML'] = $parsed['tipo'];
    $note['NaturezaOperacaoXML'] = $parsed['natureza'];
    $note['FornecedorCNPJ'] = $parsed['emitCnpj'] ?: $note['FornecedorCNPJ'];
    $note['FornecedorNome'] = $parsed['emitNome'] ?: $note['FornecedorNome'];
}
if ((int)$note['TipoXML'] < 0 || (int)$note['TipoXML'] > 1 || trim((string)$note['NaturezaOperacaoXML']) === '') {
    alenfeComprasJson(['success' => false, 'message' => 'XML sem tipo ou natureza da operação válidos.'], 422);
}

$itemsStmt = $pdo->prepare('SELECT * FROM AleNFEComprasImportacaoItens WHERE ImportacaoId=? ORDER BY NumeroItem');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
$cnpj = preg_replace('/\D+/', '', (string)$note['FornecedorCNPJ']);
foreach ($items as &$item) {
    $mapped = null;
    if ($cnpj !== '' && !empty($item['CodigoFornecedor'])) {
        $map = $pdo->prepare('SELECT ProdutoCodigo FROM AleNFEComprasProdutoMapeamento WHERE FornecedorCNPJ=? AND CodigoFornecedor=? AND NCM=? LIMIT 1');
        $map->execute([$cnpj, (string)$item['CodigoFornecedor'], (string)($item['NCM'] ?? '')]);
        $mapped = $map->fetchColumn();
    }
    if ($mapped) {
        $item['ProdutoCodigo'] = (int)$mapped;
    }
    $search = '%' . mb_substr((string)$item['Descricao'], 0, 60) . '%';
    $prodStmt = $pdo->prepare('SELECT Codigo, Nome, UnidadeMedida, NCM FROM Produtos WHERE Ativo=1 AND (Nome LIKE ? OR (? <> "" AND NCM=?)) ORDER BY CASE WHEN NCM=? AND ?<>"" THEN 0 ELSE 1 END, Nome LIMIT 6');
    $ncm = (string)($item['NCM'] ?? '');
    $prodStmt->execute([$search, $ncm, $ncm, $ncm, $ncm]);
    $item['sugestoes'] = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
    if ($mapped) {
        $mappedStmt = $pdo->prepare('SELECT Codigo, Nome, UnidadeMedida, NCM FROM Produtos WHERE Codigo=? AND Ativo=1');
        $mappedStmt->execute([(int)$mapped]);
        $mappedProduct = $mappedStmt->fetch(PDO::FETCH_ASSOC);
        if ($mappedProduct && !in_array((int)$mappedProduct['Codigo'], array_map('intval', array_column($item['sugestoes'], 'Codigo')), true)) {
            array_unshift($item['sugestoes'], $mappedProduct);
        }
    }
}
unset($item);

$markets = $pdo->query('SELECT Codigo, Nome, RazaoSocial, CNPJ FROM Mercados WHERE Ativo=1 ORDER BY Nome')->fetchAll(PDO::FETCH_ASSOC);
$cnpjDigits = preg_replace('/\D+/', '', (string)$note['FornecedorCNPJ']);
$marketMatch = null;
foreach ($markets as $market) {
    if (preg_replace('/\D+/', '', (string)$market['CNPJ']) === $cnpjDigits && $cnpjDigits !== '') {
        $marketMatch = (int)$market['Codigo'];
        break;
    }
}
$existingStmt = $pdo->prepare('SELECT Id FROM NFe WHERE ChaveAcesso=? LIMIT 1');
$existingStmt->execute([$note['ChaveAcesso']]);
$alreadyImported = (int)$existingStmt->fetchColumn();

alenfeComprasJson([
    'success' => true,
    'nota' => [
        'id' => (int)$note['Id'], 'Numero' => $note['Numero'], 'Serie' => $note['Serie'],
        'ChaveAcesso' => $note['ChaveAcesso'], 'DataEmissao' => $note['DataEmissao'],
        'ValorTotal' => $note['ValorTotal'], 'FornecedorNome' => $note['FornecedorNome'],
        'FornecedorCNPJ' => $note['FornecedorCNPJ'], 'Tipo' => (int)$note['TipoXML'],
        'NaturezaOperacao' => $note['NaturezaOperacaoXML'], 'MercadoCodigoSugerido' => $marketMatch,
        'ImportadaNFeId' => $alreadyImported ?: null
    ],
    'mercados' => $markets,
    'itens' => $items
]);
