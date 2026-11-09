<?php
/* /public/api/inc/nfce_helpers.php — funções compartilhadas da integração ALENFE
 *
 * Observação: usa `match` (PHP 8.0+) — o Alesys roda PHP 8.3, ok.
 * Idealmente o /configs/alenfe.php é carregado antes deste arquivo, mas as
 * funções têm fallback embutido via defined(), então não quebram sem ele.
 */

/* ---------- Lê uma config do banco com fallback ---------- */
function alenfeConfig($db, string $chave, string $default): string
{
    try {
        $stmt = $db->prepare("SELECT Valor FROM Configuracoes WHERE Chave = ? LIMIT 1");
        $stmt->execute([$chave]);
        $v = $stmt->fetchColumn();
        return ($v === false || $v === null || $v === '') ? $default : (string) $v;
    } catch (Throwable $e) {
        return $default;
    }
}

function alenfeUrlBase($db): string
{
    $fallback = defined('ALENFE_URL_FALLBACK') ? ALENFE_URL_FALLBACK : 'http://localhost:3001';
    return rtrim(alenfeConfig($db, 'ALENFE_URL', $fallback), '/');
}

function alenfeApiKey($db): string
{
    $fallback = defined('ALENFE_API_KEY_FALLBACK') ? ALENFE_API_KEY_FALLBACK : '';
    return alenfeConfig($db, 'ALENFE_API_KEY', $fallback);
}

function alenfeWebhookToken($db): string
{
    $fallback = defined('ALENFE_WEBHOOK_TOKEN_FALLBACK') ? ALENFE_WEBHOOK_TOKEN_FALLBACK : '';
    return alenfeConfig($db, 'ALENFE_WEBHOOK_TOKEN', $fallback);
}

/* ---------- HTTP (cURL) ---------- */
function alenfeHttp($db, string $method, string $path, ?array $body = null): array
{
    $conTimeout = defined('ALENFE_TIMEOUT_CONEXAO') ? ALENFE_TIMEOUT_CONEXAO : 5;
    $totTimeout = defined('ALENFE_TIMEOUT_TOTAL') ? ALENFE_TIMEOUT_TOTAL : 25;

    $ch = curl_init(alenfeUrlBase($db) . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $conTimeout,
        CURLOPT_TIMEOUT        => $totTimeout,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . alenfeApiKey($db),
        ],
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
    } else {
        $opts[CURLOPT_HTTPGET] = true;
    }
    curl_setopt_array($ch, $opts);

    $res    = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    return ['status' => $status, 'body' => $res, 'error' => $error];
}

/* ---------- Log ---------- */
function alenfeLog(string $msg, array $ctx = []): void
{
    $arquivo = defined('ALENFE_LOG_PATH') ? ALENFE_LOG_PATH : (__DIR__ . '/../../../logs/alenfe.log');
    $dir = dirname($arquivo);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg
           . ($ctx ? ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE) : '') . PHP_EOL;
    @file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);
}

/* ---------- Nome da forma de pagamento pelo código (tPag) ---------- */
function nomeFormaPagamento(string $tPag): string
{
    return match ($tPag) {
        '01' => 'Dinheiro',
        '02' => 'Cheque',
        '03' => 'Cartão de Crédito',
        '04' => 'Cartão de Débito',
        '05' => 'Crédito Loja',
        '10' => 'Vale Alimentação',
        '11' => 'Vale Refeição',
        '12' => 'Vale Presente',
        '13' => 'Vale Combustível',
        '14' => 'Duplicata Mercantil',
        '15' => 'Boleto Bancário',
        '16' => 'Depósito Bancário',
        '17' => 'PIX',
        '18' => 'Transferência Bancária',
        '19' => 'Programa de Fidelidade',
        '90' => 'Sem Pagamento',
        default => 'Outros (' . $tPag . ')',
    };
}

/* ---------- Dados da empresa (emitente) para o cupom ---------- */
function alenfeEmpresa($db): array
{
    try {
        $stmt = $db->query("SELECT * FROM Empresa WHERE Ativo = 1 ORDER BY Id LIMIT 1");
        $e = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($e) {
            return $e;
        }
    } catch (Throwable $e) { /* tabela ainda não existe */ }
    return [
        'RazaoSocial' => '', 'NomeFantasia' => '', 'Cnpj' => '',
        'InscricaoEstadual' => '', 'Logradouro' => '', 'Numero' => '',
        'Bairro' => '', 'Cidade' => '', 'Uf' => '', 'Cep' => '',
    ];
}

