<?php
/* /public/admin/usuarios/modal_form.php */

require_once __DIR__ . '/../../../configs/database.php';

$db = getDB();

$id = $_GET['id'] ?? null;
$usuario = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM Usuarios WHERE Id=?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
}

$perfis = $db->query("SELECT id,nome FROM Perfis ORDER BY nome")->fetchAll();

?>

<input type="hidden" name="id" value="<?= $usuario['Id'] ?? '' ?>">

<div class="row g-3">

    <div class="col-md-4">
        <label class="form-label">Usuário</label>
        <input type="text" class="form-control" name="Username" required value="<?= $usuario['Username'] ?? '' ?>">
    </div>

    <div class="col-md-8">
        <label class="form-label">Nome Completo</label>
        <input type="text" class="form-control" name="NomeCompleto" required
            value="<?= $usuario['NomeCompleto'] ?? '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Senha</label>
        <input type="password" class="form-control" name="Password"
            placeholder="<?= $usuario ? 'Deixe vazio para manter' : '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Perfil</label>
        <select name="perfil_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($perfis as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($usuario['perfil_id'] ?? null) == $p['id'] ? 'selected' : '' ?>>
                    <?= $p['nome'] ?>
                </option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="col-md-4">
        <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" name="Ativo" <?= ($usuario['Ativo'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label">
                Usuário ativo
            </label>
        </div>
    </div>
</div>