<?php
// public/api/save_receita.php

require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    $pratoId = $_POST['pratoId'] ?? null;
    $ingredienteId = $_POST['ingredienteId'] ?? null;
    $quantidade = $_POST['quantidade'] ?? 0;
    $unidade = $_POST['unidade'] ?? '';
    $perda = $_POST['perda'] ?? 0;
    $metodo = $_POST['metodo'] ?? 'auto';
    $responsavelId = $_SESSION['user_id'] ?? 1;

    if (!$pratoId || !$ingredienteId || !$quantidade) {
        throw new Exception("Dados insuficientes para salvar a receita.");
    }

    $db = getDB();

    if ($id) {
        $sql = "UPDATE Receitas SET Quantidade=?, UnidadeMedida=?, PercentualPerda=?, MetodoBaixa=?, Ativo=1 WHERE Id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$quantidade, $unidade, $perda, $metodo, $id]);
    } else {
        $sql = "INSERT INTO Receitas (PratoId, IngredienteId, Quantidade, UnidadeMedida, PercentualPerda, MetodoBaixa, Ativo) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$pratoId, $ingredienteId, $quantidade, $unidade, $perda, $metodo]);
        $id = $db->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id]);
    exit;

} catch (Exception $e) {
    logError("Erro ao salvar receita: " . $e->getMessage(), [
        'post' => $_POST
    ]);
    echo json_encode(['success' => false, 'error' => 'Erro interno ao salvar receita.']);
}
