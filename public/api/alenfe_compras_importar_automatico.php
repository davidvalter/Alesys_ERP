<?php
require_once __DIR__ . '/alenfe_compras_helpers.php';
require_once __DIR__ . '/alenfe_compras_persistir.php';

$pdo = alenfeComprasAccess();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alenfeComprasJson(['success' => false, 'message' => 'Método não permitido.'], 405);
}
alenfeComprasVerifyCsrf();

function alenfeComprasDigits($value): string
{
    return preg_replace('/\D+/', '', (string)$value);
}

$markets = $pdo->query('SELECT Codigo, CNPJ FROM Mercados WHERE Ativo=1')->fetchAll(PDO::FETCH_ASSOC);
$marketsByCnpj = [];
foreach ($markets as $market) {
    $digits = alenfeComprasDigits($market['CNPJ']);
    if ($digits !== '') $marketsByCnpj[$digits] = (int)$market['Codigo'];
}

$notes = $pdo->query("
    SELECT *
    FROM AleNFEComprasImportacao
    WHERE StatusImportacao='pendente'
    ORDER BY DataEmissao DESC, Id DESC
    LIMIT 250
")->fetchAll(PDO::FETCH_ASSOC);

$stats = ['importadas' => 0, 'ignoradas' => 0, 'falhas' => 0];
$details = [];
$duplicateStmt = $pdo->prepare('SELECT Id FROM NFe WHERE ChaveAcesso=? LIMIT 1');
$itemsStmt = $pdo->prepare('SELECT * FROM AleNFEComprasImportacaoItens WHERE ImportacaoId=? ORDER BY NumeroItem');

foreach ($notes as $note) {
    $reason = null;
    try {
        $supplierCnpj = alenfeComprasDigits($note['FornecedorCNPJ']);
        $marketId = $marketsByCnpj[$supplierCnpj] ?? null;
        if (!$marketId) {
            $reason = 'Sem fornecedor ativo cadastrado com o CNPJ da nota.';
        } elseif (($note['SituacaoAleNFE'] ?? '') !== 'XML_COMPLETO') {
            $reason = 'XML completo ainda não disponível no AleNFE.';
        }

        if ($reason === null) {
            $duplicateStmt->execute([$note['ChaveAcesso']]);
            if ($duplicateStmt->fetchColumn()) {
                $reason = 'A chave de acesso já existe na tabela NFe.';
            }
        }

        $mapping = [];
        if ($reason === null) {
            $itemsStmt->execute([$note['Id']]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$items) {
                $reason = 'A nota não possui itens para conciliar.';
            } else {
                foreach ($items as $item) {
                    $productId = (int)($item['ProdutoCodigo'] ?? 0);
                    if ($productId <= 0 && trim((string)$item['CodigoFornecedor']) !== '') {
                        $mapStmt = $pdo->prepare("
                            SELECT ProdutoCodigo
                            FROM AleNFEComprasProdutoMapeamento
                            WHERE FornecedorCNPJ=? AND CodigoFornecedor=? AND NCM=?
                            LIMIT 1
                        ");
                        $mapStmt->execute([
                            $supplierCnpj,
                            (string)$item['CodigoFornecedor'],
                            (string)($item['NCM'] ?? '')
                        ]);
                        $productId = (int)$mapStmt->fetchColumn();
                    }
                    if ($productId <= 0) {
                        $reason = 'Um ou mais itens ainda não têm produto vinculado.';
                        break;
                    }
                    $mapping[(string)$item['Id']] = $productId;
                }
            }
        }

        if ($reason === null) {
            $xml = (string)($note['XMLCompleto'] ?? '');
            $parsed = $xml !== '' ? alenfeComprasParseXml($xml) : null;
            if (!$parsed) {
                $externalId = rawurlencode((string)$note['AleNFEId']);
                $response = alenfeHttp($pdo, 'GET', '/api/integracoes/alesys/notas-compras/' . $externalId . '/xml');
                if ((int)$response['status'] < 200 || (int)$response['status'] >= 300) {
                    $reason = 'Falha ao obter XML completo do AleNFE (HTTP ' . (int)$response['status'] . ').';
                } else {
                    $xml = alenfeComprasExtractXmlPayload($response);
                    $parsed = alenfeComprasParseXml($xml);
                    if (!$parsed) $reason = 'Não foi possível extrair Tipo e NaturezaOperacao do XML.';
                }
            }
        }

        if ($reason === null) {
            if ($parsed['emitCnpj'] && $supplierCnpj !== $parsed['emitCnpj']) {
                $reason = 'O CNPJ do emitente no XML difere do CNPJ retornado na listagem.';
            } else {
                $saveXml = $pdo->prepare("
                    UPDATE AleNFEComprasImportacao
                    SET XMLCompleto=?, TipoXML=?, NaturezaOperacaoXML=?,
                        FornecedorCNPJ=COALESCE(?,FornecedorCNPJ),
                        FornecedorNome=COALESCE(?,FornecedorNome)
                    WHERE Id=?
                ");
                $saveXml->execute([
                    $xml, $parsed['tipo'], $parsed['natureza'], $parsed['emitCnpj'], $parsed['emitNome'], $note['Id']
                ]);
                $note['FornecedorCNPJ'] = $parsed['emitCnpj'] ?: $note['FornecedorCNPJ'];
                $nfeId = alenfeComprasPersistirImportacao($pdo, (int)$note['Id'], (int)$marketId, $mapping, true);
                $stats['importadas']++;
                if (count($details) < 20) $details[] = 'Nota ' . $note['Numero'] . ': importada como NFe #' . $nfeId . '.';
                continue;
            }
        }

        $stats['ignoradas']++;
        if (count($details) < 20) $details[] = 'Nota ' . $note['Numero'] . ': ' . $reason;
    } catch (Throwable $e) {
        $stats['falhas']++;
        if (count($details) < 20) $details[] = 'Nota ' . $note['Numero'] . ': falha na importação (' . $e->getMessage() . ').';
    }
}

$message = $stats['importadas'] . ' nota(s) importada(s) automaticamente; '
    . $stats['ignoradas'] . ' sem correspondência suficiente; '
    . $stats['falhas'] . ' falha(s). O estoque não foi alterado.';
alenfeComprasJson(['success' => true] + $stats + ['message' => $message, 'detalhes' => $details]);
