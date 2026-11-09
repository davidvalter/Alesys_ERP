<?php
/* /public/api/vinculacao_nfe.php
 * Vínculo explícito FinanceiroLancamentos x NFe (FinanceiroLancamentoNFe).
 * Ações:
 *   lancamentos_sem_vinculo : lançamentos de saída sem NF-e vinculada (filtro por DataLancamento)
 *   nfes_sem_vinculo        : NF-e sem lançamento vinculado (filtro por DataEmissao)
 *   candidatos_nfe          : NF-e candidatas para um lançamento
 *   candidatos_lancamento   : lançamentos candidatos para uma NF-e
 *   vincular / desvincular  : manter o vínculo
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

session_start();

try {
    $pdo = getDB();

    $usuarioId = $_SESSION['Usuario']['id'] ?? null;
    if (!$usuarioId) {
        throw new Exception('Sessão expirada. Faça login novamente.');
    }

    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

    switch ($acao) {
        case 'lancamentos_sem_vinculo':
            acaoLancamentosSemVinculo($pdo);
            break;
        case 'nfes_sem_vinculo':
            acaoNfesSemVinculo($pdo);
            break;
        case 'candidatos_nfe':
            acaoCandidatosNfe($pdo);
            break;
        case 'candidatos_lancamento':
            acaoCandidatosLancamento($pdo);
            break;
        case 'vincular':
            acaoVincular($pdo, $usuarioId);
            break;
        case 'desvincular':
            acaoDesvincular($pdo);
            break;
        default:
            throw new Exception('Ação inválida.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;

/* =========================================================
 * HELPERS
 * ========================================================= */

function responder(array $dados): void
{
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lê a janela de datas por aba:
 * - aba Por Lançamento: data_ini_lanc / data_fim_lanc
 * - aba Por NF-e:       data_ini_nfe  / data_fim_nfe
 * Sem parâmetros, usa os últimos 30 dias (proteção contra carga pesada).
 */
function resolverDatas(string $sufixo = 'lanc'): array
{
    $kIni = in_array($sufixo, ['lanc', 'nfe'], true) ? ('data_ini_' . $sufixo) : 'data_ini';
    $kFim = in_array($sufixo, ['lanc', 'nfe'], true) ? ('data_fim_' . $sufixo) : 'data_fim';

    $ini = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET[$kIni] ?? '')
        ? $_GET[$kIni]
        : date('Y-m-d', strtotime('-30 days'));
    $fim = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET[$kFim] ?? '')
        ? $_GET[$kFim]
        : date('Y-m-d');

    return [$ini . ' 00:00:00', $fim . ' 23:59:59'];
}


/**
 * Pontuação de um par lançamento x NF-e. Critérios explicáveis:
 * valor com aproximação (multa/mora), fornecedor na descrição,
 * número da NF na descrição e janela de data pós-emissão.
 *
 * COMPARAÇÃO POR MÓDULO: saídas podem estar gravadas negativas.
 * DATA EFETIVA DO PAGAMENTO: DataBaixa quando existir (é a data real
 * do pagamento); DataLancamento pode ser a data da importação.
 */
