<?php
/* /public/admin/perfis/editar.php */

require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/autenticacao.php';

verificarPermissao('admin.perfis');

$perfilId = $_GET['id'] ?? null;

if (!$perfilId) {
    header("Location: index.php");
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM Perfis WHERE id=?");
$stmt->execute([$perfilId]);

$perfil = $stmt->fetch();

if (!$perfil) {
    $_SESSION['erro'] = 'Perfil não encontrado';
    header("Location: index.php");
    exit;
}

$moduloAtual = 'admin.perfis';
$tituloPagina = 'Editar Perfil';
$tituloConteudo = 'Editar: ' . $perfil['nome'];

require_once __DIR__ . '/../../templates/base_protected_v2.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $stmt = $db->prepare("
        UPDATE Perfis
        SET nome=?, descricao=?
        WHERE id=?
        ");

        $stmt->execute([
            $_POST['nome'],
            $_POST['descricao'] ?? null,
            $perfilId
        ]);

        $_SESSION['sucesso'] = 'Perfil atualizado com sucesso';

        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['erro'] = $e->getMessage();
    }

}
?>

<div class="card shadow-sm">
    <div class="card-header">
        <i class="fas fa-edit me-2"></i>
        Editar Perfil
    </div>
    <form method="post">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($perfil['nome']) ?>"
                    required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control"
                    rows="3"><?= htmlspecialchars($perfil['descricao']) ?></textarea>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>
                Salvar
            </button>
        </div>
    </form>
</div>

<script src="/js/admin.js"></script>
<?php require_once __DIR__ . '/../../templates/footer_protected_v2.php'; ?>