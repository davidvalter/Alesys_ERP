<?php
/* /public/admin/permissoes/index.php */

$moduloAtual = 'admin.permissoes';
$tituloPagina = 'Gerenciamento de Permissões';
$tituloConteudo = 'Permissões por Perfil';

require_once __DIR__ . '/../../templates/base_protected_v2.php';

$db = getDB();
$perfis = $db->query("SELECT * FROM Perfis ORDER BY nome")->fetchAll();
?>

<div class="card shadow-sm">

    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-user-shield me-2"></i>
            Perfis do Sistema
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Perfil</th>
                        <th width="180">Módulos com Acesso</th>
                        <th width="140" class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($perfis as $perfil):
                        $stmt = $db->prepare("
                            SELECT COUNT(*) 
                            FROM Permissoes 
                            WHERE perfil_id = ?
                        ");
                        $stmt->execute([$perfil['id']]);
                        $totalPermissoes = $stmt->fetchColumn();
                    ?>

                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($perfil['nome']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">
                                    <?= $totalPermissoes ?> módulo(s)
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="gerenciar.php?perfil_id=<?= $perfil['id'] ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-sliders-h me-1"></i>
                                    Gerenciar

                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/js/admin.js"></script>
<?php require_once __DIR__ . '/../../templates/footer_protected_v2.php'; ?>