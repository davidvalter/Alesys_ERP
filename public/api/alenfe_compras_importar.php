<?php
require_once __DIR__ . '/alenfe_compras_helpers.php';
require_once __DIR__ . '/alenfe_compras_persistir.php';

$pdo = alenfeComprasAccess();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alenfeComprasJson(['success' => false, 'message' => 'Método não permitido.'], 405);
}
alenfeComprasVerifyCsrf();
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) alenfeComprasJson(['success' => false, 'message' => 'Corpo JSON inválido.'], 400);

$importId = filter_var($input['importacaoId'] ?? null, FILTER_VALIDATE_INT);
$marketId = filter_var($input['mercadoCodigo'] ?? null, FILTER_VALIDATE_INT);
$mapping = $input['itens'] ?? [];
if (!$importId || !$marketId || !is_array($mapping)) {
    alenfeComprasJson(['success' => false, 'message' => 'Selecione o fornecedor e vincule todos os itens a produtos cadastrados.'], 400);
}

try {
    $nfeId = alenfeComprasPersistirImportacao($pdo, (int)$importId, (int)$marketId, $mapping);
    alenfeComprasJson([
        'success' => true,
        'notaId' => $nfeId,
        'message' => 'Nota importada. O estoque não foi alterado.'
    ]);
} catch (Throwable $e) {
    alenfeComprasJson(['success' => false, 'message' => $e->getMessage()], 422);
}
