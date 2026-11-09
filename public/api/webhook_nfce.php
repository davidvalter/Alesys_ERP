<?php
/* /public/api/webhook_nfce.php
 * Chamado pelo ALENFE quando a nota é processada.
 * NÃO exige sessão do Alesys — autenticação por token (x-webhook-token).
 * Retorna sempre 200 para o ALENFE não reenviar.
 *
 * Eventos: NFC_E_AUTORIZADA | NFC_E_REJEITADA | NFC_E_CONTINGENCIA
 *          | NFC_E_CANCELADA | NFC_E_ERRO_CERTIFICADO | NFC_E_ERRO
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json');

set_error_handler(function ($severidade, $mensagem, $arquivo, $linha) {
    throw new ErrorException($mensagem, 0, $severidade, $arquivo, $linha);
});

try {
    $deps = [
        __DIR__ . '/../../configs/database.php',
        __DIR__ . '/../../configs/alenfe.php',
        __DIR__ . '/nfce_helpers.php',
    ];
    foreach ($deps as $arquivo) {
        if (!is_file($arquivo))
            throw new RuntimeException("Arquivo não encontrado: {$arquivo}");
        require_once $arquivo;
    }

    $db = getDB();
    $rawBody = file_get_contents('php://input');

    /* ---------- Valida token ---------- */
    $token = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? ($_GET['token'] ?? '');
    $esperado = alenfeWebhookToken($db);
    if ($esperado === '' || !is_string($token) || !hash_equals($esperado, $token)) {
        alenfeLog('webhook: token inválido', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }

    /* ---------- Valida assinatura HMAC (x-webhook-signature) ----------
     * O ALENFE assina JSON.stringify(payload) com HMAC-SHA256 usando o MESMO
     * secret. Comparação contra o RAW body. Tolerância a base64 além de hex. */
    $assinatura = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
    if ($assinatura !== '') {
        $hmacHex = hash_hmac('sha256', $rawBody, $esperado);
        $hmacB64 = base64_encode(hash_hmac('sha256', $rawBody, $esperado, true));
        if (!hash_equals($hmacHex, $assinatura) && !hash_equals($hmacB64, $assinatura)) {
            alenfeLog('webhook: assinatura HMAC inválida', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'esperado' => $hmacHex,
                'recebido' => $assinatura,
            ]);
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Assinatura inválida']);
            exit;
        }
        alenfeLog('webhook: assinatura HMAC OK', []);
    } else {
        alenfeLog('webhook: assinatura ausente (processado sem verificação de integridade)', []);
    }

    /* ---------- Parse do body ---------- */
    $input = json_decode($rawBody, true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payload inválido']);
        exit;
    }

    $evento = (string) ($input['evento'] ?? '');
    $nfceId = (int) ($input['nfceId'] ?? 0);
    $chave = (string) ($input['chaveAcesso'] ?? '');
    $protocolo = (string) ($input['protocolo'] ?? '');
    $motivo = $input['motivoRejeicao'] ?? ($input['motivo'] ?? null);
    $xmlProc = $input['xmlProc'] ?? null;
    $xmlCont = $input['xmlContingencia'] ?? null;
    $dataAutor = $input['dataAutorizacao'] ?? null;
    $dataCont = $input['dataContingencia'] ?? null;

    /* ---------- Evento → status ---------- */
    $evt = mb_strtoupper($evento);
    $status = 'ERRO';
    if (str_contains($evt, 'AUTORIZADA')) {
        $status = 'AUTORIZADA';
    } elseif (str_contains($evt, 'CONTINGENCIA')) {
        $status = 'CONTINGENCIA';
    } elseif (str_contains($evt, 'REJEITADA')) {
        $status = 'REJEITADA';
    } elseif (str_contains($evt, 'CANCELADA')) {
        $status = 'CANCELADA';
    } elseif (str_contains($evt, 'ERRO')) {
        $status = 'ERRO'; /* cobre NFC_E_ERRO_CERTIFICADO e NFC_E_ERRO */
    }

    /* ---------- Extrai dados do XML ---------- */
    $dados = null;
    if ($status === 'AUTORIZADA' && is_string($xmlProc) && $xmlProc !== '') {
        $dados = extrairDadosXmlNfce($xmlProc);
    } elseif ($status === 'CONTINGENCIA' && is_string($xmlCont) && $xmlCont !== '') {
        $dados = extrairDadosXmlNfce($xmlCont);
    }

    if ($dados === null) {
        $dados = [
            'chaveAcesso' => $chave,
            'protocolo' => $protocolo,
            'numero' => null,
            'serie' => null,
            'dataEmissao' => null,
            'ambiente' => null,
            'emitente' => null,
            'itens' => [],
            'totais' => ['vProd' => 0, 'vDesc' => 0, 'vNF' => 0],
            'pagamentos' => [],
            'troco' => 0,
            'qrCode' => null,
            'urlChave' => null,
        ];
    }
    if ($chave !== '')
        $dados['chaveAcesso'] = $chave;
    if ($protocolo !== '')
        $dados['protocolo'] = $protocolo;

    /* ---------- Localiza registro (NfceId ou chave) ---------- */
    $stmt = $db->prepare("SELECT Id, PedidoId FROM VendasNFCE WHERE NfceId = ? OR (ChaveAcesso <> '' AND ChaveAcesso = ?) LIMIT 1");
    $stmt->execute([$nfceId, $chave]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);

    /* Fallback: se o NfceId não foi capturado na emissão, casa pela emissão
     * pendente mais recente. Seguro para PDV único — o webhook chega ~1s após
     * o envio. */
    if (!$reg) {
        $stmt = $db->prepare("
            SELECT Id, PedidoId FROM VendasNFCE
            WHERE Status IN ('ENVIADA','PENDENTE')
              AND CriadoEm >= NOW() - INTERVAL 15 MINUTE
            ORDER BY Id DESC LIMIT 1
        ");
        $stmt->execute();
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reg) {
            alenfeLog('webhook: registro casado por fallback (nfceId ausente na emissão)', [
                'pedidoId' => $reg['PedidoId'],
                'nfceId' => $nfceId,
                'chave' => $chave,
            ]);
        }
    }

    if (!$reg) {
        alenfeLog('webhook: sem registro correspondente', ['evento' => $evento, 'nfceId' => $nfceId, 'chave' => $chave]);
        echo json_encode(['success' => true, 'matched' => false, 'status' => $status]);
        exit;
    }

    /* ---------- Atualiza VendasNFCE ---------- */
    $stmt = $db->prepare("
        UPDATE VendasNFCE SET
            NfceId = ?, ChaveAcesso = ?, Protocolo = ?, Status = ?,
            MotivoRejeicao = ?, XmlProc = ?, XmlContingencia = ?,
            DadosImpressao = ?
        WHERE Id = ?
    ");
    $stmt->execute([
        $nfceId,
        $chave,
        $protocolo,
        $status,
        is_string($motivo) ? $motivo : null,
        is_string($xmlProc) ? $xmlProc : null,
        is_string($xmlCont) ? $xmlCont : null,
        json_encode($dados, JSON_UNESCAPED_UNICODE),
        $reg['Id'],
    ]);

    /* ---------- StatusNFe do Pedido (best-effort) ---------- */
    try {
        $statusPedido = match ($status) {
            'AUTORIZADA', 'CONTINGENCIA' => 'emitida',
            'REJEITADA', 'CANCELADA', 'ERRO' => 'erro',
            default => 'pendente',
        };
        $stmt = $db->prepare("UPDATE Pedidos SET StatusNFe = ? WHERE Id = ?");
        $stmt->execute([$statusPedido, $reg['PedidoId']]);
    } catch (Throwable $e) {
        alenfeLog('webhook: StatusNFe nao atualizado (ignorado)', ['erro' => $e->getMessage()]);
    }

    alenfeLog('webhook: processado', ['evento' => $evento, 'status' => $status, 'nfceId' => $nfceId, 'chave' => $chave]);
    echo json_encode(['success' => true, 'matched' => true, 'status' => $status]);

} catch (Throwable $e) {
    if (function_exists('alenfeLog')) {
        alenfeLog('webhook: erro fatal', ['msg' => $e->getMessage(), 'arquivo' => $e->getFile(), 'linha' => $e->getLine()]);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar webhook: ' . $e->getMessage()]);
}