<?php
declare(strict_types=1);

/**
 * Resumo de dados reais para o dashboard inicial.
 * Os períodos usam a data/hora local configurada no servidor.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

$usuario = $_SESSION['Usuario'] ?? [];
if (empty($usuario['id']) || empty($usuario['logado'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sua sessão expirou. Entre novamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$permissoes = $usuario['permissoes'] ?? [];
$isAdmin = ($usuario['perfil_nome'] ?? '') === 'admin';
if (!$isAdmin && (!is_array($permissoes) || !in_array('dashboard', $permissoes, true))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para consultar o dashboard.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $periodo = $_GET['periodo'] ?? '7d';
    if (!in_array($periodo, ['hoje', '7d', 'mes'], true)) {
        $periodo = '7d';
    }

    $fuso = new DateTimeZone(date_default_timezone_get());
    $hoje = new DateTimeImmutable('today', $fuso);
    $amanha = $hoje->modify('+1 day');

    if ($periodo === 'hoje') {
        $inicio = $hoje;
        $fim = $amanha;
        $inicioAnterior = $hoje->modify('-1 day');
        $fimAnterior = $hoje;
        $rotuloPeriodo = 'Hoje';
        $rotuloComparacao = 'em relação a ontem';
    } elseif ($periodo === 'mes') {
        $inicio = $hoje->modify('first day of this month')->setTime(0, 0);
        $fim = $inicio->modify('+1 month');
        $inicioAnterior = $inicio->modify('-1 month');
        $ultimoDiaAnterior = (int) $inicioAnterior->modify('last day of this month')->format('j');
        $diasComparaveis = min((int) $hoje->format('j'), $ultimoDiaAnterior);
        $fimAnterior = $inicioAnterior->modify('+' . $diasComparaveis . ' days');
        $rotuloPeriodo = 'Este mês';
        $rotuloComparacao = 'em relação ao mesmo período do mês anterior';
    } else {
        $inicio = $hoje->modify('-6 days');
        $fim = $amanha;
        $inicioAnterior = $inicio->modify('-7 days');
        $fimAnterior = $inicio;
        $rotuloPeriodo = 'Últimos 7 dias';
        $rotuloComparacao = 'em relação aos 7 dias anteriores';
    }

    $db = getDB();
    $resumoPeriodo = function (DateTimeImmutable $de, DateTimeImmutable $ate) use ($db): array {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS pedidos,
                   COALESCE(SUM(ValorTotal), 0) AS vendas
            FROM Pedidos
            WHERE Status = 'fechado'
              AND DataFechamento >= :inicio
              AND DataFechamento < :fim
        ");
        $stmt->execute([
            ':inicio' => $de->format('Y-m-d H:i:s'),
            ':fim' => $ate->format('Y-m-d H:i:s'),
        ]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'pedidos' => (int) ($linha['pedidos'] ?? 0),
            'vendas' => (float) ($linha['vendas'] ?? 0),
        ];
    };

    $atual = $resumoPeriodo($inicio, $fim);
    $anterior = $resumoPeriodo($inicioAnterior, $fimAnterior);

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM Pedidos
        WHERE Status = 'aberto'
    ");
    $stmt->execute();
    $pedidosAbertos = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM ItensPedido i
        INNER JOIN Pedidos p ON p.Id = i.PedidoId
        WHERE i.Status = 'preparando'
          AND p.Status = 'aberto'
    ");
    $stmt->execute();
    $itensEmPreparo = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM VendasNFCE
        WHERE Status IN ('PENDENTE', 'ENVIADA', 'CONTINGENCIA', 'REJEITADA', 'ERRO')
    ");
    $stmt->execute();
    $nfcesAcompanhar = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM FinanceiroImportacaoOfxLancamento
        WHERE Status IN ('pendente', 'erro')
    ");
    $stmt->execute();
    $ofxPendentes = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM FinanceiroLancamentos
        WHERE Status = 'pendente'
          AND COALESCE(DataPrevista, DATE(DataLancamento)) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $lancamentosVencendo = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM FechamentosCaixa
        WHERE Status = 'aberto'
    ");
    $stmt->execute();
    $caixasAbertos = (int) $stmt->fetchColumn();

    if ($periodo === 'hoje') {
        $stmt = $db->prepare("
            SELECT HOUR(DataFechamento) AS periodo, COUNT(*) AS pedidos,
                   COALESCE(SUM(ValorTotal), 0) AS vendas
            FROM Pedidos
            WHERE Status = 'fechado'
              AND DataFechamento >= :inicio
              AND DataFechamento < :fim
            GROUP BY HOUR(DataFechamento)
            ORDER BY periodo
        ");
    } else {
        $stmt = $db->prepare("
            SELECT DATE(DataFechamento) AS periodo, COUNT(*) AS pedidos,
                   COALESCE(SUM(ValorTotal), 0) AS vendas
            FROM Pedidos
            WHERE Status = 'fechado'
              AND DataFechamento >= :inicio
              AND DataFechamento < :fim
            GROUP BY DATE(DataFechamento)
            ORDER BY periodo
        ");
    }
    $stmt->execute([
        ':inicio' => $inicio->format('Y-m-d H:i:s'),
        ':fim' => $fim->format('Y-m-d H:i:s'),
    ]);
    $linhasTendencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $porPeriodo = [];
    foreach ($linhasTendencia as $linha) {
        $chave = $periodo === 'hoje'
            ? (string) (int) $linha['periodo']
            : (string) $linha['periodo'];
        $porPeriodo[$chave] = [
            'vendas' => (float) $linha['vendas'],
            'pedidos' => (int) $linha['pedidos'],
        ];
    }

    $tendencia = [];
    if ($periodo === 'hoje') {
        for ($hora = 0; $hora < 24; $hora++) {
            $item = $porPeriodo[(string) $hora] ?? ['vendas' => 0.0, 'pedidos' => 0];
            $tendencia[] = [
                'label' => str_pad((string) $hora, 2, '0', STR_PAD_LEFT) . ':00',
                'vendas' => $item['vendas'],
                'pedidos' => $item['pedidos'],
            ];
        }
    } else {
        for ($dia = $inicio; $dia < $fim; $dia = $dia->modify('+1 day')) {
            $chave = $dia->format('Y-m-d');
            $item = $porPeriodo[$chave] ?? ['vendas' => 0.0, 'pedidos' => 0];
            $tendencia[] = [
                'label' => $dia->format('d/m'),
                'vendas' => $item['vendas'],
                'pedidos' => $item['pedidos'],
            ];
        }
    }

    $stmt = $db->prepare("
        SELECT Id, Cliente, Tipo, Mesa, DataAbertura, ValorTotal
        FROM Pedidos
        WHERE Status = 'aberto'
        ORDER BY DataAbertura DESC
        LIMIT 8
    ");
    $stmt->execute();
    $pedidosRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $variacao = static function (float $valorAtual, float $valorAnterior): ?float {
        if ($valorAnterior == 0.0) {
            return null;
        }
        return round((($valorAtual - $valorAnterior) / abs($valorAnterior)) * 100, 1);
    };

    $alertas = [];
    $adicionarAlerta = static function (int $quantidade, string $titulo, string $descricao, string $url, string $icone, string $tipo = 'info') use (&$alertas): void {
        if ($quantidade < 1) {
            return;
        }
        $alertas[] = [
            'quantidade' => $quantidade,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'url' => $url,
            'icone' => $icone,
            'tipo' => $tipo,
        ];
    };

    $adicionarAlerta($pedidosAbertos, 'Pedidos em aberto', 'Pedidos aguardando conclusão no PDV.', '/vendas/pdv.php', 'fa-receipt', 'warning');
    $adicionarAlerta($itensEmPreparo, 'Itens em preparo', 'Itens que ainda estão sendo preparados.', '/vendas/pdv.php', 'fa-fire', 'warning');
    $adicionarAlerta($nfcesAcompanhar, 'NFC-e para acompanhar', 'Documentos pendentes, em contingência ou com erro.', '/vendas/', 'fa-file-invoice', 'danger');
    $adicionarAlerta($ofxPendentes, 'Lançamentos OFX pendentes', 'Linhas importadas aguardando conferência ou correção.', '/financeiro/importar_ofx.php', 'fa-university', 'info');
    $adicionarAlerta($lancamentosVencendo, 'Lançamentos financeiros pendentes', 'Contas vencidas ou com vencimento até os próximos 7 dias.', '/financeiro/lancamentos.php', 'fa-calendar', 'warning');
    $adicionarAlerta($caixasAbertos, 'Fechamentos de caixa em aberto', 'Fechamentos que ainda precisam ser concluídos.', '/vendas/fechamento_caixa.php', 'fa-cash-register', 'warning');

    echo json_encode([
        'success' => true,
        'periodo' => $periodo,
        'rotuloPeriodo' => $rotuloPeriodo,
        'rotuloComparacao' => $rotuloComparacao,
        'atualizadoEm' => date(DATE_ATOM),
        'metricas' => [
            'vendas' => [
                'valor' => $atual['vendas'],
                'variacao' => $variacao($atual['vendas'], $anterior['vendas']),
            ],
            'pedidosFechados' => [
                'valor' => $atual['pedidos'],
                'variacao' => $variacao((float) $atual['pedidos'], (float) $anterior['pedidos']),
            ],
            'ticketMedio' => [
                'valor' => $atual['pedidos'] > 0 ? $atual['vendas'] / $atual['pedidos'] : 0,
                'variacao' => $variacao(
                    $atual['pedidos'] > 0 ? $atual['vendas'] / $atual['pedidos'] : 0,
                    $anterior['pedidos'] > 0 ? $anterior['vendas'] / $anterior['pedidos'] : 0
                ),
            ],
            'pedidosAbertos' => ['valor' => $pedidosAbertos],
        ],
        'tendencia' => $tendencia,
        'alertas' => $alertas,
        'pedidosRecentes' => array_map(static function (array $pedido): array {
            return [
                'id' => (int) $pedido['Id'],
                'cliente' => (string) ($pedido['Cliente'] ?? ''),
                'tipo' => (string) ($pedido['Tipo'] ?? ''),
                'mesa' => (string) ($pedido['Mesa'] ?? ''),
                'abertoEm' => (string) $pedido['DataAbertura'],
                'valor' => (float) $pedido['ValorTotal'],
            ];
        }, $pedidosRecentes),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[dashboard_resumo] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível carregar o resumo do dashboard. Verifique a conexão e o schema do banco.',
    ], JSON_UNESCAPED_UNICODE);
}
