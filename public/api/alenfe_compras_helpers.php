<?php
// Helpers da importação manual de notas de compra do AleNFE.
require_once __DIR__ . '/../../configs/database.php';
$alenfePrivateConfig = __DIR__ . '/../../configs/alenfe.php';
if (is_file($alenfePrivateConfig)) {
    require_once $alenfePrivateConfig;
} else {
    require_once __DIR__ . '/../../configs/alenfe-example.php';
}
require_once __DIR__ . '/nfce_helpers.php';
require_once __DIR__ . '/../conf/autenticacao.php';

function alenfeComprasJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function alenfeComprasAccess(): PDO
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['Usuario']['id']) || empty($_SESSION['Usuario']['logado'])) {
        alenfeComprasJson(['success' => false, 'message' => 'Sessão expirada. Entre novamente.'], 401);
    }
    try {
        verificarPermissao('compras.notasfiscais');
    } catch (Throwable $e) {
        alenfeComprasJson(['success' => false, 'message' => 'Acesso não autorizado.'], 403);
    }
    return getDB();
}

function alenfeComprasVerifyCsrf(): void
{
    $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = (string)($_SESSION['alenfe_compras_csrf'] ?? '');
    if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
        alenfeComprasJson(['success' => false, 'message' => 'Sessão de segurança inválida. Atualize a página e tente novamente.'], 403);
    }
}

function alenfeComprasConfig(PDO $pdo, string $key, string $fallback = ''): string
{
    $stmt = $pdo->prepare('SELECT Valor FROM Configuracoes WHERE Chave = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return ($value === false || $value === null || trim((string)$value) === '') ? $fallback : trim((string)$value);
}

function alenfeComprasListPath(PDO $pdo): string
{
    $path = alenfeComprasConfig($pdo, 'ALENFE_COMPRAS_LIST_PATH', '');
    if ($path !== '') {
        return str_starts_with($path, '/') ? $path : '/' . $path;
    }
    return '/api/integracoes/alesys/notas-compras?page=1&limit=100';
}

function alenfeComprasExtractXmlPayload(array $response): string
{
    $body = (string)($response['body'] ?? '');
    $decoded = json_decode($body, true);
    if (is_array($decoded)) {
        foreach (['xml', 'xmlProc', 'xmlOriginal', 'conteudo', 'data'] as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                $body = $decoded[$key];
                break;
            }
        }
    }
    if (str_contains($body, '&lt;')) {
        $body = html_entity_decode($body, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    return trim($body);
}

function alenfeComprasParseXml(string $xml): ?array
{
    if ($xml === '' || !class_exists(DOMDocument::class)) {
        return null;
    }
    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$ok) {
        return null;
    }
    $xpath = new DOMXPath($dom);
    $value = static function (string $path) use ($xpath): ?string {
        $node = $xpath->query($path)->item(0);
        $text = $node ? trim((string)$node->textContent) : '';
        return $text !== '' ? $text : null;
    };

    $tpNF = $value('//*[local-name()="ide"]/*[local-name()="tpNF"]');
    $natureza = $value('//*[local-name()="ide"]/*[local-name()="natOp"]');
    $emitCnpj = $value('//*[local-name()="emit"]/*[local-name()="CNPJ"]');
    $emitNome = $value('//*[local-name()="emit"]/*[local-name()="xNome"]');
    $emitFantasia = $value('//*[local-name()="emit"]/*[local-name()="xFant"]');
    $inscricaoEstadual = $value('//*[local-name()="emit"]/*[local-name()="IE"]');
    $logradouro = $value('//*[local-name()="enderEmit"]/*[local-name()="xLgr"]');
    $numero = $value('//*[local-name()="enderEmit"]/*[local-name()="nro"]');
    $bairro = $value('//*[local-name()="enderEmit"]/*[local-name()="xBairro"]');
    $cep = $value('//*[local-name()="enderEmit"]/*[local-name()="CEP"]');
    $municipio = $value('//*[local-name()="enderEmit"]/*[local-name()="xMun"]');
    $estado = $value('//*[local-name()="enderEmit"]/*[local-name()="UF"]');
    $telefone = $value('//*[local-name()="enderEmit"]/*[local-name()="fone"]');

    if ($tpNF === null || !in_array($tpNF, ['0', '1'], true) || $natureza === null) {
        return null;
    }

    $enderecoPartes = array_filter([$logradouro, $numero, $bairro]);
    return [
        'tipo' => (int)$tpNF,
        'natureza' => $natureza,
        'emitCnpj' => $emitCnpj ? preg_replace('/\D+/', '', $emitCnpj) : null,
        'emitNome' => $emitNome,
        'emitFantasia' => $emitFantasia,
        'inscricaoEstadual' => $inscricaoEstadual,
        'endereco' => $enderecoPartes ? implode(', ', $enderecoPartes) : null,
        'cep' => $cep ? preg_replace('/\D+/', '', $cep) : null,
        'municipio' => $municipio,
        'estado' => $estado,
        'telefone' => $telefone ? preg_replace('/\D+/', '', $telefone) : null,
    ];
}

function alenfeComprasApiError(array $response, string $fallback): string
{
    if ((int)($response['status'] ?? 0) === 401 || (int)($response['status'] ?? 0) === 403) {
        return 'AleNFE recusou a chave ou a rota ainda não está habilitada para autenticação por API Key. ' . $fallback;
    }
    if (!empty($response['error'])) {
        return 'Falha de conexão com AleNFE: ' . $response['error'];
    }
    return $fallback . ' (HTTP ' . (int)($response['status'] ?? 0) . ').';
}
