<?php
$nome = $_SESSION['Usuario']['nome'] ?? 'Usuário';
$perfil = $_SESSION['Usuario']['perfil_nome'] ?? 'Perfil';
$hora = (int)date('H');

$saudacao = match (true) {
    $hora < 12 => 'Bom dia',
    $hora < 18 => 'Boa tarde',
    default => 'Boa noite'
};
?>

<div class="card dashboard-welcome shadow-sm">
    <div>
        <h4><?= $saudacao ?>, <?= htmlspecialchars($nome) ?> 👋</h4>
        <small class="text-muted">Perfil: <?= htmlspecialchars($perfil) ?></small>
    </div>
</div>