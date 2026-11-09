<?php
/* /public/admin/perfis/visualizar.php */

require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/autenticacao.php';

verificarPermissao('admin.perfis');

$perfilId = $_GET['id'] ?? null;

$db = getDB();

$stmt = $db->prepare("SELECT * FROM Perfis WHERE id=?");
$stmt->execute([$perfilId]);

$perfil = $stmt->fetch();

$stmt = $db->prepare("
SELECT Id, Username, NomeCompleto
FROM Usuarios
WHERE perfil_id=?
ORDER BY NomeCompleto
");

$stmt->execute([$perfilId]);

$usuarios = $stmt->fetchAll();

$moduloAtual = 'admin.perfis';
$tituloPagina = 'Visualizar Perfil';
$tituloConteudo = $perfil['nome'];

require_once __DIR__ . '/../../templates/base_protected_v2.php';
?>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-id-badge me-2"></i>
                Informações
            </div>
            <div class="card-body">
                <p>
                    <strong>ID:</strong><br>
                    <?= $perfil['id'] ?>
                </p>
                <p>
                    <strong>Nome:</strong><br>
                    <?= htmlspecialchars($perfil['nome']) ?>
                </p>
                <p>
                    <strong>Descrição:</strong><br>
                    <?= htmlspecialchars($perfil['descricao'] ?? '-') ?>
                </p>
            </div>
            <div class="card-footer">
                <a href="editar.php?id=<?= $perfil['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>
                    Editar
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-users me-2"></i>
                Usuários com este perfil
            </div>
            <div class="card-body">
                <?php if ($usuarios): ?>
                    <ul class="list-group">
                        <?php foreach ($usuarios as $u): ?>
                            <li class="list-group-item">
                                <a href="../usuarios/visualizar.php?id=<?= $u['Id'] ?>">
                                    <?= htmlspecialchars($u['NomeCompleto']) ?>
                                    <span class="text-muted">
                                        (
                                        <?= htmlspecialchars($u['Username']) ?>)
                                    </span>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        Nenhum usuário utiliza este perfil
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<script src="/js/admin.js"></script>
<?php require_once __DIR__ . '/../../templates/footer_protected_v2.php'; ?>