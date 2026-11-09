<?php
// public/api/get_categoriaslancamentos.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $tipo = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null;
    $ativo = isset($_GET['ativo']) && $_GET['ativo'] !== '' ? (int) $_GET['ativo'] : null;

    $sql = "SELECT c.Id, c.Nome, c.Tipo, c.Grupo, c.Ativo, m.DreLinhaId, l.Nome AS DreLinhaNome
            FROM FinanceiroCategorias c
            LEFT JOIN DreCategoriaMap m ON m.CategoriaId = c.Id
            LEFT JOIN DreLinhas l ON l.Id = m.DreLinhaId AND l.Ativo = 1
            WHERE 1=1";
    $params = [];

    if ($tipo) {
        $sql .= " AND c.Tipo = :tipo";
        $params[':tipo'] = $tipo;
    }

    if ($ativo !== null) {
        $sql .= " AND c.Ativo = :ativo";
        $params[':ativo'] = $ativo;
    }

    $sql .= " ORDER BY c.Nome ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Linhas da DRE ativas (para o select do modal)
    $linhasDre = $db->query(
        "SELECT Id, Nome FROM DreLinhas WHERE Ativo = 1 ORDER BY Ordem"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(
        ['success' => true, 'data' => $rows, 'linhasDre' => $linhasDre],
        JSON_UNESCAPED_UNICODE
    );
} catch (Exception $e) {
    error_log('Erro get_categoriaslancamentos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar categorias.']);
}