/* ---------- Extrai do XML (xmlProc/xmlContingencia) os dados do cupom ---------- */
function extrairDadosXmlNfce(string $xml): ?array
{
    // Remove declarações XML aninhadas (ex.: SOAP dentro do xmlProc) antes do parse
    $xml = preg_replace('/<\?xml[^>]*\?>/i', '', $xml);
    $xml = trim($xml);

    $prev = libxml_use_internal_errors(true);
    $dom  = new DOMDocument();
    $ok   = $dom->loadXML($xml, LIBXML_NONET);

    // Fallback: se o payload chegou com o XML "escapado" (&lt; &gt; &quot;), decodifica e tenta de novo
    if (!$ok && str_contains($xml, '&lt;')) {
        $xml = html_entity_decode($xml, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $xml = preg_replace('/<\?xml[^>]*\?>/i', '', $xml);
        $ok  = $dom->loadXML(trim($xml), LIBXML_NONET);
    }

    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if (!$ok) {
        return null;
    }

    $xp = new DOMXPath($dom);
    $xp->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

    $val = function (string $query) use ($xp): ?string {
        $res = @$xp->query($query);
        if (!$res) {
            return null;
        }
        $node = $res->item(0);
        return $node ? trim($node->textContent) : null;
    };

    $chave = $val('//nfe:infNFe/@Id');
    if ($chave) {
        $chave = preg_replace('/^NFe/', '', $chave);
    }

    $vProd = (float) ($val('//nfe:total/nfe:ICMSTot/nfe:vProd') ?? 0);
    $vNF   = (float) ($val('//nfe:total/nfe:ICMSTot/nfe:vNF') ?? 0);

    $dados = [
        'chaveAcesso' => $chave ?: null,
        'numero'      => $val('//nfe:ide/nfe:nNF'),
        'serie'       => $val('//nfe:ide/nfe:serie'),
        'dataEmissao' => $val('//nfe:ide/nfe:dhEmi'),
        'ambiente'    => $val('//nfe:ide/nfe:tpAmb'),        // 1 produção, 2 homologação
        'protocolo'   => $val('//nfe:protNFe/nfe:infProt/nfe:nProt'),
        'emitente'    => [
            'cnpj'         => $val('//nfe:emit/nfe:CNPJ'),
            'razaoSocial'  => $val('//nfe:emit/nfe:xNome'),
            'nomeFantasia' => $val('//nfe:emit/nfe:xFant'),
            'ie'           => $val('//nfe:emit/nfe:IE'),
            'logradouro'   => $val('//nfe:emit/nfe:enderEmit/nfe:xLgr'),
            'numero'       => $val('//nfe:emit/nfe:enderEmit/nfe:nro'),
            'bairro'       => $val('//nfe:emit/nfe:enderEmit/nfe:xBairro'),
            'cidade'       => $val('//nfe:emit/nfe:enderEmit/nfe:xMun'),
            'uf'           => $val('//nfe:emit/nfe:enderEmit/nfe:UF'),
            'cep'          => $val('//nfe:emit/nfe:enderEmit/nfe:CEP'),
        ],
        'itens' => [],
        'totais' => [
            'vProd' => $vProd,
            'vDesc' => (float) ($val('//nfe:total/nfe:ICMSTot/nfe:vDesc') ?? 0),
            'vNF'   => $vNF > 0 ? $vNF : $vProd,  // garante total mesmo se vNF vier zerado
        ],
        'pagamentos' => [],
        'troco'      => (float) ($val('//nfe:pag/nfe:vTroco') ?? 0),
        'qrCode'     => $val('//nfe:infNFeSupl/nfe:qrCode'),
        'urlChave'   => $val('//nfe:infNFeSupl/nfe:urlChave'),
    ];

    $numericos = ['quantidade', 'valorUnitario', 'valorTotal'];

    foreach ($xp->query('//nfe:det') as $det) {
        $item = [];
        $mapa = [
            'cProd' => 'codigo', 'xProd' => 'descricao', 'NCM' => 'ncm',
            'CFOP' => 'cfop', 'uCom' => 'unidade',
            'qCom' => 'quantidade', 'vUnCom' => 'valorUnitario', 'vProd' => 'valorTotal',
        ];
        foreach ($mapa as $tag => $campo) {
            $res  = @$xp->query(".//nfe:prod/nfe:{$tag}", $det);
            $node = $res ? $res->item(0) : null;
            if (!$node) {
                $item[$campo] = null;
                continue;
            }
            $item[$campo] = in_array($campo, $numericos, true)
                ? (float) $node->textContent
                : trim($node->textContent);
        }
        $dados['itens'][] = $item;
    }

    foreach ($xp->query('//nfe:pag/nfe:detPag') as $det) {
        $tPagRes = @$xp->query('.//nfe:tPag', $det);
        $vPagRes = @$xp->query('.//nfe:vPag', $det);
        $tPagNode = $tPagRes ? $tPagRes->item(0) : null;
        $vPagNode = $vPagRes ? $vPagRes->item(0) : null;
        $tPag = $tPagNode ? trim($tPagNode->textContent) : '';
        $dados['pagamentos'][] = [
            'formaPagamento' => $tPag,
            'descricao'      => nomeFormaPagamento($tPag),
            'valor'          => (float) ($vPagNode ? $vPagNode->textContent : 0),
        ];
    }

    return $dados;
}