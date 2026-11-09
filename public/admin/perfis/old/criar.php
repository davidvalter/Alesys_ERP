<?php
/* /public/admin/perfis/criar.php */

require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/urls_redirect.php';
require_once __DIR__ . '/../../conf/autenticacao.php';
verificarPermissao('admin.perfis');

$tituloPagina = 'Criar Novo Perfil';
$tituloConteudo = 'Adicionar Perfil';
require_once __DIR__ . '/../../templates/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Perfis (nome, descricao) VALUES (?, ?)");
        $stmt->execute([
            $_POST['nome'],
            $_POST['descricao'] ?? null
        ]);
        
        $_SESSION['sucesso'] = 'Perfil criado com sucesso!';
        header('Location: ' . admin_url('perfis/'));
        exit();
    } catch (PDOException $e) {
        $_SESSION['erro'] = 'Erro ao criar perfil: ' . $e->getMessage();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Novo Perfil</h3>
    </div>
    <form method="post">
        <div class="card-body">
            <div class="form-group">
                <label for="nome">Nome do Perfil</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= admin_url('perfis/') ?>" class="btn btn-default">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../templates/admin_footer.php'; ?>