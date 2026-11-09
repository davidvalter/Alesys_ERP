<?php
/* /public/admin/perfis/visualizar.php */

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
    
    // Perfil
    $stmtPerfil = $db->prepare("SELECT * FROM Perfis WHERE id = ?");
    if (!$stmtPerfil->execute([$perfilId])) {
        throw new Exception("Falha ao buscar perfil");
    }
    $perfil = $stmtPerfil->fetch();
    
    if (!$perfil) {
        throw new Exception("Perfil não encontrado");
    }
    
    // Usuários
    $stmtUsuarios = $db->prepare("SELECT Id, Username, NomeCompleto FROM Usuarios WHERE perfil_id = ? ORDER BY NomeCompleto");
    if (!$stmtUsuarios->execute([$perfilId])) {
        throw new Exception("Falha ao buscar usuários");
    }
    $usuarios = $stmtUsuarios->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
    header('Location: ' . admin_url('perfis/'));
    exit();
}

$tituloPagina = 'Visualizar Perfil';
$tituloConteudo = htmlspecialchars($perfil['nome']);
require_once __DIR__ . '/../../templates/admin_header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informações do Perfil</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">ID:</dt>
                    <dd class="col-sm-8"><?= $perfil['id'] ?></dd>
                    
                    <dt class="col-sm-4">Nome:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($perfil['nome']) ?></dd>
                    
                    <dt class="col-sm-4">Descrição:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($perfil['descricao'] ?? 'Nenhuma descrição') ?></dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="<?= admin_url('perfis/editar.php?id=' . $perfil['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Usuários com este Perfil</h3>
            </div>
            <div class="card-body">
                <?php if (count($usuarios) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($usuarios as $usuario): ?>
                        <li class="list-group-item">
                            <a href="<?= admin_url('usuarios/visualizar.php?id=' . $usuario['Id']) ?>">
                                <?= htmlspecialchars($usuario['NomeCompleto']) ?> (<?= htmlspecialchars($usuario['Username']) ?>)
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info">Nenhum usuário com este perfil</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/admin_footer.php'; ?>