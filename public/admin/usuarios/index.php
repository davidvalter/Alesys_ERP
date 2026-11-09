<?php
/* /public/admin/usuarios/index.php */

$moduloAtual = 'admin.usuarios';
$tituloPagina = 'Usuários';
$tituloConteudo = 'Gerenciamento de Usuários';

require_once __DIR__ . '/../../templates/base_protected_v2.php';

$db = getDB();

$usuarios = $db->query("
    SELECT 
        u.Id,
        u.Username,
        u.NomeCompleto,
        u.Ativo,
        p.nome as perfil
    FROM Usuarios u
    JOIN Perfis p ON p.id = u.perfil_id
    ORDER BY u.NomeCompleto
    ")->fetchAll();

?>

<div class="card shadow-sm">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Usuários do Sistema
        </h5>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-plus me-1"></i>Novo Usuário
        </button>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabela-usuarios">
                <thead class="table-light">
                    <tr>
                        <th width="80">ID</th>
                        <th>Usuário</th>
                        <th>Nome</th>
                        <th width="160">Perfil</th>
                        <th width="120">Status</th>
                        <th width="140" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="text-muted">
                                <?= $u['Id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['Username']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['NomeCompleto']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['perfil']) ?>
                            </td>
                            <td>
                                <span
                                    class="badge <?= $u['Ativo'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                    <?= $u['Ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-light btn-editar" data-id="<?= $u['Id'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalUsuario">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="visualizar.php?id=<?= $u['Id'] ?>" class="btn btn-light">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button
                                        class="btn btn-sm btn-status <?= $u['Ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        data-id="<?= $u['Id'] ?>" data-status="<?= $u['Ativo'] ?>">
                                        <i class="fa fa-power-off"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- MODAL USUÁRIO -->

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="formUsuario" method="post" action="salvar.php">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTituloUsuario">
                        Novo Usuário
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body" id="modalBodyUsuario">
                    <!-- conteúdo carregado via AJAX -->
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>



<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/../../templates/footer_protected_v2.php'; ?>