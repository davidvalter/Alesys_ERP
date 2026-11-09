<?php
/* /api/emitir_nfce.php — monta o JSON no formato do ALENFE, envia e registra em VendasNFCE */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
session_start();
header('Content-Type: application/json');

set_error_handler(function ($severidade, $mensagem, $arquivo, $linha) {
    throw new ErrorException($mensagem, 0, $severidade, $arquivo, $linha);
});

/* Helper de extração de nfceId (definido aqui para não depender do nfce_helpers.php) */
if (!function_exists('extrairNfceId')) {
    function extrairNfceId(array $json): ?int
    {
        $caminhos = [
            'nfceId',
            'data.nfceId',
            'data.id',
            'id',
            'emissaoId',
            'notaId',
            'data.notaId',
            'data.emissaoId',
            'data.nfce.id',
            'nfce.id',
        ];
        foreach ($caminhos as $caminho) {
            $v = $json;
            $ok = true;
            foreach (explode('.', $caminho) as $chave) {
                if (is_array($v) && array_key_exists($chave, $v)) {
                    $v = $v[$chave];
                } else {
                    $ok = false;
                    break;
                }
            }
            if ($ok && is_numeric($v))
                return (int) $v;
        }
        $achou = null;
        $parar = false;
        $buscar = function ($arr) use (&$buscar, &$achou, &$parar) {
            if ($parar || !is_array($arr))
                return;
            foreach ($arr as $k => $v) {
                if (in_array(strtolower((string) $k), ['nfceid', 'emissaoid', 'notaid', 'nfeid', 'id'], true) && is_numeric($v)) {
                    $achou = (int) $v;
                    $parar = true;
                    return;
                }
                if (is_array($v))
                    $buscar($v);
            }
        };
        $buscar($json);
        return $achou;
    }
}

/* ---------- Função de bloqueio: interrompe a emissão com erro claro ---------- */
if (!function_exists('bloquearEmissao')) {
    function bloquearEmissao(string $mensagem, array $ctx = []): void
    {
        if (function_exists('alenfeLog')) {
            alenfeLog('emitir: BLOQUEADO - ' . $mensagem, $ctx);
        }
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $mensagem]);
        exit;
    }
}

