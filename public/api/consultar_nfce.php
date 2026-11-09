<?php
/* /public/api/consultar_nfce.php
 * Reconsulta o estado da nota junto ao ALENFE (contingência/erro).
 * Endpoint interno: não exige sessão (a página do PDV já é autenticada). */
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
        if (!is_file($arquivo)) throw new RuntimeException("Arquivo não encontrado: {$arquivo}");
        require_once $arquivo;
    }

    $db = getDB();

    $input    = json_decode(file_get_contents('php://input'), true);
    $pedidoId = (int) ($input['pedidoId'] ?? 0);
    if ($pedidoId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'pedidoId obrigatório']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM VendasNFCE WHERE PedidoId = ? ORDER BY Id DESC LIMIT 1");
    $stmt->execute([$pedidoId]);
    $nfce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nfce) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Nenhuma emissão encontrada para este pedido']);
        exit;
    }

    $nfceId = (int) $nfce['NfceId'];
    if ($nfceId <= 0) {
        /* Ainda sem número no ALENFE: devolve o estado atual (não é erro fatal) */
        echo json_encode([
            'success'        => true,
            'status'         => $nfce['Status'],
            'message'        => 'A nota ainda está em processamento no ALENFE. O status será atualizado automaticamente quando o webhook chegar.',
            'dados'          => json_decode($nfce['DadosImpressao'] ?? 'null', true),
            'motivoRejeicao' => $nfce['MotivoRejeicao'],
        ]);
        exit;
    }

    /* Consulta a situação no ALENFE (rota ajustável conforme a API real) */
    $res = alenfeHttp($db, 'GET', '/nfce/' . $nfceId);
    if ($res['error'] !== '') {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Falha ao consultar o ALENFE: ' . $res['error']]);
        exit;
    }

    $ret = json_decode($res['body'], true);
    if (!is_array($ret)) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Resposta inesperada do ALENFE (' . $res['status'] . ')']);
        exit;
    }

    /* ---------- Normaliza para o mesmo formato do webhook ---------- */
    $webhookInput = [
        'evento'           => $ret['evento'] ?? $ret['status'] ?? '',
        'nfceId'           => $ret['nfceId'] ?? $nfceId,
        'chaveAcesso'      => $ret['chaveAcesso'] ?? $nfce['ChaveAcesso'],
        'protocolo'        => $ret['protocolo'] ?? $nfce['Protocolo'],
        'motivoRejeicao'   => $ret['motivoRejeicao'] ?? $ret['motivo'] ?? null,
        'xmlProc'          => $ret['xmlProc'] ?? null,
        'xmlContingencia'  => $ret['xmlContingencia'] ?? null,
        'dataAutorizacao'  => $ret['dataAutorizacao'] ?? null,
        'dataContingencia' => $ret['dataContingencia'] ?? null,
    ];

    $evt = mb_strtoupper((string) $webhookInput['evento']);
    $status = 'ERRO';
    if (str_contains($evt, 'AUTORIZADA'))       $status = 'AUTORIZADA';
    elseif (str_contains($evt, 'CONTINGENCIA')) $status = 'CONTINGENCIA';
    elseif (str_contains($evt, 'REJEITADA'))    $status = 'REJEITADA';
    elseif (str_contains($evt, 'CANCELADA'))    $status = 'CANCELADA';
    elseif (str_contains($evt, 'ERRO'))         $status = 'ERRO';

    $dados = null;
    $xmlProc = $webhookInput['xmlProc'];
    $xmlCont = $webhookInput['xmlContingencia'];
    if ($status === 'AUTORIZADA' && is_string($xmlProc) && $xmlProc !== '') {
        $dados = extrairDadosXmlNfce($xmlProc);
    } elseif ($status === 'CONTINGENCIA' && is_string($xmlCont) && $xmlCont !== '') {
        $dados = extrairDadosXmlNfce($xmlCont);
    }

    if ($dados !== null) {
        if (empty($dados['chaveAcesso'])) $dados['chaveAcesso'] = $webhookInput['chaveAcesso'];
        if (empty($dados['protocolo']))   $dados['protocolo']   = $webhookInput['protocolo'];

        $stmt = $db->prepare("
            UPDATE VendasNFCE SET
                Status = ?, ChaveAcesso = ?, Protocolo = ?, MotivoRejeicao = ?,
                XmlProc = ?, XmlContingencia = ?, DadosImpressao = ?
            WHERE Id = ?
        ");
        $stmt->execute([
            $status,
            $webhookInput['chaveAcesso'],
            $webhookInput['protocolo'],
            is_string($webhookInput['motivoRejeicao']) ? $webhookInput['motivoRejeicao'] : null,
            is_string($xmlProc) ? $xmlProc : null,
            is_string($xmlCont) ? $xmlCont : null,
            json_encode($dados, JSON_UNESCAPED_UNICODE),
            $nfce['Id'],
        ]);

        /* StatusNFe do Pedido (best-effort) */
        try {
            $statusPedido = match ($status) {
                'AUTORIZADA', 'CONTINGENCIA' => 'emitida',
                'REJEITADA', 'CANCELADA', 'ERRO' => 'erro',
                default => 'pendente',
            };
            $stmt = $db->prepare("UPDATE Pedidos SET StatusNFe = ? WHERE Id = ?");
            $stmt->execute([$statusPedido, $pedidoId]);
        } catch (Throwable $e) {
            alenfeLog('consultar: StatusNFe nao atualizado (ignorado)', ['erro' => $e->getMessage()]);
        }

        alenfeLog('consultar: nota atualizada', ['pedidoId' => $pedidoId, 'status' => $status]);
    } else {
        alenfeLog('consultar: retorno sem XML', ['pedidoId' => $pedidoId, 'body' => $res['body']]);
    }

    echo json_encode([
        'success'        => true,
        'status'         => $status,
        'dados'          => $dados ?? json_decode($nfce['DadosImpressao'] ?? 'null', true),
        'motivoRejeicao' => $webhookInput['motivoRejeicao'] ?? $nfce['MotivoRejeicao'],
    ]);

} catch (Throwable $e) {
    if (function_exists('alenfeLog')) {
        alenfeLog('consultar: erro fatal', ['msg' => $e->getMessage(), 'arquivo' => $e->getFile(), 'linha' => $e->getLine()]);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao consultar: ' . $e->getMessage()]);
}