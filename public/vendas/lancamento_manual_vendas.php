<?php
/* /public/vendas/lancamento_manual_vendas.php */

session_start();

$moduloAtual = 'vendas.lancamento_manual';
$tituloPagina = 'Lançamento Manual de Vendas';
$tituloConteudo = 'Lançamento Manual';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

$db = getDB();

// =========================
// Formas de pagamento
// =========================
$stmt = $db->prepare("
    SELECT Id, Nome 
    FROM FinanceiroFormasPagamento
    WHERE Tipo = 'entrada' AND Ativo = 1
    ORDER BY Nome
");
$stmt->execute();
$formasPagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================
// Produtos vendáveis
// =========================
$stmt = $db->prepare("
    SELECT Codigo, Nome 
    FROM Produtos 
    WHERE TipoProduto = 'vendavel' AND Ativo = 1
    ORDER BY Nome
");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <small class="text-muted">Registro rápido de vendas manuais no sistema. Essa tela serve apenas para computar as informações no banco de dados. Não se trata da tela de vendas.</small>
        </div>
    </div>

    <!-- Dados da Venda -->
    <div class="card card-default mb-4">
        <div class="card-body">

            <div class="row g-3">

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Forma de Pagamento</label>
                    <select id="formaPagamento" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($formasPagamento as $f): ?>
                            <option value="<?= $f['Id'] ?>">
                                <?= htmlspecialchars($f['Nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Data da Venda</label>
                    <input type="datetime-local" id="dataVenda" class="form-control"
                        value="<?= date('Y-m-d\TH:i') ?>">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Observações</label>
                    <input type="text" id="observacoes" class="form-control"
                        placeholder="Opcional">
                </div>

            </div>

        </div>
    </div>

    <!-- Itens da Venda -->
    <div class="card card-default mb-4">
        <div class="card-header">
            <strong><i class="fas fa-list me-2"></i> Itens da Venda</strong>
        </div>

        <div class="card-body">

            <div class="row g-3 align-items-end">

                <div class="col-lg-5 col-md-6">
                    <label class="form-label">Produto</label>
                    <select id="produto" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($produtos as $p): ?>
                            <option value="<?= $p['Codigo'] ?>">
                                <?= htmlspecialchars($p['Nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label class="form-label">Qtd</label>
                    <input type="number" id="quantidade" class="form-control"
                        min="0.001" step="0.001" value="1">
                </div>

                <div class="col-lg-2 col-md-3">
                    <label class="form-label">Valor Unit.</label>
                    <input type="number" id="valorUnitario" class="form-control"
                        step="0.01">
                </div>

                <div class="col-lg-3">
                    <button class="btn btn-outline-success w-100"
                        onclick="adicionarItem()">
                        <i class="fas fa-plus me-2"></i> Adicionar Item
                    </button>
                </div>

            </div>

            <hr class="my-3">

            <div class="table-responsive">
                <table class="table table-sm align-middle" id="tabelaItens">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th class="text-center" width="90">Qtd</th>
                            <th class="text-end" width="120">Valor Unit.</th>
                            <th class="text-end" width="120">Total</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Total -->
    <div class="card total-card">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>
                <small class="text-muted">Total da Venda</small>
                <h2 class="mb-0">
                    R$ <span id="totalVenda">0,00</span>
                </h2>
            </div>

            <button class="btn btn-success btn-lg px-4"
                onclick="salvarVenda()">
                <i class="fas fa-check me-2"></i> Confirmar Venda
            </button>

        </div>
    </div>

</div>

<script src="/js/lancamento_manual_vendas.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/lancamento_manual_vendas.js') ?>"></script>

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>