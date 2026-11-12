<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../../includes/ImageMediaStorage.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $pdo = getDB();
    if (empty($input['Codigo']) || (int)$input['Codigo'] < 1) {
        throw new InvalidArgumentException('Código do produto é obrigatório.');
    }
    $codigo = (int)$input['Codigo'];

    $pdo->beginTransaction();
    $stmtMedia = $pdo->prepare('SELECT ImagemId FROM Produtos WHERE Codigo = ?');
    $stmtMedia->execute([$codigo]);
    $imageId = $stmtMedia->fetchColumn();
    if ($imageId === false) {
        throw new RuntimeException('Produto não encontrado.');
    }

    $pdo->prepare('DELETE FROM ProdutoPrecos WHERE fk_Produtos_Codigo = ?')->execute([$codigo]);
    $pdo->prepare('DELETE FROM NFeItens WHERE fk_Produtos_Codigo = ?')->execute([$codigo]);
    $stmt = $pdo->prepare('DELETE FROM Produtos WHERE Codigo = ?');
    $stmt->execute([$codigo]);
    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Produto não encontrado.');
    }
    $pdo->commit();

    if ($imageId !== null) {
        try {
            ImageMediaStorage::deleteIfUnused($pdo, (int)$imageId);
        } catch (Throwable $cleanupError) {
            error_log('Não foi possível limpar a mídia do produto ' . $codigo . ': ' . $cleanupError->getMessage());
        }
    }

    echo json_encode(['success' => true, 'message' => 'Produto excluído com sucesso!'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao excluir produto: ' . $e->getMessage());
    $isUserError = $e instanceof InvalidArgumentException;
    http_response_code($isUserError ? 400 : 500);
    echo json_encode([
        'success' => false,
        'message' => $isUserError ? $e->getMessage() : 'Não foi possível excluir o produto.'
    ], JSON_UNESCAPED_UNICODE);
}
