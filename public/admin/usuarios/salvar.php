<?php
/* /public/admin/usuarios/salvar.php */

require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../../configs/segredos.php';

$db = getDB();

$id = $_POST['id'] ?? null;

$username = $_POST['Username'];
$nome = $_POST['NomeCompleto'];
$perfil = $_POST['perfil_id'];
$ativo = isset($_POST['Ativo']) ? 1 : 0;

if ($id) {

    if (!empty($_POST['Password'])) {

        $salt = bin2hex(random_bytes(32));
        $senha = $_POST['Password'];

        $hash = hash_hmac('sha256', $senha, CHAVE_SECRETA . $salt);

        $stmt = $db->prepare("
        UPDATE Usuarios
        SET Username=?, NomeCompleto=?, Senha=?, Salt=?, perfil_id=?, Ativo=?
        WHERE Id=?
        ");

        $stmt->execute([$username, $nome, $hash, $salt, $perfil, $ativo, $id]);

    } else {

        $stmt = $db->prepare("
        UPDATE Usuarios
        SET Username=?, NomeCompleto=?, perfil_id=?, Ativo=?
        WHERE Id=?
        ");

        $stmt->execute([$username, $nome, $perfil, $ativo, $id]);
    }

}

header("Location: index.php");
exit;