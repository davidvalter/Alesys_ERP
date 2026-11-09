<?php
/* /public/admin/perfis/index.php */

$moduloAtual = 'admin.perfis';
$tituloPagina = 'Gerenciamento de Perfis';
$tituloConteudo = 'Lista de Perfis';

require_once __DIR__ . '/../../templates/base_protected.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Perfis do Sistema</h3>
        <div class="card-tools">
            <a href="criar.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Perfil
            </a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db = getDB();
                $perfis = $db->query("SELECT * FROM Perfis ORDER BY nome")->fetchAll();
                
                foreach ($perfis as $perfil): ?>
                <tr>
                    <td><?= $perfil['id'] ?></td>
                    <td><?= htmlspecialchars($perfil['nome']) ?></td>
                    <td><?= htmlspecialchars($perfil['descricao'] ?? '') ?></td>
                    <td>
                        <a href="editar.php?id=<?= $perfil['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="visualizar.php?id=<?= $perfil['id'] ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/admin_footer.php'; ?>