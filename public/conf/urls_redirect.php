<?php
// public/conf/urls_redirect.php
function url($path = '') {
    $base = '/'; // Altere se seu sistema estiver em subdiretório
    return $base . ltrim($path, '/');
}

function admin_url($path = '') {
    return url('admin/' . ltrim($path, '/'));
}

function compras_url($path = '') {
    return url('compras/' . ltrim($path, '/'));
}

function vendas_url($path = '') {
    return url('vendas/' . ltrim($path, '/'));
}

function estoque_url($path = '') {
    return url('estoque/' . ltrim($path, '/'));
}

function financeiro_url($path = '') {
    return url('financeiro/' . ltrim($path, '/'));
}
function relatorios_url($path = '') {
    return url('relatorios/' . ltrim($path, '/'));
}
?>