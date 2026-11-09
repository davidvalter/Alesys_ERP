<?php
// public/api/get_prato.php
require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID do prato não informado.");
    }
    $id = intval($_GET['id']);
    $db = getDB();
    $sql = "SELECT Id, Nome, Categoria, Preco, Foto, Status,
                   NCM, CEST, CFOP, CSOSN, UnidadeMedida
            FROM Pratos
            WHERE Id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $prato = $stmt->fetch();
    if (!$prato) {
        throw new Exception("Prato não encontrado.");
    }
    echo json_encode(['success' => true, 'data' => $prato]);
    exit;
} catch (Exception $e) {
    logError("Erro ao buscar prato: " . $e->getMessage(), ['get' => $_GET]);
    echo json_encode(['success' => false, 'error' => 'Erro interno ao buscar prato.']);
    exit;
}