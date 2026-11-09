<?php
/* public/relatorios/registro_maquininhas.php */

session_start();

$moduloAtual = 'relatorios.registromaquininhas';
$tituloPagina = 'Relatório Matricial - Maquininhas';
$tituloConteudo = 'Relatório Matricial - Maquininhas';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

$dataInicial = date('Y-m-01');
$dataFinal   = date('Y-m-d');

include __DIR__ . '/../templates/base_protected_v2.php';
?>

<div class="container-fluid registro-maquininhas">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-3">
      <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
      <li class="breadcrumb-item active" aria-current="page">Registro Matricial de Maquininhas</li>
    </ol>
  </nav>

  <!-- Título -->
  <div class="page-header mb-4">
    <h4 class="fw-bold mb-1"><i class="fas fa-table me-2"></i>Relatório Matricial de Maquininhas</h4>
    <small class="text-muted">Visualização diária por operadora e meio de pagamento</small>
  </div>

  <!-- FILTROS -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header py-3 fw-semibold text-white bg-success">
      <i class="fas fa-filter me-2"></i>Filtros
    </div>
    <div class="card-body p-4">
      <form id="formFiltros">

        <!-- Linha 1 -->
        <div class="row g-3 mb-3">
          <div class="col-md-3 col-sm-6">
            <label for="dataInicial" class="form-label">Data Inicial</label>
            <input type="date" class="form-control" id="dataInicial" name="dataInicial"
                   value="<?= $dataInicial ?>" required>
          </div>
          <div class="col-md-3 col-sm-6">
            <label for="dataFinal" class="form-label">Data Final</label>
            <input type="date" class="form-control" id="dataFinal" name="dataFinal"
                   value="<?= $dataFinal ?>" required>
          </div>
          <div class="col-md-3 col-sm-6">
            <label for="operadora" class="form-label">Operadora</label>
            <select class="form-select" id="operadora" name="operadora">
              <option value="">Todas Operadoras</option>
              <option value="Stone">Stone</option>
              <option value="Rede">Rede</option>
              <option value="PagBank">PagBank</option>
              <option value="InfinityPay">InfinityPay</option>
              <option value="Sipag">Sipag</option>
              <option value="Cielo">Cielo</option>
              <option value="Getnet">Getnet</option>
              <option value="PagSeguro">PagSeguro</option>
              <option value="Dinheiro">Dinheiro</option>
            </select>
          </div>
          <div class="col-md-3 col-sm-6">
            <label for="statusCaixa" class="form-label">Status do Caixa</label>
            <select class="form-select" id="statusCaixa" name="statusCaixa">
              <option value="">Todos</option>
              <option value="fechado" selected>Fechado</option>
              <option value="aberto">Aberto</option>
            </select>
          </div>
        </div>

        <!-- Linha 2 -->
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label for="meioPagamento" class="form-label">Meio de Pagamento</label>
            <select class="form-select" id="meioPagamento" name="meioPagamento">
              <option value="">Todos</option>
              <option value="credito">Crédito</option>
              <option value="debito">Débito</option>
              <option value="voucher">Voucher</option>
              <option value="pix">Pix</option>
              <option value="deliveryapp">Delivery App</option>
              <option value="dinheiro">Dinheiro</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="responsavel" class="form-label">Responsável</label>
            <select class="form-select" id="responsavel" name="responsavelId">
              <option value="">Todos os Responsáveis</option>
            </select>
          </div>
          <div class="col-md-4">
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-success flex-fill">
                <i class="fas fa-search me-1"></i> Consultar
              </button>
              <button type="button" id="btnLimpar" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row dashboard-gerencial g-3 mb-4">
    <div class="col-xl-3 col-md-6">
      <div class="card card-kpi border-0 h-100">
        <div class="card-body">
          <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Faturamento Total</small>
          <h4 id="kpiFaturamento" class="fw-bold mt-2 mb-0">R$ 0,00</h4>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-kpi border-0 h-100">
        <div class="card-body">
          <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Ticket Médio Diário</small>
          <h4 id="kpiTicket" class="fw-bold mt-2 mb-0">R$ 0,00</h4>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-kpi border-0 h-100">
        <div class="card-body">
          <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Melhor Dia</small>
          <h4 id="kpiMelhorDia" class="fw-bold mt-2 mb-0">-</h4>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-kpi border-0 h-100">
        <div class="card-body">
          <small class="text-muted text-uppercase fw-semibold" style="font-size:0.75rem;">Operadora Líder</small>
          <h4 id="kpiOperadora" class="fw-bold mt-2 mb-0">-</h4>
        </div>
      </div>
    </div>
  </div>

  <!-- CHARTS -->
  <div class="charts-grid mb-4">
    <div class="chart-full">
      <div class="card chart-card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold border-bottom">Faturamento Diário</div>
        <div class="card-body p-3">
          <canvas id="chartFaturamento"></canvas>
        </div>
      </div>
    </div>
    <div class="chart-half">
      <div class="card chart-card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold border-bottom">Operadoras</div>
        <div class="card-body p-3">
          <canvas id="chartOperadoras"></canvas>
        </div>
      </div>
    </div>
    <div class="chart-half">
      <div class="card chart-card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold border-bottom">Meios de Pagamento</div>
        <div class="card-body p-3">
          <canvas id="chartMeios"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- BARRA DE FERRAMENTAS DA TABELA -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted small">Exibir</span>
      <select id="itensPorPagina" class="form-select form-select-sm" style="width:auto;">
        <option value="10">10</option>
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="0">Todos</option>
      </select>
      <span class="text-muted small">registros</span>
    </div>
    <small id="contadorRegistros" class="text-muted fw-medium">Nenhum registro carregado</small>
  </div>

  <!-- TABELA -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-0">
      <div class="tabela-scroll-wrapper">
        <div class="tabela-scroll-inner">
          <table class="table table-bordered table-sm matriz mb-0" id="tabelaRelatorio">
            <thead></thead>
            <tbody></tbody>
            <tfoot></tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- PAGINAÇÃO -->
  <nav>
    <ul id="paginacao" class="pagination pagination-sm justify-content-center"></ul>
  </nav>

</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay" style="display:none;">
  <div class="text-center">
    <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;">
      <span class="visually-hidden">Carregando...</span>
    </div>
    <p class="text-muted fw-medium">Carregando relatório...</p>
  </div>
</div>

<script src="/js/libs/chart.js"></script>
<script src="/js/relatorio_registros_maquininhas.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/relatorio_registros_maquininhas.js') ?>"></script>
<link rel="stylesheet" href="/styles/relatorio_registro_maquininhas.css">

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>