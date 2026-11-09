<?php
// public/api/get_dre.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    // ---------- Parâmetros ----------
    $modo = ($_GET['modo'] ?? 'mensal') === 'anual' ? 'anual' : 'mensal';
    $contaId = isset($_GET['conta_id']) && $_GET['conta_id'] !== '' ? (int) $_GET['conta_id'] : null;
    $status = ($_GET['status'] ?? 'todos') === 'pagos' ? "'pago'" : "'pago','pendente'";

    if ($modo === 'mensal') {
        preg_match('/^(\d{4})-(\d{2})$/', $_GET['mes'] ?? '', $m);
        if (!$m)
            throw new Exception('Mês inválido (formato AAAA-MM).');
        $im = (int) $m[2];
        if ($im < 1 || $im > 12)
            throw new Exception('Mês inválido.');
        $ini = sprintf('%04d-%02d-01', (int) $m[1], $im);
        $fim = date('Y-m-t', strtotime($ini));
        $rotulo = 'Mês ' . str_pad($im, 2, '0', STR_PAD_LEFT) . '/' . $m[1];
    } else {
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        if ($ano < 2000 || $ano > 2100)
            throw new Exception('Ano inválido.');
        $ini = $ano . '-01-01';
        $fim = $ano . '-12-31';
        $rotulo = 'Ano ' . $ano;
    }

    // Competência derivada: baixa > prevista > lançamento
    $comp = "COALESCE(fl.DataBaixa, fl.DataPrevista, DATE(fl.DataLancamento))";
    $extras = "AND fl.Status IN ($status)" . ($contaId ? " AND fl.ContaId = $contaId" : '');

    // ---------- Valores por linha (período total) ----------
    $stmt = $pdo->prepare(
        "SELECT dl.Codigo,
                SUM(CASE WHEN fl.Tipo = 'entrada' THEN fl.ValorBruto
                         WHEN fl.Tipo = 'saida'   THEN -fl.ValorBruto END) AS Valor
         FROM DreLinhas dl
         JOIN DreCategoriaMap m ON m.DreLinhaId = dl.Id
         JOIN FinanceiroLancamentos fl
              ON fl.CategoriaId = m.CategoriaId
             AND $comp BETWEEN ? AND ?
             $extras
         WHERE dl.Ativo = 1
         GROUP BY dl.Codigo"
    );
    $stmt->execute([$ini, $fim]);
    $valores = array_map('floatval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Valor', 'Codigo'));

    // ---------- Anual: valores por mês ----------
    $valoresMes = [];
    if ($modo === 'anual') {
        $stmt = $pdo->prepare(
            "SELECT dl.Codigo, MONTH($comp) AS Mes,
                    SUM(CASE WHEN fl.Tipo = 'entrada' THEN fl.ValorBruto
                             WHEN fl.Tipo = 'saida'   THEN -fl.ValorBruto END) AS Valor
             FROM DreLinhas dl
             JOIN DreCategoriaMap m ON m.DreLinhaId = dl.Id
             JOIN FinanceiroLancamentos fl
                  ON fl.CategoriaId = m.CategoriaId
                 AND $comp BETWEEN ? AND ?
                 $extras
             WHERE dl.Ativo = 1
             GROUP BY dl.Codigo, Mes"
        );
        $stmt->execute([$ini, $fim]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $valoresMes[$r['Codigo']][(int) $r['Mes']] = (float) $r['Valor'];
        }
    }

    // ---------- Estrutura de linhas ----------
    $linhasCfg = $pdo->query(
        "SELECT Codigo, Nome, Operacao, Ordem FROM DreLinhas WHERE Ativo = 1 ORDER BY Ordem"
    )->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($linhasCfg as $l) {
        $l['valor'] = $valores[$l['Codigo']] ?? 0.0;
        $map[$l['Codigo']] = $l;
    }

    // Subtotais derivados em PHP (nunca lidos do banco)
    $map['REC_LIQUIDA']['valor'] = $map['REC_BRUTA']['valor'] - $map['DEDUCOES']['valor'];
    $map['LUCRO_BRUTO']['valor'] = $map['REC_LIQUIDA']['valor'] - $map['CMV']['valor'];
    $map['RES_OPER']['valor'] = $map['LUCRO_BRUTO']['valor']
        - $map['DESP_VENDAS']['valor'] - $map['DESP_ADM']['valor'] - $map['DESP_FIN']['valor'];
    $map['LAIR']['valor'] = $map['RES_OPER']['valor'] - $map['DESP_EXTRA']['valor'];
    $map['RES_LIQUIDO']['valor'] = $map['LAIR']['valor'] - $map['PROV_IR_CSLL']['valor'];

    $rb = abs($map['REC_BRUTA']['valor']);

    $linhas = [];
    foreach ($map as $l) {
        $v = round((float) $l['valor'], 2);
        $linha = [
            'codigo' => $l['Codigo'],
            'nome' => $l['Nome'],
            'tipo' => $l['Operacao'],
            'valor' => $v,
            'total' => $v,
            'pct' => $rb > 0 ? round($v / $rb * 100, 1) : null,
            'meses' => [],
        ];
        if ($modo === 'anual') {
            $tot = 0.0;
            for ($i = 1; $i <= 12; $i++) {
                $mv = $valoresMes[$l['Codigo']][$i] ?? 0.0;
                if ($l['Operacao'] !== 'linha') {
                    // subtotais não têm detalhamento real por mês
                    $mv = null;
                } else {
                    $tot += $mv;
                }
                $linha['meses'][] = $mv;
            }
            if ($l['Operacao'] === 'linha')
                $linha['total'] = round($tot, 2);
        }
        $linhas[] = $linha;
    }

    // ---------- Diagnóstico: lançamentos sem classificação DRE ----------
    $stmt = $pdo->prepare(
        "SELECT c.Nome,
                SUM(CASE WHEN fl.Tipo = 'entrada' THEN fl.ValorBruto
                         WHEN fl.Tipo = 'saida'   THEN -fl.ValorBruto END) AS Valor
         FROM FinanceiroLancamentos fl
         JOIN FinanceiroCategorias c ON c.Id = fl.CategoriaId
         WHERE $comp BETWEEN ? AND ?
           $extras
           AND fl.CategoriaId NOT IN (SELECT CategoriaId FROM DreCategoriaMap)
         GROUP BY c.Nome
         ORDER BY 2 DESC
         LIMIT 10"
    );
    $stmt->execute([$ini, $fim]);
    $diagCategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---------- Contas ----------
    $contas = [];
    try {
        $contas = $pdo->query("SELECT Id, Nome FROM FinanceiroContas WHERE Ativo = 1 ORDER BY Nome")
            ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $contas = [];
    }

    echo json_encode([
        'success' => true,
        'ok' => true,
        'modo' => $modo,
        'periodo' => ['ini' => $ini, 'fim' => $fim, 'rotulo' => $rotulo],
        'linhas' => $linhas,
        'contas' => $contas,
        'diagnostico' => [
            'total' => round(array_sum(array_column($diagCategorias, 'Valor')), 2),
            'categorias' => array_map(function ($c) {
                return ['Nome' => $c['Nome'], 'Valor' => round((float) $c['Valor'], 2)];
            }, $diagCategorias),
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'ok' => false, 'message' => $e->getMessage()]);
}