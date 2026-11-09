<?php
// atualizar_admin.php
require_once '../configs/segredos.php';

$db_host = 'localhost';
$db_name = 'alesys';
$db_user = 'Alesys';
$db_pass = 'alesys#pass';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Gerar novo salt
    $novo_salt = bin2hex(random_bytes(32));
    
    // Nova senha (substitua 'admin123' pela senha desejada)
    $nova_senha = 'admin123';
    
    // Criar hash seguro
    $senha_hash = hash_hmac('sha256', $nova_senha, CHAVE_SECRETA . $novo_salt);
    
    // Atualizar o admin
    $stmt = $conn->prepare("UPDATE Usuarios SET Senha = ?, Salt = ? WHERE Username = 'admin'");
    $stmt->execute([$senha_hash, $novo_salt]);
    
    echo "Admin atualizado com sucesso! Senha: " . $nova_senha;
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}