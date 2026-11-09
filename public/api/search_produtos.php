<?php
// api/search_produtos.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    
    // Validação dos parâmetros
    $searchTerm = $_GET['search'] ?? '';
    $searchTerm = trim(htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'));
    $page = max(1, (int)($_GET['page'] ?? 1));

    // Configuração de paginação
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // CONSULTA PRINCIPAL - Versão com trim para evitar espaços
    $sql = "
        SELECT 
            Codigo as id,
            TRIM(Nome) as text,  -- Aplicando TRIM diretamente no SQL
            UnidadeMedida
        FROM Produtos 
        WHERE Ativo = 1 
        AND (Nome LIKE ? OR CodigoBarras LIKE ?)
        ORDER BY Nome
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $searchParam = "%$searchTerm%";
    $stmt->bindValue(1, $searchParam, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchParam, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new PDOException("Falha ao executar consulta principal");
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CONSULTA DE CONTAGEM
    $countSql = "SELECT COUNT(*) as total FROM Produtos WHERE Ativo = 1 AND (Nome LIKE ? OR CodigoBarras LIKE ?)";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindValue(1, $searchParam, PDO::PARAM_STR);
    $countStmt->bindValue(2, $searchParam, PDO::PARAM_STR);
    
    if (!$countStmt->execute()) {
        throw new PDOException("Falha ao executar consulta de contagem");
    }
    
    $total = $countStmt->fetchColumn();

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'results' => array_map(function($item) {
            // Garantindo que todos os campos estejam sem espaços extras
            return [
                'id' => $item['id'],
                'text' => trim($item['text']),  // Dupla verificação
                'UnidadeMedida' => isset($item['UnidadeMedida']) ? trim($item['UnidadeMedida']) : 'UN'
            ];
        }, $results),
        'pagination' => [
            'more' => ($page * $limit) < $total
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('PDOException in search_produtos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Exception in search_produtos.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Requisição inválida',
        'details' => $e->getMessage()
    ]);
}