function pontuarVinculo(array $lanc, array $nfe): array
{
    $motivos = [];
    $score = 0;

    $valorLanc = abs((float) $lanc['ValorLiquido']);
    $valorNfe = abs((float) $nfe['ValorTotal']);
    $descricao = mb_strtolower($lanc['Descricao'] ?? '');

    // Valor com aproximação (multa/mora) — sempre por módulo
    $diff = $valorLanc - $valorNfe;
    $base = max($valorNfe, 0.01);
    if (abs($diff) < 0.005) {
        $score += 50;
        $motivos[] = 'Valor exato';
    } elseif ($diff > 0 && $diff / $base <= 0.05) {
        $score += 35;
        $motivos[] = 'Valor acima da NF em ' . number_format($diff, 2, ',', '.') . ' (possível multa/juros)';
    } elseif ($diff < 0 && abs($diff) / $base <= 0.01) {
        $score += 30;
        $motivos[] = 'Valor até 1% abaixo da NF';
    } elseif (abs($diff) / $base <= 0.10) {
        $score += 10;
        $motivos[] = 'Valor aproximado (dif. ' . number_format(abs($diff), 2, ',', '.') . ')';
    }

    // Fornecedor na descrição: Nome, RazaoSocial e CNPJ
    $matchFornecedor = false;
    foreach (['Nome', 'RazaoSocial'] as $campo) {
        $nome = trim((string) ($nfe[$campo] ?? ''));
        if ($nome === '' || mb_strlen($nome) < 4)
            continue;
        $partes = preg_split('/\s+/', mb_strtolower($nome));
        $significativas = array_values(array_filter($partes, fn($p) => mb_strlen($p) >= 3));
        if (!$significativas)
            continue;
        $stem = implode(' ', array_slice($significativas, 0, 2));
        if (mb_strpos($descricao, $stem) !== false) {
            $matchFornecedor = true;
            break;
        }
    }
    $cnpj = preg_replace('/\D/', '', (string) ($nfe['CNPJ'] ?? ''));
    if (strlen($cnpj) === 14 && mb_strpos($descricao, $cnpj) !== false) {
        $matchFornecedor = true;
    }
    if ($matchFornecedor) {
        $score += 25;
        $motivos[] = 'Fornecedor citado na descrição';
    }

    // Número da NF na descrição
    $numero = trim((string) ($nfe['Numero'] ?? ''));
    if (
        $numero !== '' && $numero !== '0'
        && mb_strlen($numero) >= 3 && mb_strpos($descricao, mb_strtolower($numero)) !== false
    ) {
        $score += 30;
        $motivos[] = "NF {$numero} citada na descrição";
    }

    // Data: usa a data EFETIVA do pagamento (DataBaixa > DataLancamento).
    // Mesmo dia da emissão vale mais do que qualquer janela posterior.
    $dataPagamento = !empty($lanc['DataBaixa'])
        ? $lanc['DataBaixa']
        : ($lanc['DataLancamento'] ?? null);

    if (!empty($nfe['DataEmissao']) && !empty($dataPagamento)) {
        $dias = (int) round((strtotime($dataPagamento) - strtotime($nfe['DataEmissao'])) / 86400);
        if ($dias === 0) {
            $score += 35;
            $motivos[] = 'Pagamento na mesma data da emissão';
        } elseif ($dias > 0 && $dias <= 30) {
            $score += 15;
            $motivos[] = 'Pagamento até 30 dias após a emissão';
        } elseif ($dias > 0 && $dias <= 90) {
            $score += 8;
            $motivos[] = 'Pagamento até 90 dias após a emissão';
        } elseif ($dias < 0) {
            $score -= 10;
            $motivos[] = '⚠️ Débito anterior à emissão';
        }
    }

    return ['score' => $score, 'motivos' => $motivos];
}

/* =========================================================
 * AÇÕES
 * ========================================================= */

