<?php
/* /public/admin/permissoes/salvar_permissoes.php */

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/autenticacao.php';
verificarPermissao('admin.permissoes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$perfilId = $_POST['perfil_id'] ?? null;
$modulosSelecionados = $_POST['modulos'] ?? [];

if (!$perfilId || !is_numeric($perfilId)) {
    $_SESSION['erro'] = 'Perfil não especificado';
    header('Location: index.php');
    exit();
}

try {
    $db = getDB();
    
    // Inicia transação
    $db->beginTransaction();
    
    // Remove todas as permissões atuais
    $stmtDelete = $db->prepare("DELETE FROM Permissoes WHERE perfil_id = ?");
    $stmtDelete->execute([$perfilId]);
    
    // Adiciona as novas permissões
    if (!empty($modulosSelecionados)) {
        $stmtInsert = $db->prepare("INSERT INTO Permissoes (perfil_id, modulo_id) VALUES (?, ?)");
        foreach ($modulosSelecionados as $moduloId) {
            if (is_numeric($moduloId)) {
                $stmtInsert->execute([$perfilId, $moduloId]);
            }
        }
    }
    
    // Confirma a transação
    $db->commit();
    
    $_SESSION['sucesso'] = 'Permissões atualizadas com sucesso!';
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['erro'] = 'Erro ao salvar permissões: ' . $e->getMessage();
} catch (Exception $e) {
    $_SESSION['erro'] = 'Erro no sistema: ' . $e->getMessage();
}

header("Location: gerenciar.php?perfil_id=$perfilId");
exit();