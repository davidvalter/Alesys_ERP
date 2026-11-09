<?php
/* /public/relatorios/index.php */

session_start();

$moduloAtual = 'financeiro.relatorios';
$tituloPagina = 'Relatórios';
$tituloConteudo = 'Relatórios';

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

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/dre.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-success-soft">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="widget-content">
                    <h5>DRE</h5>
                    <p>Demonstração do Resultado mensal e anual.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/fluxo_caixa.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-success-soft">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="widget-content">
                    <h5>Fluxo de Caixa</h5>
                    <p>Entradas, saídas e saldo por período.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/registro_maquininhas.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-primary-soft">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="widget-content">
                    <h5>Registros das Maquininhas</h5>
                    <p>Vendas e pagamentos nas maquininhas.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/por_categoria.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-warning-soft">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="widget-content">
                    <h5>Por Categoria</h5>
                    <p>Análise por grupos de receitas e despesas.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/extrato_conta.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-info-soft">
                    <i class="fas fa-university"></i>
                </div>
                <div class="widget-content">
                    <h5>Extrato por Conta</h5>
                    <p>Movimentações por conta bancária.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/origem_lancamentos.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-danger-soft">
                    <i class="fas fa-file-import"></i>
                </div>
                <div class="widget-content">
                    <h5>Origem dos Lançamentos</h5>
                    <p>Rastreamento por origem financeira.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/pendencias.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-warning-soft">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="widget-content">
                    <h5>Pendências</h5>
                    <p>Lançamentos em aberto ou vencidos.</p>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="/relatorios/conciliacao_bancaria.php" class="card widget-card text-decoration-none">
                <div class="widget-icon bg-success">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="widget-content">
                    <h5>Conciliação Bancária</h5>
                    <p>Conferência entre OFX e lançamentos.</p>
                </div>
            </a>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>