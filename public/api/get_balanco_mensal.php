<?php
// public/api/get_balanco_mensal.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('m');
    $ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) date('Y');
    if ($mes < 1 || $mes > 12)
        $mes = (int) date('m');

    // número de dias no mês (compatível sem extensão calendar)
    $diasNoMes = (int) date('t', strtotime(sprintf('%04d-%02d-01', $ano, $mes)));
    $dias = [];
    for ($d = 1; $d <= $diasNoMes; $d++)
        $dias[] = str_pad($d, 2, '0', STR_PAD_LEFT);

    // inicializa acumuladores (índices 1..N)
    $totaisEntradasDia = array_fill(1, $diasNoMes, 0.0);
    $totaisSaidasDia = array_fill(1, $diasNoMes, 0.0);

    // Query: soma por categoria, tipo e dia
    $sql = "
        SELECT
            c.Id AS CategoriaId,
            c.Nome AS CategoriaNome,
            c.Tipo AS Tipo,
            DAY(f.DataBaixa) AS Dia,
            SUM(f.ValorLiquido) AS TotalDia
        FROM FinanceiroLancamentos f
        LEFT JOIN FinanceiroCategorias c ON c.Id = f.CategoriaId
        WHERE MONTH(f.DataBaixa) = :mes AND YEAR(f.DataBaixa) = :ano
        GROUP BY c.Id, DAY(f.DataBaixa)
        ORDER BY c.Tipo DESC, c.Nome ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':mes' => $mes, ':ano' => $ano]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoriasEntrada = []; // keyed by CategoriaId
    $categoriasSaida = [];   // keyed by CategoriaId

    foreach ($rows as $r) {
        $cid = $r['CategoriaId'] ?? ('uncat_' . ($r['CategoriaNome'] ?? 'Sem Categoria'));
        $nome = $r['CategoriaNome'] ?? 'Sem Categoria';
        $tipo = $r['Tipo'] ?? 'entrada';
        $dia = (int) $r['Dia'];
        $totalRaw = (float) $r['TotalDia'];

        if ($tipo === 'entrada') {
            if (!isset($categoriasEntrada[$cid])) {
                $categoriasEntrada[$cid] = [
                    'id' => $cid,
                    'nome' => $nome,
                    'valores' => array_fill(1, $diasNoMes, 0.0)
                ];
            }
            $categoriasEntrada[$cid]['valores'][$dia] += $totalRaw;
            $totaisEntradasDia[$dia] += $totalRaw;
        } else {
            // tratar saídas como valores negativos
            if (!isset($categoriasSaida[$cid])) {
                $categoriasSaida[$cid] = [
                    'id' => $cid,
                    'nome' => $nome,
                    'valores' => array_fill(1, $diasNoMes, 0.0)
                ];
            }
            $categoriasSaida[$cid]['valores'][$dia] += $totalRaw;
            $totaisSaidasDia[$dia] += $totalRaw;
        }
    }

    // balanço diário: entradas + saídas (saídas já negativas)
    $balancoDiario = [];
    for ($d = 1; $d <= $diasNoMes; $d++) {
        $balancoDiario[$d] = ($totaisEntradasDia[$d] ?? 0.0) + ($totaisSaidasDia[$d] ?? 0.0);
    }

    echo json_encode([
        'success' => true,
        'mes' => $mes,
        'ano' => $ano,
        'diasNoMes' => $diasNoMes,
        'dias' => $dias,
        'categorias_entrada' => array_values($categoriasEntrada),
        'categorias_saida' => array_values($categoriasSaida),
        'totais_entradas_dia' => $totaisEntradasDia,
        'totais_saidas_dia' => $totaisSaidasDia,
        'balanco_diario' => $balancoDiario
    ], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

} catch (Exception $e) {
    error_log('Erro get_balanco_mensal: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao gerar balanço mensal.']);
}