try {
    $deps = [
        __DIR__ . '/../conf/autenticacao.php',
        __DIR__ . '/../../configs/database.php',
        __DIR__ . '/../../configs/alenfe.php',
        __DIR__ . '/nfce_helpers.php',
    ];
    foreach ($deps as $arquivo) {
        if (!is_file($arquivo))
            throw new RuntimeException("Arquivo não encontrado: {$arquivo}");
        require_once $arquivo;
    }

    if (empty($_SESSION['Usuario']['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payload inválido']);
        exit;
    }
    $pedidoId = (int) ($input['pedidoId'] ?? 0);
    if ($pedidoId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'pedidoId obrigatório']);
        exit;
    }

    $db = getDB();
    $ambiente = (int) alenfeConfig($db, 'ALENFE_AMBIENTE', '2');

    /* ============ 1. Pedido ============ */
    $stmt = $db->prepare("SELECT * FROM Pedidos WHERE Id = ?");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }

    /* ============ 2. Itens ============ */
    $stmt = $db->prepare("SELECT * FROM ItensPedido WHERE PedidoId = ?");
    $stmt->execute([$pedidoId]);
    $itensDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $itensNfce = [];
    foreach ($itensDb as $item) {
        $cardapioId = (int) ($item['CardapioId'] ?? 0);
        $qtd = (float) ($item['Quantidade'] ?? 1);
        $vUnit = (float) ($item['ValorUnitario'] ?? 0);

        $nome = '';
        $ncm = '';
        $cfop = '';
        $csosn = '';
        $cest = '';
        $un = '';

        /* ---------- Busca o item do cardápio ---------- */
        $cardapio = null;
        try {
            $st = $db->prepare("SELECT * FROM Cardapio WHERE Id = ? LIMIT 1");
            $st->execute([$cardapioId]);
            $cardapio = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { /* segue */
        }

        if (!$cardapio) {
            bloquearEmissao("Item do cardápio não encontrado (CardapioId {$cardapioId})", [
                'cardapioId' => $cardapioId,
                'pedidoId' => $pedidoId,
            ]);
        }

        $nome = (string) ($cardapio['Nome'] ?? '');
        $tipo = (string) ($cardapio['TipoItem'] ?? '');

        /* ---------- Busca os dados fiscais conforme o tipo ---------- */
        if ($tipo === 'prato' && !empty($cardapio['PratoId'])) {
            /* PRATO: busca em Pratos (campos fiscais a cadastrar) */
            try {
                $st = $db->prepare("SELECT Nome, NCM, CFOP, CSOSN, CEST, UnidadeMedida FROM Pratos WHERE Id = ? LIMIT 1");
                $st->execute([(int) $cardapio['PratoId']]);
                $fiscal = $st->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $fiscal = false;
            }
        } else {
            /* PRODUTO: busca em Produtos pela coluna CODIGO (= Cardapio.ProdutoId) */
            $prodCodigo = (int) ($cardapio['ProdutoId'] ?? 0);
            try {
                $st = $db->prepare("SELECT Nome, NCM, CFOP, CSOSN, CEST, UnidadeMedida FROM Produtos WHERE Codigo = ? LIMIT 1");
                $st->execute([$prodCodigo]);
                $fiscal = $st->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $fiscal = false;
            }
        }

        if (!$fiscal) {
            bloquearEmissao("Cadastro fiscal não encontrado para o item '{$nome}'. Verifique o cadastro do produto/prato.", [
                'cardapioId' => $cardapioId,
                'tipo' => $tipo,
                'pedidoId' => $pedidoId,
            ]);
        }

        if ($nome === '')
            $nome = (string) ($fiscal['Nome'] ?? 'PRODUTO ' . $cardapioId);
        $ncm = (string) ($fiscal['NCM'] ?? '');
        $cfop = (string) ($fiscal['CFOP'] ?? '');
        $csosn = (string) ($fiscal['CSOSN'] ?? '');
        $cest = (string) ($fiscal['CEST'] ?? '');
        $un = (string) ($fiscal['UnidadeMedida'] ?? '');

        /* ---------- BLOQUEIA se NCM/CFOP estiverem vazios (não emite com padrão errado) ---------- */
        if ($ncm === '') {
            bloquearEmissao("O item '{$nome}' está sem NCM cadastrado. Corrija o cadastro fiscal antes de emitir.", [
                'cardapioId' => $cardapioId,
                'tipo' => $tipo,
            ]);
        }
        if ($cfop === '') {
            bloquearEmissao("O item '{$nome}' está sem CFOP cadastrado. Corrija o cadastro fiscal antes de emitir.", [
                'cardapioId' => $cardapioId,
                'tipo' => $tipo,
            ]);
        }
        if ($un === '')
            $un = 'UN';

        $itemNfce = [
            'codigo' => (string) $cardapioId,
            'descricao' => mb_substr($nome, 0, 120),
            'ncm' => $ncm,
            'cfop' => $cfop,
            'unidade' => $un,
            'quantidade' => round($qtd, 4),
            'valorUnitario' => round($vUnit, 2),
            'valorTotal' => round($qtd * $vUnit, 2),
        ];

        /* Inclui CSOSN/CEST apenas se preenchidos */
        if ($csosn !== '')
            $itemNfce['csosn'] = $csosn;
        if ($cest !== '')
            $itemNfce['cest'] = $cest;

        $itensNfce[] = $itemNfce;
    }

    /* Em homologação, a SEFAZ exige a descrição padrão no PRIMEIRO item */
    if ($ambiente === 2 && $itensNfce) {
        $itensNfce[0]['descricao'] = alenfeConfig(
            $db,
            'ALENFE_DESCRICAO_HOMOLOGACAO',
            'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL'
        );
    }

    if (!$itensNfce) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Pedido sem itens para emissão']);
        exit;
    }

    /* ============ 3. Pagamentos ============ */
    $stmt = $db->prepare("SELECT * FROM Pagamentos WHERE PedidoId = ?");
    $stmt->execute([$pedidoId]);
    $pagamentosDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mapTpag = [
        'dinheiro' => '01',
        'cheque' => '02',
        'cartao_credito' => '03',
        'cartao_debito' => '04',
        'credito_loja' => '05',
        'vale_alimentacao' => '10',
        'vale' => '11',
        'vale_presente' => '12',
        'vale_combustivel' => '13',
        'duplicata' => '14',
        'boleto' => '15',
        'deposito' => '16',
        'pix' => '17',
        'transferencia' => '18',
        'fidelidade' => '19',
        'outros' => '99',
    ];

    $pagamentosNfce = [];
    foreach ($pagamentosDb as $p) {
        $forma = mb_strtolower(trim((string) ($p['FormaPagamento'] ?? '')));
        $pagamentosNfce[] = [
            'formaPagamento' => $mapTpag[$forma] ?? '99',
            'valor' => round((float) ($p['Valor'] ?? 0), 2),
        ];
    }
    if (!$pagamentosNfce) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Pedido sem pagamentos para emissão']);
        exit;
    }

    /* ============ 4. Cliente ============ */
    $cpf = preg_replace('/\D/', '', trim((string) ($pedido['Cliente'] ?? '')));
    $nomePadrao = alenfeConfig($db, 'ALENFE_CONSUMIDOR_PADRAO', 'CONSUMIDOR FINAL');
    $nomeHomologacao = alenfeConfig(
        $db,
        'ALENFE_NOME_HOMOLOGACAO',
        'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL'
    );
    $nomeDest = ($cpf !== '' && $ambiente === 2) ? $nomeHomologacao : $nomePadrao;

    $clientePayload = [
        'cpfCnpj' => $cpf !== '' ? $cpf : alenfeConfig($db, 'ALENFE_CPF_PADRAO', ''),
        'nome' => $nomeDest,
        'endereco' => [
            'logradouro' => alenfeConfig($db, 'ALENFE_ENDERECO_LOGRADOURO', 'NAO INFORMADO'),
            'numero' => alenfeConfig($db, 'ALENFE_ENDERECO_NUMERO', '0'),
            'bairro' => alenfeConfig($db, 'ALENFE_ENDERECO_BAIRRO', 'NAO INFORMADO'),
            'cidade' => alenfeConfig($db, 'ALENFE_ENDERECO_CIDADE', 'NAO INFORMADO'),
            'uf' => alenfeConfig($db, 'ALENFE_ENDERECO_UF', 'DF'),
            'cep' => alenfeConfig($db, 'ALENFE_ENDERECO_CEP', '00000000'),
        ],
    ];

    /* ============ 5. Payload + envio ============ */
    $payload = [
        'cliente' => $clientePayload,
        'itens' => array_values($itensNfce),
        'pagamentos' => array_values($pagamentosNfce),
    ];

    alenfeLog('emitir: enviando NFC-e', [
        'pedidoId' => $pedidoId,
        'itens' => count($payload['itens']),
        'total' => array_sum(array_column($payload['itens'], 'valorTotal')),
    ]);

    $res = alenfeHttp($db, 'POST', '/nfce/criar', $payload);

    /* ============ 6. Extrai nfceId (robusto) + log da resposta crua ============ */
    $nfceId = null;
    if ($res['body'] !== '') {
        $decoded = json_decode($res['body'], true);
        if (is_array($decoded)) {
            $nfceId = extrairNfceId($decoded);
        }
    }
    alenfeLog('emitir: resposta do ALENFE', [
        'status' => $res['status'],
        'nfceId' => $nfceId,
        'body' => mb_substr((string) $res['body'], 0, 2000),
    ]);

    /* ============ 7. Dados provisórios ============ */
    $total = round(array_sum(array_column($payload['itens'], 'valorTotal')), 2);
    $dadosProvisorios = [
        'chaveAcesso' => null,
        'protocolo' => null,
        'numero' => null,
        'serie' => null,
        'dataEmissao' => null,
        'ambiente' => null,
        'emitente' => null,
        'itens' => $payload['itens'],
        'totais' => ['vProd' => $total, 'vDesc' => 0, 'vNF' => $total],
        'pagamentos' => array_map(fn($p) => [
            'formaPagamento' => $p['formaPagamento'],
            'descricao' => nomeFormaPagamento($p['formaPagamento']),
            'valor' => $p['valor'],
        ], $payload['pagamentos']),
        'troco' => 0,
        'qrCode' => null,
        'urlChave' => null,
    ];

    /* ============ 8. Grava VendasNFCE ============ */
    $stmt = $db->prepare("
        INSERT INTO VendasNFCE (PedidoId, NfceId, Status, PayloadEnvio, DadosImpressao, Tentativas)
        VALUES (?, ?, 'ENVIADA', ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            NfceId = VALUES(NfceId),
            Status = 'ENVIADA',
            PayloadEnvio = VALUES(PayloadEnvio),
            DadosImpressao = VALUES(DadosImpressao),
            Tentativas = Tentativas + 1
    ");
    $stmt->execute([
        $pedidoId,
        $nfceId ?: null,
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        json_encode($dadosProvisorios, JSON_UNESCAPED_UNICODE),
    ]);

    /* ============ 9. StatusNFe (best-effort) ============ */
    try {
        $stmt = $db->prepare("UPDATE Pedidos SET StatusNFe = 'emitindo' WHERE Id = ?");
        $stmt->execute([$pedidoId]);
    } catch (Throwable $e) {
        alenfeLog('emitir: StatusNFe nao atualizado (ignorado)', ['pedidoId' => $pedidoId, 'erro' => $e->getMessage()]);
    }

    /* ============ 10. Resposta ============ */
    if ($res['error'] !== '') {
        alenfeLog('emitir: falha de conexão com o ALENFE', ['erro' => $res['error']]);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Falha ao conectar no ALENFE: ' . $res['error']]);
        exit;
    }
    if ($res['status'] >= 400) {
        alenfeLog('emitir: ALENFE respondeu erro', ['status' => $res['status'], 'body' => $res['body']]);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'ALENFE retornou erro (' . $res['status'] . '): ' . mb_substr((string) $res['body'], 0, 300)]);
        exit;
    }

    echo json_encode(['success' => true, 'pedidoId' => $pedidoId, 'nfceId' => $nfceId]);

} catch (Throwable $e) {
    if (function_exists('alenfeLog')) {
        alenfeLog('emitir: erro fatal', ['msg' => $e->getMessage(), 'arquivo' => $e->getFile(), 'linha' => $e->getLine()]);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao emitir: ' . $e->getMessage()]);
}