<?php
// public/api/save_categorialancamentos.php
header('Content-Type: application/json; charset=utf-8');

// Impede que warnings/notices do PHP apareçam no corpo da resposta e quebrem o JSON
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['Nome']) || empty($data['Tipo'])) {
        throw new Exception('Campos obrigatórios não informados.');
    }

    $id = isset($data['Id']) && $data['Id'] !== '' ? (int) $data['Id'] : null;
    $nome = trim($data['Nome']);
    $tipo = $data['Tipo'];
    $grupo = trim($data['Grupo'] ?? '');
    // DreLinhaId pode vir vazio (sem classificação DRE) ou com o Id da linha
    $dreLinhaId = isset($data['DreLinhaId']) && $data['DreLinhaId'] !== '' ? (int) $data['DreLinhaId'] : null;

    $db->beginTransaction();

    // ---------- Categoria ----------
    if ($id) {
        // Atualizar
        $sql = "UPDATE FinanceiroCategorias SET Nome = :nome, Tipo = :tipo, Grupo = :grupo WHERE Id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':nome' => $nome, ':tipo' => $tipo, ':grupo' => $grupo, ':id' => $id]);
        $msg = 'Categoria atualizada com sucesso.';
    } else {
        // Inserir
        $sql = "INSERT INTO FinanceiroCategorias (Nome, Tipo, Grupo, Ativo) VALUES (:nome, :tipo, :grupo, 1)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':nome' => $nome, ':tipo' => $tipo, ':grupo' => $grupo]);
        $id = (int) $db->lastInsertId();
        $msg = 'Categoria criada com sucesso.';
    }

    // ---------- Vínculo com a DRE ----------
    if ($dreLinhaId) {
        // Valida existência da linha antes de gravar
        $chk = $db->prepare("SELECT COUNT(*) FROM DreLinhas WHERE Id = :id AND Ativo = 1");
        $chk->execute([':id' => $dreLinhaId]);
        if (!$chk->fetchColumn()) {
            throw new Exception('Linha da DRE inválida.');
        }

        $sql = "INSERT INTO DreCategoriaMap (CategoriaId, DreLinhaId)
                VALUES (:cat, :linha)
                ON DUPLICATE KEY UPDATE DreLinhaId = VALUES(DreLinhaId)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':cat' => $id, ':linha' => $dreLinhaId]);
    } else {
        // Sem linha selecionada: remove vínculo anterior, se existir
        $stmt = $db->prepare("DELETE FROM DreCategoriaMap WHERE CategoriaId = :cat");
        $stmt->execute([':cat' => $id]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => $msg], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Erro save_categorialancamentos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}