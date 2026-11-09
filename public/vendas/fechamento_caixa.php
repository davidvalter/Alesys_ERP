<?php
/* public/vendas/fechamento_caixa.php */

session_start();

$moduloAtual = 'vendas.fechamento_caixa';
$tituloPagina = 'Fechamento de Caixa';
$tituloConteudo = 'Fechamento de Caixa';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}
?>

<div class="container-fluid fechamento-caixa">

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <small class="text-muted">
                Consolidação diária de vendas por forma de pagamento
            </small>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card card-default mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data</label>
                    <input type="date" id="data" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label">Observações</label>
                    <input type="text" id="observacoes" class="form-control"
                        placeholder="Observações gerais do fechamento">
                </div>
            </div>
        </div>
    </div>

    <!-- Operadoras -->
    <div class="operadoras-grid">
        <?php
        $operadoras = ['Stone', 'Rede', 'Sipag', 'Pagbank', 'InfinityPay'];
        foreach ($operadoras as $op):
            $slug = strtolower($op);
            ?>
            <div class="card operadora-card operadora-<?= $slug ?>">
                <div class="card-header">
                    <?= $op ?>
                </div>

                <div class="card-body">
                    <div class="grid-valores">
                        <div>
                            <label>Débito</label>
                            <input type="text" class="form-control money valor" data-operadora="<?= $op ?>"
                                data-tipo="debito">
                        </div>

                        <div>
                            <label>Crédito</label>
                            <input type="text" class="form-control money valor" data-operadora="<?= $op ?>"
                                data-tipo="credito">
                        </div>

                        <div>
                            <label>Voucher</label>
                            <input type="text" class="form-control money valor" data-operadora="<?= $op ?>"
                                data-tipo="voucher">
                        </div>

                        <div>
                            <label>PIX</label>
                            <input type="text" class="form-control money valor" data-operadora="<?= $op ?>" data-tipo="pix">
                        </div>
                    </div>

                    <div class="subtotal-operadora">
                        Subtotal:
                        <strong>
                            R$ <span class="subtotal" data-operadora="<?= $op ?>">0,00</span>
                        </strong>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Valores Diretos -->
    <div class="row g-4 mt-3">
        <div class="col-lg-6">
            <div class="card resumo-card h-100">
                <div class="card-body">
                    <h6>PIX Direto (Conta)</h6>
                    <input type="text" class="form-control money valor" id="pix_direto" data-operadora="PixCNPJ"
                        data-tipo="pix">
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card resumo-card h-100">
                <div class="card-body">
                    <h6>Dinheiro</h6>
                    <input type="text" class="form-control money valor" id="dinheiro" data-operadora="Dinheiro"
                        data-tipo="dinheiro">
                </div>
            </div>
        </div>
    </div>

    <!-- Total Geral -->
    <div class="card total-card mt-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <small class="text-muted">Total Geral</small>
                <h2 class="mb-0">
                    R$ <span id="totalGeral">0,00</span>
                </h2>
            </div>

            <button class="btn btn-success btn-lg px-4" onclick="salvarFechamento()">
                <i class="fas fa-check me-2"></i> Salvar Fechamento
            </button>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>

<script
    src="/js/fechamento_caixa.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/fechamento_caixa.js') ?>"></script>

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>