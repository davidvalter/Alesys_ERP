<?php
// public/api/get_prato.php
require_once __DIR__ . '/../../configs/database.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_GET['id'])) {
        throw new InvalidArgumentException('ID do prato não informado.');
    }
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('ID do prato inválido.');
    }

    $pdo = getDB();
    $sql = "SELECT pr.Id, pr.Nome, pr.Categoria, pr.Preco, pr.Status,
                   pr.NCM, pr.CEST, pr.CFOP, pr.CSOSN, pr.UnidadeMedida,
                   m.CaminhoMiniatura AS Foto
            FROM Pratos pr
            LEFT JOIN Midias m ON m.Id = pr.ImagemId
            WHERE pr.Id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $prato = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prato) {
        throw new RuntimeException('Prato não encontrado.');
    }

    echo json_encode(['success' => true, 'data' => $prato], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Erro interno ao buscar prato.'
    ], JSON_UNESCAPED_UNICODE);
}
