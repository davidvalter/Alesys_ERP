<?php
/* /public/admin/perfis/criar.php */

require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/autenticacao.php';

verificarPermissao('admin.perfis');

$moduloAtual = 'admin.perfis';
$tituloPagina = 'Novo Perfil';
$tituloConteudo = 'Criar Perfil';

require_once __DIR__ . '/../../templates/base_protected_v2.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $db = getDB();
        $stmt = $db->prepare("
        INSERT INTO Perfis (nome, descricao)
        VALUES (?,?)
        ");

        $stmt->execute([
            $_POST['nome'],
            $_POST['descricao'] ?? null
        ]);

        $_SESSION['sucesso'] = 'Perfil criado com sucesso!';

        header("Location: index.php");
        exit;

    } catch (PDOException $e) {

        $_SESSION['erro'] = $e->getMessage();

    }
}
?>

<div class="card shadow-sm">
    <div class="card-header">
        <i class="fas fa-plus me-2"></i>
        Novo Perfil
    </div>
    <form method="post">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nome do Perfil</label>
                <input type="text" name="nome" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="3"></textarea>
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