<?php
// public/api/get_ncm.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    $termo = trim($_GET['termo'] ?? '');
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $limite = 20; // 20 resultados por página
    $offset = ($pagina - 1) * $limite;

    // Monta a condição de busca (código ou descrição)
    $where = '';
    $params = [];
    if ($termo !== '') {
        $where = "WHERE Codigo LIKE ? OR Descricao LIKE ?";
        $like = '%' . $termo . '%';
        $params[] = $like;
        $params[] = $like;
    }

    // Total de resultados (para paginação)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ProdutosNCM $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPaginas = (int)ceil($total / $limite);

    // Busca paginada
    $stmt = $pdo->prepare("
        SELECT Codigo, Descricao
        FROM ProdutosNCM
        $where
        ORDER BY Codigo
        LIMIT $limite OFFSET $offset
    ");
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'dados' => $dados,
        'total' => $total,
        'pagina' => $pagina,
        'totalPaginas' => $totalPaginas
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>