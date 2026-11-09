<?php
/* /public/api/conciliacao.php
 * Conciliação: FinanceiroLancamentos (tabela central, sem filtro de origem)
 * x NFe. Usa ValorLiquido do lançamento x ValorTotal (ou saldo parcial) da NF-e.
 * A conciliação é camada de registro/auditoria: não altera o lançamento.
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
        case 'resumo':
            acaoResumo($pdo);
            break;
        case 'contas':
            acaoContas($pdo);
            break;
        case 'lancamentos':
            acaoLancamentos($pdo);
            break;
        case 'nfe':
            acaoNfe($pdo);
            break;
        case 'sugestoes':
            acaoSugestoes($pdo);
            break;
        case 'confirmar':
            acaoConfirmar($pdo, $usuarioId);
            break;
        case 'estornar':
            acaoEstornar($pdo, $usuarioId);
            break;
        case 'historico':
            acaoHistorico($pdo);
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

/** Janela de datas: usa data_ini/data_fim (YYYY-MM-DD); sem parâmetros, últimos 30 dias. */
function janelaDatas(): array
{
    $ini = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_ini'] ?? '')
        ? $_GET['data_ini']
        : date('Y-m-d', strtotime('-30 days'));
    $fim = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim'] ?? '')
        ? $_GET['data_fim']
        : date('Y-m-d');
    return [$ini . ' 00:00:00', $fim . ' 23:59:59'];
}

