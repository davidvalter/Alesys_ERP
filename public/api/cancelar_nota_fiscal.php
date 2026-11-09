<?php
/* /public/api/cancelar_nota_fiscal.php */

header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();

    if (empty($input['Id'])) {
        throw new Exception('ID da nota fiscal é obrigatório');
    }

    $stmt = $pdo->prepare("SELECT Status FROM NFe WHERE Id = ?");
    $stmt->execute([$input['Id']]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota) {
        throw new Exception('Nota fiscal não encontrada');
    }

    // 🔥 alterna status
    $novoStatus = $nota['Status'] == 1 ? 0 : 1;
    $acao = $novoStatus == 1 ? 'reativada' : 'desativada';

    $stmtUpdate = $pdo->prepare("UPDATE NFe SET Status = ? WHERE Id = ?");
    $success = $stmtUpdate->execute([$novoStatus, $input['Id']]);

    if (!$success) {
        throw new Exception("Erro ao {$acao} nota fiscal");
    }

    echo json_encode([
        'success' => true,
        'message' => "Nota fiscal {$acao} com sucesso!",
        'novoStatus' => $novoStatus
    ]);

} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados'
    ]);
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}