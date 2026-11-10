<?php
require_once __DIR__ . '/alenfe_compras_helpers.php';

$pdo = alenfeComprasAccess();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alenfeComprasJson(['success' => false, 'message' => 'Método não permitido.'], 405);
}
alenfeComprasVerifyCsrf();
$response = alenfeHttp($pdo, 'GET', alenfeComprasListPath($pdo));
if ((int)$response['status'] < 200 || (int)$response['status'] >= 300) {
    alenfeComprasJson(['success' => false, 'message' => alenfeComprasApiError($response, 'Não foi possível consultar as notas de compra.')], 502);
}
$payload = json_decode((string)$response['body'], true);
if (!is_array($payload)) {
    alenfeComprasJson(['success' => false, 'message' => 'AleNFE retornou uma resposta que não está em JSON.'], 502);
}
$notas = $payload['notas'] ?? $payload['data'] ?? $payload['items'] ?? $payload['resultados'] ?? null;
if (is_array($notas) && isset($notas['data']) && is_array($notas['data'])) {
    $notas = $notas['data'];
}
if (!is_array($notas)) {
    alenfeComprasJson(['success' => false, 'message' => 'A resposta de AleNFE não contém uma lista reconhecida de notas. Configure ALENFE_COMPRAS_LIST_PATH conforme o endpoint de integração.'], 502);
}

function pickValue(array $row, array $keys, $default = null)
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            return $row[$key];
        }
    }
    return $default;
}
function isoToMysql($value): ?string
{
    if (!$value) return null;
    try { return (new DateTime((string)$value))->format('Y-m-d H:i:s'); }
    catch (Throwable $e) { return null; }
}

$now = date('Y-m-d H:i:s');
$upsert = $pdo->prepare("
    INSERT INTO AleNFEComprasImportacao
      (AleNFEId, ChaveAcesso, Numero, Serie, Modelo, FornecedorCNPJ, FornecedorNome, DataEmissao, ValorTotal, SituacaoAleNFE, UltimaConsultaEm)
    VALUES
      (:external, :key, :number, :series, :model, :cnpj, :supplier, :issued, :total, :situation, :checked)
    ON DUPLICATE KEY UPDATE
      AleNFEId=VALUES(AleNFEId), Numero=VALUES(Numero), Serie=VALUES(Serie), Modelo=VALUES(Modelo),
      FornecedorCNPJ=VALUES(FornecedorCNPJ), FornecedorNome=VALUES(FornecedorNome),
      DataEmissao=VALUES(DataEmissao), ValorTotal=VALUES(ValorTotal), SituacaoAleNFE=VALUES(SituacaoAleNFE),
      UltimaConsultaEm=VALUES(UltimaConsultaEm)
");
$upsertItem = $pdo->prepare("
    INSERT INTO AleNFEComprasImportacaoItens
      (ImportacaoId, NumeroItem, CodigoFornecedor, Descricao, NCM, CFOP, UnidadeMedida, Quantidade, ValorUnitario, ValorTotal)
    VALUES (:importId, :itemNo, :code, :description, :ncm, :cfop, :unit, :qty, :price, :total)
    ON DUPLICATE KEY UPDATE
      CodigoFornecedor=VALUES(CodigoFornecedor), Descricao=VALUES(Descricao), NCM=VALUES(NCM),
      CFOP=VALUES(CFOP), UnidadeMedida=VALUES(UnidadeMedida), Quantidade=VALUES(Quantidade),
      ValorUnitario=VALUES(ValorUnitario), ValorTotal=VALUES(ValorTotal)
");
$updated = 0;
$skipped = 0;
try {
    $pdo->beginTransaction();
    foreach ($notas as $note) {
        if (!is_array($note)) { $skipped++; continue; }
        $key = preg_replace('/\D+/', '', (string)pickValue($note, ['chaveAcesso','ChaveAcesso','chNFe','chave'], ''));
        if (strlen($key) !== 44) { $skipped++; continue; }
        $external = (string)pickValue($note, ['id','Id','nfeId','NFeId'], $key);
        $supplierCnpj = preg_replace('/\D+/', '', (string)pickValue($note, ['fornecedorCNPJ','FornecedorCNPJ','cnpjEmitente','emitenteCnpj'], ''));
        if ($supplierCnpj === '') $supplierCnpj = null;
        $upsert->execute([
            ':external' => $external, ':key' => $key,
            ':number' => (string)pickValue($note, ['numero','Numero','nNF'], ''),
            ':series' => pickValue($note, ['serie','Serie'], null),
            ':model' => pickValue($note, ['modelo','Modelo','mod'], null),
            ':cnpj' => $supplierCnpj,
            ':supplier' => pickValue($note, ['fornecedorNome','FornecedorNome','emitenteNome','nomeEmitente'], null),
            ':issued' => isoToMysql(pickValue($note, ['dataEmissao','DataEmissao','dhEmi'], null)),
            ':total' => (float)pickValue($note, ['valorNota','ValorNota','valorTotal','ValorTotal','vNF'], 0),
            ':situation' => (string)pickValue($note, ['situacao','Situacao','status','Status'], ''),
            ':checked' => $now
        ]);
        $stmt = $pdo->prepare('SELECT Id FROM AleNFEComprasImportacao WHERE ChaveAcesso=?');
        $stmt->execute([$key]);
        $importId = (int)$stmt->fetchColumn();
        $items = pickValue($note, ['itens','Itens','items'], []);
        if (is_array($items)) {
            foreach ($items as $i => $item) {
                if (!is_array($item)) continue;
                $upsertItem->execute([
                    ':importId' => $importId,
                    ':itemNo' => (int)pickValue($item, ['itemNumero','numeroItem','NumeroItem','nItem'], $i + 1),
                    ':code' => pickValue($item, ['codigo','Codigo','cProd','codigoFornecedor'], null),
                    ':description' => (string)pickValue($item, ['descricao','Descricao','xProd','nome'], 'Item sem descrição'),
                    ':ncm' => pickValue($item, ['ncm','NCM'], null),
                    ':cfop' => pickValue($item, ['cfop','CFOP'], null),
                    ':unit' => pickValue($item, ['unidade','Unidade','uCom'], null),
                    ':qty' => (float)pickValue($item, ['quantidade','Quantidade','qCom'], 0),
                    ':price' => (float)pickValue($item, ['valorUnitario','ValorUnitario','vUnCom'], 0),
                    ':total' => (float)pickValue($item, ['valorTotal','ValorTotal','vProd'], 0),
                ]);
            }
        }
        $updated++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    alenfeComprasJson(['success' => false, 'message' => 'Erro ao atualizar a caixa de entrada: ' . $e->getMessage()], 500);
}
alenfeComprasJson(['success' => true, 'atualizadas' => $updated, 'ignoradas' => $skipped, 'message' => "$updated nota(s) consultada(s)."]);
