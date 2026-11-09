<?php
/* /public/financeiro/index.php */

$moduloAtual = 'financeiro';
$tituloPagina = 'Financeiro';
$tituloConteudo = 'Balanço Financeiro';

require_once '../templates/base_protected_v2.php';
require_once '../../configs/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT Id, Nome FROM FinanceiroContasBancarias WHERE Ativo = 1 ORDER BY Nome");
    $contas = $stmt->fetchAll();
} catch (Exception $e) {
    $contas = [];
}

$mesAtual = date('m');
$anoAtual = date('Y');
?>

<div class="financeiro-dashboard">

    <div id="toast-container"></div>

    <!-- ======================AÇÕES DO FINANCEIRO===================== -->

    <div class="card card-default mb-3">

        <div class="card-body d-flex flex-wrap gap-2">

            <a href="conciliacao.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-scale-balanced me-1"></i>
                Conciliação Financeira
            </a>

            <a href="contasbancarias.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-university me-1"></i>
                Contas Bancárias
            </a>

            <a href="lancamentos.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-book-open me-1"></i>
                Lançamentos
            </a>

            <a href="balanco.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-chart-column me-1"></i>
                Balanço
            </a>

            <a href="categoriaslancamentos.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-folder-tree me-1"></i>
                Categorias
            </a>

            <a href="formaspagamento.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-credit-card me-1"></i>
                Formas de Pagamento
            </a>

            <a href="importar_ofx.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-import me-1"></i>
                Importar OFX
            </a>

        </div>

    </div>


    <!-- ======================FILTROS====================== -->

    <div class="card card-default mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Mês</label>
                    <select id="mes" class="form-select">
                        <?php
                        $nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

                        foreach ($nomesMeses as $i => $nome) {
                            $mesNum = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            $selected = ($mesNum == $mesAtual) ? 'selected' : '';
                            echo "<option value='$mesNum' $selected>$nome</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ano</label>
                    <select id="ano" class="form-select">
                        <?php
                        for ($i = $anoAtual - 2; $i <= $anoAtual; $i++) {
                            $selected = ($i == $anoAtual) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Conta Bancária</label>
                    <select id="conta" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($contas as $c): ?>
                            <option value="<?= $c['Id'] ?>">
                                <?= htmlspecialchars($c['Nome']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>


                <div class="col-md-2">
                    <button id="btnFiltrar" class="btn btn-success w-100">
                        <i class="fas fa-filter me-1"></i>
                        Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ======================KPIs FINANCEIROS====================== -->

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-kpi">
                <div class="icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div>
                    <h6>Entradas</h6>
                    <h3 id="totalEntradas">R$ 0,00</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-kpi">
                <div class="icon bg-danger-soft">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div>
                    <h6>Saídas</h6>
                    <h3 id="totalSaidas">R$ 0,00</h3>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="card-kpi">
                <div class="icon bg-primary-soft">
                    <i class="fas fa-scale-balanced"></i>
                </div>
                <div>
                    <h6>Resultado</h6>
                    <h3 id="totalDiferenca">R$ 0,00</h3>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="card-kpi">
                <div class="icon bg-warning-soft">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h6>Variação</h6>
                    <h3 id="percentVariacao">0%</h3>
                </div>
            </div>
        </div>
    </div>


    <!-- ======================TABELA====================== -->

    <div class="card card-default">
        <div class="card-header">
            <i class="fas fa-table me-2"></i>
            Movimentações Financeiras
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaBalanco">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Conta</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Bruto</th>
                        <th>Taxas</th>
                        <th>Líquido</th>
                    </tr>
                </thead>
                <tbody id="tabelaFinanceiroBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="../js/financeiro.js"></script>

<?php require_once '../templates/footer_protected_v2.php'; ?>