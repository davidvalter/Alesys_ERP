<?php
/* /public/admin/perfis/editar.php */

ob_start();
require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/urls_redirect.php';
require_once __DIR__ . '/../../conf/autenticacao.php';
verificarPermissao('admin.perfis');

$perfilId = $_GET['id'] ?? null;
if (!$perfilId || !is_numeric($perfilId)) {
    header('Location: ' . admin_url('perfis/'));
    exit();
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM Perfis WHERE id = ?");
    $stmt->execute([$perfilId]);
    $perfil = $stmt->fetch();
    
    if (!$perfil) {
        $_SESSION['erro'] = 'Perfil não encontrado';
        header('Location: ' . admin_url('perfis/'));
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao carregar perfil: ' . $e->getMessage();
    header('Location: ' . admin_url('perfis/'));
    exit();
}

$tituloPagina = 'Editar Perfil';
$tituloConteudo = 'Editar: ' . htmlspecialchars($perfil['nome']);
require_once __DIR__ . '/../../templates/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("UPDATE Perfis SET nome = ?, descricao = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nome'],
            $_POST['descricao'] ?? null,
            $perfilId
        ]);
        
        $_SESSION['sucesso'] = 'Perfil atualizado com sucesso!';
        header('Location: ' . admin_url('perfis/'));
        exit();
    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro ao atualizar perfil: ' . $e->getMessage();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Editar Perfil</h3>
    </div>
    <form method="post">
        <div class="card-body">
            <div class="form-group">
                <label for="nome">Nome do Perfil</label>
                <input type="text" class="form-control" id="nome" name="nome" 
                       value="<?= htmlspecialchars($perfil['nome']) ?>" required>
            </div>
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= 
                    htmlspecialchars($perfil['descricao'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= admin_url('perfis/') ?>" class="btn btn-default">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../templates/admin_footer.php'; ?>