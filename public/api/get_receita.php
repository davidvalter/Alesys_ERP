<?php
// public/api/get_receita.php

require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_GET['pratoId'])) {
        throw new Exception("ID do prato não informado.");
    }

    $pratoId = intval($_GET['pratoId']);
    $db = getDB();

    $sql = "SELECT r.Id, r.IngredienteId, p.Nome as IngredienteNome, r.Quantidade, r.UnidadeMedida, r.PercentualPerda, r.MetodoBaixa, r.Ativo
            FROM Receitas r
            JOIN Produtos p ON r.IngredienteId = p.Codigo
            WHERE r.PratoId = ? AND r.Ativo = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$pratoId]);

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;

} catch (Exception $e) {
    logError("Erro ao buscar receita: " . $e->getMessage(), ['get' => $_GET]);
    echo json_encode(['success' => false, 'error' => 'Erro interno ao buscar receita.']);
}