function acaoLancamentosSemVinculo(PDO $pdo): void
{
    [$ini, $fim] = resolverDatas('lanc');

    // A janela deve capturar o lançamento se ele se encaixar
    // em DataLancamento OU em DataBaixa.
    $st = $pdo->prepare("
        SELECT
            fl.Id,
            fl.DataLancamento,
            fl.DataBaixa,
            fl.ValorBruto,
            fl.ValorLiquido,
            fl.Descricao,
            fl.Origem
        FROM FinanceiroLancamentos fl
        WHERE fl.Tipo = 'saida'
          AND fl.Status <> 'cancelado'
          AND (
                fl.DataLancamento BETWEEN ? AND ?
             OR (fl.DataBaixa IS NOT NULL AND fl.DataBaixa BETWEEN ? AND ?)
          )
          AND NOT EXISTS (
              SELECT 1 FROM FinanceiroLancamentoNFe r
              WHERE r.FinanceiroLancamentoId = fl.Id
          )
        ORDER BY COALESCE(fl.DataBaixa, fl.DataLancamento) DESC, fl.Id ASC
        LIMIT 500
    ");
    $st->execute([$ini, $fim, $ini, $fim]);
    responder(['success' => true, 'lancamentos' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

function acaoNfesSemVinculo(PDO $pdo): void
{
    [$ini, $fim] = resolverDatas('nfe');

    $st = $pdo->prepare("
        SELECT
            n.Id,
            n.Numero,
            n.Serie,
            n.DataEmissao,
            n.ValorTotal,
            n.fk_Mercados_Codigo AS FornecedorId,
            m.Nome,
            m.RazaoSocial,
            m.CNPJ
        FROM NFe n
        LEFT JOIN Mercados m ON m.Codigo = n.fk_Mercados_Codigo
        WHERE n.Status = 1
          AND n.DataEmissao BETWEEN ? AND ?
          AND NOT EXISTS (
              SELECT 1 FROM FinanceiroLancamentoNFe r
              WHERE r.NFeId = n.Id
          )
        ORDER BY n.DataEmissao DESC
        LIMIT 500
    ");
    $st->execute([$ini, $fim]);
    responder(['success' => true, 'nfes' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/** NF-e candidatas para um lançamento. */
function acaoCandidatosNfe(PDO $pdo): void
{
    $lancId = (int) ($_GET['lancamento_id'] ?? 0);

    if ($lancId <= 0)
        throw new Exception('lancamento_id inválido.');

    $st = $pdo->prepare("
        SELECT Id, DataLancamento, DataBaixa, ValorLiquido, Descricao
        FROM FinanceiroLancamentos
        WHERE Id = ? AND Tipo = 'saida' AND Status <> 'cancelado'
    ");
    $st->execute([$lancId]);
    $lanc = $st->fetch(PDO::FETCH_ASSOC);
    if (!$lanc)
        throw new Exception('Lançamento não encontrado.');

    // Data efetiva do pagamento (baixa > lançamento) e valor por módulo
    //$dataRef = !empty($lanc['DataBaixa']) ? $lanc['DataEmissaoRef'] = null : null;
    $dataRef = !empty($lanc['DataBaixa']) ? $lanc['DataBaixa'] : $lanc['DataLancamento'];
    $valorLanc = abs((float) $lanc['ValorLiquido']);

    // Janela: NF-e emitidas até 180 dias antes do pagamento (nota em aberto
    // paga tarde) e até 7 dias depois (paga quase junto).
    $diasAntes = date('Y-m-d', strtotime($dataRef . ' -180 days'));
    $diasDepois = date('Y-m-d', strtotime($dataRef . ' +7 days'));

    // PRÉ-FILTRO POR VALOR: só entram NF-e com valor até 10% de diferença.
    // Garante que a nota de valor exato entra, mesmo com milhões de registros.
    $st = $pdo->prepare("
        SELECT
            n.Id, n.Numero, n.Serie, n.DataEmissao, n.ValorTotal,
            m.Nome, m.RazaoSocial, m.CNPJ
        FROM NFe n
        LEFT JOIN Mercados m ON m.Codigo = n.fk_Mercados_Codigo
        WHERE n.Status = 1
          AND n.DataEmissao BETWEEN ? AND ?
          AND ABS(ABS(n.ValorTotal) - ?) <= ? * 0.10
        ORDER BY ABS(ABS(n.ValorTotal) - ?) ASC, n.DataEmissao DESC
        LIMIT 200
    ");
    $st->execute([
        $diasAntes . ' 00:00:00',
        $diasDepois . ' 23:59:59',
        $valorLanc,
        $valorLanc,
        $valorLanc,
    ]);
    $nfes = $st->fetchAll(PDO::FETCH_ASSOC);

    $candidatos = [];
    foreach ($nfes as $nfe) {
        $p = pontuarVinculo($lanc, $nfe);
        if ($p['score'] >= 45) {
            $candidatos[] = [
                'nfe_id' => (int) $nfe['Id'],
                'numero' => $nfe['Numero'],
                'serie' => $nfe['Serie'],
                'emissao' => $nfe['DataEmissao'],
                'valor' => abs((float) $nfe['ValorTotal']),
                'fornecedor' => $nfe['Nome'],
                'score' => $p['score'],
                'confianca' => $p['score'] >= 85 ? 'alta' : ($p['score'] >= 60 ? 'media' : 'baixa'),
                'motivos' => $p['motivos'],
            ];
        }
    }

    usort($candidatos, fn($a, $b) => $b['score'] <=> $a['score']);

    responder([
        'success' => true,
        'lancamento' => [
            'id' => (int) $lanc['Id'],
            'valor' => $valorLanc,
            'descricao' => $lanc['Descricao'],
        ],
        'candidatos' => array_slice($candidatos, 0, 15),
    ]);
}

/** Lançamentos candidatos para uma NF-e (vice-versa). */
function acaoCandidatosLancamento(PDO $pdo): void
{
    $nfeId = (int) ($_GET['nfe_id'] ?? 0);
    if ($nfeId <= 0)
        throw new Exception('nfe_id inválido.');

    $st = $pdo->prepare("
        SELECT
            n.Id, n.Numero, n.Serie, n.DataEmissao, n.ValorTotal,
            m.Nome, m.RazaoSocial, m.CNPJ
        FROM NFe n
        LEFT JOIN Mercados m ON m.Codigo = n.fk_Mercados_Codigo
        WHERE n.Id = ? AND n.Status = 1
    ");
    $st->execute([$nfeId]);
    $nfe = $st->fetch(PDO::FETCH_ASSOC);
    if (!$nfe)
        throw new Exception('NF-e não encontrada ou cancelada.');

    $valorNfe = abs((float) $nfe['ValorTotal']);

    // Janela pela emissão: lançamentos pagos (baixa) de 90 dias antes
    // até 180 dias depois da emissão.
    $diasAntes = date('Y-m-d', strtotime($nfe['DataEmissao'] . ' -90 days'));
    $diasDepois = date('Y-m-d', strtotime($nfe['DataEmissao'] . ' +180 days'));

    // PRÉ-FILTRO POR VALOR + data efetiva (COALESCE baixa/lançamento):
    // o corte nunca elimina o lançamento de valor exato, apenas registros
    // com valor fora da tolerância.
    $st = $pdo->prepare("
        SELECT
            fl.Id, fl.DataLancamento, fl.DataBaixa, fl.ValorBruto, fl.ValorLiquido, fl.Descricao
        FROM FinanceiroLancamentos fl
        WHERE fl.Tipo = 'saida'
          AND fl.Status <> 'cancelado'
          AND COALESCE(fl.DataBaixa, fl.DataLancamento) BETWEEN ? AND ?
          AND ABS(ABS(fl.ValorLiquido) - ?) <= ? * 0.10
        ORDER BY ABS(ABS(fl.ValorLiquido) - ?) ASC, COALESCE(fl.DataBaixa, fl.DataLancamento) DESC
        LIMIT 200
    ");
    $st->execute([
        $diasAntes . ' 00:00:00',
        $diasDepois . ' 23:59:59',
        $valorNfe,
        $valorNfe,
        $valorNfe,
    ]);
    $lancamentos = $st->fetchAll(PDO::FETCH_ASSOC);

    $candidatos = [];
    foreach ($lancamentos as $lanc) {
        $p = pontuarVinculo($lanc, $nfe);
        if ($p['score'] >= 45) {
            $candidatos[] = [
                'lancamento_id' => (int) $lanc['Id'],
                'data' => $lanc['DataLancamento'],
                'data_baixa' => $lanc['DataBaixa'],
                'valor' => abs((float) $lanc['ValorLiquido']),
                'descricao' => $lanc['Descricao'],
                'score' => $p['score'],
                'confianca' => $p['score'] >= 85 ? 'alta' : ($p['score'] >= 60 ? 'media' : 'baixa'),
                'motivos' => $p['motivos'],
            ];
        }
    }

    usort($candidatos, fn($a, $b) => $b['score'] <=> $a['score']);

    responder([
        'success' => true,
        'nfe' => [
            'id' => (int) $nfe['Id'],
            'numero' => $nfe['Numero'],
            'serie' => $nfe['Serie'],
            'valor' => $valorNfe,
            'fornecedor' => $nfe['Nome'],
        ],
        'candidatos' => array_slice($candidatos, 0, 15),
    ]);
}


/** Cria o vínculo. Divergência de valor acima de 5% exige justificativa. */
function acaoVincular(PDO $pdo, int $usuarioId): void
{
    $entrada = json_decode(file_get_contents('php://input'), true) ?? [];
    $lancId = (int) ($entrada['lancamento_id'] ?? 0);
    $nfeId = (int) ($entrada['nfe_id'] ?? 0);
    $obs = trim($entrada['observacoes'] ?? '');

    if ($lancId <= 0 || $nfeId <= 0)
        throw new Exception('Informe lançamento e NF-e.');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT Id FROM FinanceiroLancamentoNFe WHERE FinanceiroLancamentoId = ? AND NFeId = ? FOR UPDATE");
        $st->execute([$lancId, $nfeId]);
        if ($st->fetch())
            throw new Exception('Esse vínculo já existe.');

        $st = $pdo->prepare("SELECT ValorLiquido FROM FinanceiroLancamentos WHERE Id = ? AND Tipo = 'saida' AND Status <> 'cancelado' FOR UPDATE");
        $st->execute([$lancId]);
        $lanc = $st->fetch(PDO::FETCH_ASSOC);
        if (!$lanc)
            throw new Exception('Lançamento inválido.');

        $st = $pdo->prepare("SELECT ValorTotal FROM NFe WHERE Id = ? AND Status = 1 FOR UPDATE");
        $st->execute([$nfeId]);
        $nfe = $st->fetch(PDO::FETCH_ASSOC);
        if (!$nfe)
            throw new Exception('NF-e não encontrada ou cancelada.');

        // Divergência por MÓDULO: saídas podem estar gravadas com sinal
        // negativo (ex.: -150.00) enquanto a NF é positiva (150.00).
        // Comparar sem abs() transformava valores idênticos em 200% de
        // divergência e bloqueava o vínculo.
        $valorLanc = abs((float) $lanc['ValorLiquido']);
        $valorNfe = abs((float) $nfe['ValorTotal']);
        $base = max($valorNfe, 0.01);
        $diff = abs($valorLanc - $valorNfe);
        if ($diff / $base > 0.05 && $obs === '') {
            throw new Exception('Divergência de valor acima de 5%. Informe a justificativa (ex.: multa, juros, desconto).');
        }

        $st = $pdo->prepare("
            INSERT INTO FinanceiroLancamentoNFe (FinanceiroLancamentoId, NFeId, UsuarioId)
            VALUES (?, ?, ?)
        ");
        $st->execute([$lancId, $nfeId, $usuarioId]);

        $pdo->commit();
        responder(['success' => true, 'message' => 'Vínculo criado com sucesso.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception($e->getMessage());
    }
}

function acaoDesvincular(PDO $pdo): void
{
    $entrada = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($entrada['vinculo_id'] ?? 0);
    if ($id <= 0)
        throw new Exception('vinculo_id inválido.');

    // A conciliação confirmada não depende do vínculo para existir;
    // remover o vínculo não estorna conciliações.
    $st = $pdo->prepare("DELETE FROM FinanceiroLancamentoNFe WHERE Id = ?");
    $st->execute([$id]);

    responder(['success' => true, 'message' => 'Vínculo removido.']);
}

