<?php
// public/financeiro/balanco.php

session_start();

$moduloAtual = 'financeiro.balanco';
$tituloPagina = 'Financeiro - Balanço Mensal';
$tituloConteudo = 'Balanço Mensal';

require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../../configs/database.php';
?>

<div class="card card-default">

  <!-- HEADER -->
  <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">

    <h5 class="mb-0 fw-semibold">
      Balanço Mensal
    </h5>

    <div class="d-flex align-items-end gap-2 flex-wrap">

      <div>
        <label class="form-label mb-1">Mês</label>
        <select id="selectMes" class="form-select form-select-sm">
          <?php
          $mesAtual = date('m');
          $meses = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro'
          ];
          foreach ($meses as $k => $nome) {
            $sel = ($k === $mesAtual) ? 'selected' : '';
            echo "<option value=\"$k\" $sel>$nome</option>";
          }
          ?>
        </select>
      </div>

      <div>
        <label class="form-label mb-1">Ano</label>
        <select id="selectAno" class="form-select form-select-sm">
          <?php
          $anoAtual = (int) date('Y');
          for ($y = $anoAtual; $y >= $anoAtual - 3; $y--) {
            echo "<option value=\"$y\">$y</option>";
          }
          ?>
        </select>
      </div>

      <button id="btnAtualizarBalanco" class="btn btn-success btn-sm">
        <i class="fas fa-filter"></i>
      </button>

      <button id="btnExportarCsv" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-file-csv"></i>
      </button>

    </div>

  </div>

  <div class="card-body">

    <!-- Botão Voltar -->
    <div class="mb-3">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Voltar
      </a>
    </div>

    <!-- TABELA -->
    <div class="table-responsive">
      <table id="tabelaBalanco" class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th class="text-start sticky-col">
              Categoria / Dia
            </th>
          </tr>
        </thead>
        <tbody id="balancoBody">
          <tr>
            <td colspan="32" class="text-center text-muted">
              Carregando dados...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>

<div id="toast-container" class="toast-container"></div>

<link rel="stylesheet" href="/styles/financeiro_balanco.css">

<?php require_once '../templates/footer_protected_v2.php'; ?>

<script
  src="/js/financeiro_balanco.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/financeiro_balanco.js') ?>"></script>