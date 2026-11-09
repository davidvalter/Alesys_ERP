<?php
header('Content-Type: application/json');
require_once '../../configs/database.php';

$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');
$conta = $_GET['conta'] ?? null;

try {
    $db = getDB();
    $sql = "
        SELECT 
            f.Id,
            DATE(f.DataBaixa) AS DataBaixa,
            cb.Nome AS Conta,
            c.Nome AS Categoria,
            f.Descricao,
            f.Tipo,
            f.ValorBruto,
            f.Taxas,
            f.ValorLiquido
        FROM FinanceiroLancamentos f
        LEFT JOIN FinanceiroContasBancarias cb ON cb.Id = f.ContaId
        LEFT JOIN FinanceiroCategorias c ON c.Id = f.CategoriaId
        WHERE f.Status = 'pago' 
        AND MONTH(f.DataBaixa) = :mes AND YEAR(f.DataBaixa) = :ano
    ";

    if (!empty($conta)) $sql .= " AND f.ContaId = :conta";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
    $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
    if (!empty($conta)) $stmt->bindValue(':conta', $conta, PDO::PARAM_INT);

    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $dados]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