/** Saldo ainda não conciliado do lançamento (base: ValorLiquido). */
function saldoLancamento(PDO $pdo, int $id): float
{
    $st = $pdo->prepare("
        SELECT ABS(fl.ValorLiquido) - COALESCE((
            SELECT SUM(ci.ValorConciliado)
            FROM FinanceiroConciliacaoItens ci
            JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
            WHERE ci.LancamentoFinanceiroId = fl.Id AND c.Status = 'confirmada'
        ), 0) AS Saldo
        FROM FinanceiroLancamentos fl
        WHERE fl.Id = ?
    ");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (float) $row['Saldo'] : 0.0;
}

/** Saldo ainda não conciliado da NF-e. */
function saldoNFe(PDO $pdo, int $id): float
{
    $st = $pdo->prepare("
        SELECT n.ValorTotal - COALESCE((
            SELECT SUM(ci.ValorConciliado)
            FROM FinanceiroConciliacaoItens ci
            JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
            WHERE ci.NFeId = n.Id AND c.Status = 'confirmada'
        ), 0) AS Saldo
        FROM NFe n
        WHERE n.Id = ?
    ");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (float) $row['Saldo'] : 0.0;
}

/**
 * Pontuação de um par lançamento x NF-e. Critérios explicáveis:
 * vínculo explícito, fornecedor na descrição, número da NF na descrição,
 * valor com aproximação (multa/mora) e janela de data pós-emissão.
 */
function pontuar(PDO $pdo, array $lanc, array $nfe): array
{
    $motivos = [];
    $score = 0;

    $valorLanc = (float) $lanc['SaldoDisponivel'];
    $saldoNfe = (float) $nfe['SaldoAberto'];
    $descricao = mb_strtolower($lanc['Descricao'] ?? '');

    // 1) Vínculo explícito cadastrado na vinculação
    $st = $pdo->prepare("
        SELECT 1 FROM FinanceiroLancamentoNFe
        WHERE FinanceiroLancamentoId = ? AND NFeId = ?
    ");
    $st->execute([(int) $lanc['Id'], (int) $nfe['Id']]);
    if ($st->fetch()) {
        $score += 40;
        $motivos[] = 'Vínculo NF-e ↔ lançamento já cadastrado';
    }

    // 2) Valor: exato, acima (multa/mora), levemente abaixo, aproximado
    $diff = $valorLanc - $saldoNfe;
    $base = max($saldoNfe, 0.01);
    if (abs($diff) < 0.005) {
        $score += 50;
        $motivos[] = 'Valor exato';
    } elseif ($diff > 0 && $diff / $base <= 0.05) {
        $score += 35;
        $motivos[] = 'Valor acima da NF em ' . number_format($diff, 2, ',', '.') . ' (possível multa/juros)';
    } elseif ($diff < 0 && abs($diff) / $base <= 0.01) {
        $score += 30;
        $motivos[] = 'Valor até 1% abaixo da NF (possível desconto)';
    } elseif (abs($diff) / $base <= 0.10) {
        $score += 10;
        $motivos[] = 'Valor aproximado (dif. ' . number_format(abs($diff), 2, ',', '.') . ')';
    }

    // 3) Fornecedor na descrição: Nome, RazaoSocial e CNPJ
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
        $motivos[] = 'Fornecedor citado na descrição do lançamento';
    }

    // 4) Número da NF na descrição
    $numero = trim((string) ($nfe['Numero'] ?? ''));
    if (
        $numero !== '' && $numero !== '0'
        && mb_strlen($numero) >= 3 && mb_strpos($descricao, mb_strtolower($numero)) !== false
    ) {
        $score += 30;
        $motivos[] = "NF {$numero} citada na descrição";
    }

    // 5) Data: pagamento após a emissão, com janela de tolerância
    if (!empty($nfe['DataEmissao']) && !empty($lanc['DataLancamento'])) {
        $dias = (strtotime($lanc['DataLancamento']) - strtotime($nfe['DataEmissao'])) / 86400;
        if ($dias >= 0 && $dias <= 30) {
            $score += 15;
            $motivos[] = 'Pagamento até 30 dias após a emissão';
        } elseif ($dias >= 0 && $dias <= 90) {
            $score += 8;
            $motivos[] = 'Pagamento até 90 dias após a emissão';
        } elseif ($dias < 0) {
            $score -= 10;
            $motivos[] = '⚠️ Débito anterior à emissão da NF';
        }
    }

    return ['score' => $score, 'motivos' => $motivos];
}

/* =========================================================
 * AÇÕES
 * ========================================================= */

function acaoResumo(PDO $pdo): void
{
    $lancConciliados = $pdo->query("
        SELECT COUNT(DISTINCT ci.LancamentoFinanceiroId)
        FROM FinanceiroConciliacaoItens ci
        JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
        WHERE c.Status = 'confirmada'
    ")->fetchColumn();

    $nfeConciliadas = $pdo->query("
        SELECT COUNT(DISTINCT ci.NFeId)
        FROM FinanceiroConciliacaoItens ci
        JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
        WHERE c.Status = 'confirmada'
    ")->fetchColumn();

    $vinculos = $pdo->query("SELECT COUNT(*) FROM FinanceiroLancamentoNFe")->fetchColumn();

    $conciliadoMes = $pdo->query("
        SELECT COALESCE(SUM(ci.ValorConciliado), 0)
        FROM FinanceiroConciliacaoItens ci
        JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
        WHERE c.Status = 'confirmada'
          AND DATE_FORMAT(c.DataConciliacao, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ")->fetchColumn();

    responder([
        'success' => true,
        'resumo' => [
            'lancamentos_conciliados' => (int) $lancConciliados,
            'nfe_conciliadas' => (int) $nfeConciliadas,
            'conciliado_mes' => round((float) $conciliadoMes, 2),
            'vinculos' => (int) $vinculos,
        ],
    ]);
}

/** Contas bancárias ativas para o filtro. */
function acaoContas(PDO $pdo): void
{
    $st = $pdo->query("
        SELECT Id, Nome, Tipo
        FROM FinanceiroContasBancarias
        WHERE Ativo = 1
        ORDER BY Nome
    ");
    responder(['success' => true, 'contas' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/** Lançamentos de saída com saldo não conciliado — sem filtro de Origem. */
function acaoLancamentos(PDO $pdo): void
{
    $contaId = isset($_GET['conta_id']) && $_GET['conta_id'] !== '' ? (int) $_GET['conta_id'] : null;
    $dataIni = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_ini'] ?? '') ? $_GET['data_ini'] : null;
    $dataFim = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim'] ?? '') ? $_GET['data_fim'] : null;

    $sql = "
        SELECT
            fl.Id,
            fl.DataLancamento,
            fl.ValorBruto,
            fl.ValorLiquido,
            fl.Descricao,
            fl.Origem,
            fl.ContaId,
            cb.Nome AS ContaNome,
            fl.ValorLiquido - COALESCE((
                SELECT SUM(ci.ValorConciliado) FROM FinanceiroConciliacaoItens ci
                JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
                WHERE ci.LancamentoFinanceiroId = fl.Id AND c.Status = 'confirmada'), 0) AS SaldoDisponivel
        FROM FinanceiroLancamentos fl
        LEFT JOIN FinanceiroContasBancarias cb ON cb.Id = fl.ContaId
        WHERE fl.Tipo = 'saida'
          AND fl.Status <> 'cancelado'
          AND fl.ValorLiquido - COALESCE((
                SELECT SUM(ci.ValorConciliado) FROM FinanceiroConciliacaoItens ci
                JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
                WHERE ci.LancamentoFinanceiroId = fl.Id AND c.Status = 'confirmada'), 0) > 0.005
    ";
    $params = [];
    if ($contaId) {
        $sql .= " AND fl.ContaId = ?";
        $params[] = $contaId;
    }
    if ($dataIni) {
        $sql .= " AND fl.DataLancamento >= ?";
        $params[] = $dataIni . ' 00:00:00';
    }
    if ($dataFim) {
        $sql .= " AND fl.DataLancamento <= ?";
        $params[] = $dataFim . ' 23:59:59';
    }
    $sql .= " ORDER BY fl.DataLancamento DESC, fl.Id ASC LIMIT 500";

    $st = $pdo->prepare($sql);
    $st->execute($params);

    responder(['success' => true, 'lancamentos' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/** NF-e com saldo não conciliado. */
function acaoNfe(PDO $pdo): void
{
    $st = $pdo->query("
        SELECT
            n.Id,
            n.Numero,
            n.Serie,
            n.DataEmissao,
            n.ValorTotal,
            n.fk_Mercados_Codigo AS FornecedorId,
            m.Nome,
            m.RazaoSocial,
            m.CNPJ,
            n.ValorTotal - COALESCE((
                SELECT SUM(ci.ValorConciliado) FROM FinanceiroConciliacaoItens ci
                JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
                WHERE ci.NFeId = n.Id AND c.Status = 'confirmada'), 0) AS SaldoAberto
        FROM NFe n
        LEFT JOIN Mercados m ON m.Codigo = n.fk_Mercados_Codigo
        WHERE n.Status = 1
          AND n.ValorTotal - COALESCE((
                SELECT SUM(ci.ValorConciliado) FROM FinanceiroConciliacaoItens ci
                JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
                WHERE ci.NFeId = n.Id AND c.Status = 'confirmada'), 0) > 0.005
        ORDER BY n.DataEmissao DESC
        LIMIT 500
    ");
    responder(['success' => true, 'nfes' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}

/** Sugestões para um lançamento: NF-e candidatas pontuadas. */
function acaoSugestoes(PDO $pdo): void
{
    $lancId = (int) ($_GET['lancamento_id'] ?? 0);
    if ($lancId <= 0)
        throw new Exception('lancamento_id inválido.');

    $st = $pdo->prepare("
        SELECT Id, DataLancamento, ValorLiquido, Descricao
        FROM FinanceiroLancamentos
        WHERE Id = ? AND Tipo = 'saida' AND Status <> 'cancelado'
    ");
    $st->execute([$lancId]);
    $lanc = $st->fetch(PDO::FETCH_ASSOC);
    if (!$lanc)
        throw new Exception('Lançamento não encontrado.');

    $saldoLanc = saldoLancamento($pdo, $lancId);
    if ($saldoLanc <= 0.005)
        throw new Exception('Lançamento já totalmente conciliado.');
    $lanc['SaldoDisponivel'] = $saldoLanc;

    $st = $pdo->prepare("
        SELECT
            n.Id,
            n.Numero,
            n.Serie,
            n.DataEmissao,
            n.ValorTotal,
            m.Nome,
            m.RazaoSocial,
            m.CNPJ,
            n.ValorTotal - COALESCE((
                SELECT SUM(ci.ValorConciliado) FROM FinanceiroConciliacaoItens ci
                JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
                WHERE ci.NFeId = n.Id AND c.Status = 'confirmada'), 0) AS SaldoAberto
        FROM NFe n
        LEFT JOIN Mercados m ON m.Codigo = n.fk_Mercados_Codigo
        WHERE n.Status = 1
          AND n.ValorTotal - COALESCE((
                SELECT SUM(ci.ValorConciliado) FROM FinanceiroConciliacaoItens ci
                JOIN FinanceiroConciliacoes c ON c.Id = ci.ConciliacaoId
                WHERE ci.NFeId = n.Id AND c.Status = 'confirmada'), 0) > 0.005
        ORDER BY n.DataEmissao DESC
        LIMIT 500
    ");
    $st->execute();
    $candidatos = $st->fetchAll(PDO::FETCH_ASSOC);

    $sugestoes = [];
    foreach ($candidatos as $nfe) {
        $p = pontuar($pdo, $lanc, $nfe);
        if ($p['score'] >= 45) {
            $sugestoes[] = [
                'nfe_id' => (int) $nfe['Id'],
                'score' => $p['score'],
                'confianca' => $p['score'] >= 85 ? 'alta' : ($p['score'] >= 60 ? 'media' : 'baixa'),
                'motivos' => $p['motivos'],
                'saldo' => round((float) $nfe['SaldoAberto'], 2),
                'numero' => $nfe['Numero'],
                'serie' => $nfe['Serie'],
                'fornecedor' => $nfe['Nome'],
            ];
        }
    }

    usort($sugestoes, fn($a, $b) => $b['score'] <=> $a['score']);

    responder([
        'success' => true,
        'lancamento' => [
            'id' => (int) $lanc['Id'],
            'valor' => round($saldoLanc, 2),
            'data' => $lanc['DataLancamento'],
            'descricao' => $lanc['Descricao'],
        ],
        'sugestoes' => array_slice($sugestoes, 0, 10),
    ]);
}

/** Confirma a conciliação com validação de saldo dos dois lados. */
function acaoConfirmar(PDO $pdo, int $usuarioId): void
{
    $entrada = json_decode(file_get_contents('php://input'), true) ?? [];

    $lancId = (int) ($entrada['lancamento_id'] ?? 0);
    $itens = $entrada['itens'] ?? [];
    $obs = trim($entrada['observacoes'] ?? '');

    if ($lancId <= 0 || !is_array($itens) || count($itens) === 0) {
        throw new Exception('Informe o lançamento e ao menos uma NF-e.');
    }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("
            SELECT Id FROM FinanceiroLancamentos
            WHERE Id = ? AND Tipo = 'saida' AND Status <> 'cancelado' FOR UPDATE
        ");
        $st->execute([$lancId]);
        if (!$st->fetch())
            throw new Exception('Lançamento inválido.');

        $saldoLanc = saldoLancamento($pdo, $lancId);
        $totalItens = 0.0;
        $itensValidos = [];

        foreach ($itens as $item) {
            $nfeId = (int) ($item['nfe_id'] ?? 0);
            $valor = round((float) ($item['valor'] ?? 0), 2);
            if ($nfeId <= 0)
                throw new Exception('Item inválido.');
            if ($valor <= 0)
                throw new Exception("Valor inválido para a NF #{$nfeId}.");

            $st = $pdo->prepare("SELECT Id FROM NFe WHERE Id = ? AND Status = 1 FOR UPDATE");
            $st->execute([$nfeId]);
            if (!$st->fetch())
                throw new Exception("NF-e #{$nfeId} não encontrada ou cancelada.");

            $saldo = saldoNFe($pdo, $nfeId);
            if ($valor > $saldo + 0.005) {
                throw new Exception("Valor excede o saldo da NF #{$nfeId} (R$ "
                    . number_format($saldo, 2, ',', '.') . ").");
            }

            $itensValidos[] = ['nfe_id' => $nfeId, 'valor' => $valor];
            $totalItens += $valor;
        }

        if ($totalItens > $saldoLanc + 0.005) {
            throw new Exception('Total das NF-e excede o valor disponível do lançamento.');
        }

        $st = $pdo->prepare("
            INSERT INTO FinanceiroConciliacoes (Status, UsuarioId, Observacoes)
            VALUES ('confirmada', ?, ?)
        ");
        $st->execute([$usuarioId, $obs !== '' ? $obs : null]);
        $conciliacaoId = (int) $pdo->lastInsertId();

        $stItem = $pdo->prepare("
            INSERT INTO FinanceiroConciliacaoItens
                (ConciliacaoId, LancamentoFinanceiroId, NFeId, ValorConciliado, Observacoes)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($itensValidos as $i) {
            $stItem->execute([$conciliacaoId, $lancId, $i['nfe_id'], $i['valor'], $obs !== '' ? $obs : null]);
        }

        $pdo->commit();

        responder([
            'success' => true,
            'conciliacao_id' => $conciliacaoId,
            'message' => 'Conciliação confirmada com sucesso.',
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception($e->getMessage());
    }
}

function acaoEstornar(PDO $pdo, int $usuarioId): void
{
    $entrada = json_decode(file_get_contents('php://input'), true) ?? [];
    $conciliacaoId = (int) ($entrada['conciliacao_id'] ?? 0);
    $motivo = trim($entrada['motivo'] ?? '');

    if ($conciliacaoId <= 0)
        throw new Exception('conciliacao_id inválido.');
    if ($motivo === '')
        throw new Exception('Informe o motivo do estorno.');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("
            SELECT Id FROM FinanceiroConciliacoes
            WHERE Id = ? AND Status = 'confirmada' FOR UPDATE
        ");
        $st->execute([$conciliacaoId]);
        if (!$st->fetch())
            throw new Exception('Conciliação não encontrada ou já estornada.');

        $st = $pdo->prepare("
            UPDATE FinanceiroConciliacoes
            SET Status = 'estornada', EstornadoEm = NOW(), EstornadoPorId = ?,
                Observacoes = CONCAT(COALESCE(Observacoes,''), ' | Estorno: ', ?)
            WHERE Id = ?
        ");
        $st->execute([$usuarioId, $motivo, $conciliacaoId]);

        $pdo->commit();
        responder(['success' => true, 'message' => 'Conciliação estornada.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception($e->getMessage());
    }
}

function acaoHistorico(PDO $pdo): void
{
    $st = $pdo->query("
        SELECT
            c.Id,
            c.DataConciliacao,
            c.Status,
            u.NomeCompleto AS Usuario,
            COUNT(ci.Id) AS Itens,
            SUM(ci.ValorConciliado) AS Total,
            GROUP_CONCAT(DISTINCT ci.LancamentoFinanceiroId) AS Lancamentos,
            GROUP_CONCAT(DISTINCT ci.NFeId) AS NFes
        FROM FinanceiroConciliacoes c
        LEFT JOIN FinanceiroConciliacaoItens ci ON ci.ConciliacaoId = c.Id
        LEFT JOIN Usuarios u ON u.Id = c.UsuarioId
        GROUP BY c.Id
        ORDER BY c.DataConciliacao DESC
        LIMIT 30
    ");
    responder(['success' => true, 'historico' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}