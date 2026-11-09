<?php
/* /public/vendas/index.php */

session_start();

$moduloAtual = 'vendas.dashboard';
$tituloPagina = 'Vendas - Dashboard';
$tituloConteudo = 'Vendas';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

// =========================
// Valida módulo
// =========================
if (!isset($moduloAtual)) {
    die('Módulo não definido na página');
}

// =========================
// Verifica permissão
// =========================
verificarPermissao($moduloAtual);

// =========================
// Valida usuário
// =========================
$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}
?>

<div class="container-fluid">

    <div class="row g-4">

        <!-- PDV -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/vendas/pdv.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-primary-soft">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="widget-content">
                    <h5>Ponto de Venda</h5>
                    <p>Realize suas vendas em tempo real ou frente de Caixa, e faça a emissão de notas fiscais.
                    </p>
                </div>
            </a>
        </div>

        <!-- Cadastro de Pratos -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/vendas/cadastro_pratos.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-success-soft">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="widget-content">
                    <h5>Cadastro de Pratos</h5>
                    <p>Gerencie pratos, receitas e composição dos custos.</p>
                </div>
            </a>
        </div>

        <!-- Cardápio -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/vendas/cardapio.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-info-soft">
                    <i class="fas fa-list"></i>
                </div>
                <div class="widget-content">
                    <h5>Cardápio</h5>
                    <p>Gerencie os itens disponíveis para venda no PDV.</p>
                </div>
            </a>
        </div>

        <!-- Cadastro de Insumos -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/compras/produtos.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-warning-soft">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="widget-content">
                    <h5>Cadastro de Insumos</h5>
                    <p>Controle de produtos, estoque e entradas.</p>
                </div>
            </a>
        </div>

        <!-- Lançamento Manual -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/vendas/lancamento_manual_vendas.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-primary-soft">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="widget-content">
                    <h5>Lançamento Manual</h5>
                    <p>Registro de vendas fora do PDV.</p>
                </div>
            </a>
        </div>

        <!-- Fechamento de Caixa -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/vendas/fechamento_caixa.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-success">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="widget-content">
                    <h5>Fechamento de Caixa</h5>
                    <p>Consolidação diária das vendas.</p>
                </div>
            </a>
        </div>

        <!-- Histórico -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/vendas/historico.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-info-soft">
                    <i class="fas fa-history"></i>
                </div>
                <div class="widget-content">
                    <h5>Histórico de Vendas</h5>
                    <p>Consulta e auditoria das vendas.</p>
                </div>
            </a>
        </div>

        <!-- Movimentações -->
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/estoque/movimentacoes.php?origem=vendas" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-danger-soft">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="widget-content">
                    <h5>Movimentações</h5>
                    <p>Baixas automáticas e auditoria.</p>
                </div>
            </a>
        </div>

    </div>

</div>


<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>