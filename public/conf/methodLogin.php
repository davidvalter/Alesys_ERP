<?php
//public/conf/methodLogin.php

session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../configs/segredos.php';
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/autenticacao.php';

function responderErro($mensagem, $detalhes = [])
{
    $_SESSION['login_erro'] = $mensagem;

    // Log do erro
    $log = date('[Y-m-d H:i:s] ') . 'ERRO LOGIN: ' . $mensagem;
    if (!empty($detalhes)) {
        $log .= ' - ' . json_encode($detalhes);
    }
    file_put_contents(__DIR__ . '/../logs/auth.log', $log . PHP_EOL, FILE_APPEND);

    header('Location: ../login.php');
    exit();
}

// Verifica método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErro('Método inválido.');
}

// Validações básicas
if (empty($_POST['Username'])) {
    responderErro('Por favor, informe o usuário.');
}

if (empty($_POST['Password'])) {
    responderErro('Por favor, informe a senha.');
}

$username = trim($_POST['Username']);
$password = $_POST['Password'];

try {
    $db = getDB();

    // Consulta otimizada
    $stmt = $db->prepare("
        SELECT 
            u.Id, u.Username, u.NomeCompleto, u.Senha, u.Salt, 
            u.perfil_id, p.nome AS perfil_nome
        FROM Usuarios u
        JOIN Perfis p ON u.perfil_id = p.id
        WHERE u.Username = :username AND u.Ativo = 1
        LIMIT 1
    ");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        responderErro('Credenciais inválidas.', ['username' => $username]);
    }

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificação segura da senha
    $hashCalculado = hash_hmac('sha256', $password, CHAVE_SECRETA . $usuario['Salt']);
    if (!hash_equals($usuario['Senha'], $hashCalculado)) {
        responderErro('Credenciais inválidas.');
    }

    // Carrega permissões
    $stmtPermissoes = $db->prepare("
    SELECT m.identificador 
    FROM Permissoes p
    JOIN Modulos m ON p.modulo_id = m.id
    WHERE p.perfil_id = :perfil_id
    ");
    
    $stmtPermissoes->bindParam(':perfil_id', $usuario['perfil_id'], PDO::PARAM_INT);
    $stmtPermissoes->execute();
    $permissoes = $stmtPermissoes->fetchAll(PDO::FETCH_COLUMN, 0);

    // Cria sessão segura
    $_SESSION['Usuario'] = [
        'id' => $usuario['Id'],
        'username' => $usuario['Username'],
        'nome' => $usuario['NomeCompleto'],
        'perfil_id' => $usuario['perfil_id'],
        'perfil_nome' => $usuario['perfil_nome'],
        'logado' => true,
        'permissoes' => $permissoes
    ];

    // Atualiza último login
    $db->prepare("UPDATE Usuarios SET UltimoLogin = NOW() WHERE Id = ?")
        ->execute([$usuario['Id']]);

    // Redireciona para dashboard
    header('Location: ../dashboard.php');
    exit();

} catch (PDOException $e) {
    responderErro('Erro no sistema. Por favor, tente novamente.', [
        'exception' => $e->getMessage()
    ]);
}