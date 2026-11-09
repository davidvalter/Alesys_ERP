<?php
/* /public/admin/perfis/index.php */

$moduloAtual = 'admin.perfis';
$tituloPagina = 'Gerenciamento de Perfis';
$tituloConteudo = 'Perfis do Sistema';

require_once __DIR__ . '/../../templates/base_protected_v2.php';

$db = getDB();

$perfis = $db->query("
SELECT p.*, 
       (SELECT COUNT(*) FROM Usuarios u WHERE u.perfil_id = p.id) AS total_usuarios
FROM Perfis p
ORDER BY nome
")->fetchAll();
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-id-badge me-2"></i>
            Perfis cadastrados
        </h5>
        <a href="criar.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i>
            Novo Perfil
        </a>

    </div>

    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:80px">ID</th>
                    <th>Perfil</th>
                    <th>Descrição</th>
                    <th style="width:150px">Usuários</th>
                    <th style="width:150px">Ações</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($perfis as $perfil): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary">
                                <?= $perfil['id'] ?>
                            </span>
                        </td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars($perfil['nome']) ?>
                        </td>

                        <td class="text-muted">
                            <?= htmlspecialchars($perfil['descricao'] ?? '-') ?>
                        </td>

                        <td>
                            <span class="badge bg-info-subtle text-info">
                                <?= $perfil['total_usuarios'] ?> usuário(s)
                            </span>
                        </td>

                        <td>
                            <a href="visualizar.php?id=<?= $perfil['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="editar.php?id=<?= $perfil['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<script src="/js/admin.js"></script>
<?php require_once __DIR__ . '/../../templates/footer_protected_v2.php'; ?>