<?php
/* /public/vendas/pdv.php */

session_start();

$moduloAtual = 'vendas';
$tituloPagina = 'PDV';
$tituloConteudo = 'Ponto de Venda';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

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

// =========================
// CONTEÚDO
// =========================
ob_start();
?>

<div class="row g-3">

    <!-- PRODUTOS -->
    <div class="col-lg-8">

        <div class="mb-3">
            <input type="text" id="buscaProduto" class="form-control form-control-lg" placeholder="Buscar produto...">
        </div>

        <div class="mb-3 d-flex gap-2 overflow-auto" id="categorias"></div>

        <div class="row g-3" id="gridProdutos"></div>

    </div>

    <!-- CARRINHO -->
    <div class="col-lg-4">

        <div class="card shadow-sm h-100 d-flex flex-column">

            <div class="card-header">
                <strong>Carrinho</strong>
            </div>

            <div class="card-body overflow-auto" id="carrinhoItens" style="max-height: 50vh;"></div>

            <div class="card-footer">

                <div class="d-flex justify-content-between mb-2">
                    <span>Total:</span>
                    <strong id="totalVenda">R$ 0,00</strong>
                </div>

                <button class="btn btn-success w-100 btn-lg" id="btnFinalizar">
                    Finalizar Venda
                </button>

            </div>

        </div>

    </div>

</div>

<!-- MODAL -->
<div class="modal fade" id="modalPagamento">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Finalizar Venda</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- CPF -->
                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Deseja incluir CPF/CNPJ na Nota?
                    </label>

                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="PDV.toggleDocumento(true)">
                            Sim
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="PDV.toggleDocumento(false)">
                            Não
                        </button>
                    </div>

                    <input type="text" id="clienteDocumento" class="form-control d-none"
                        placeholder="Digite o CPF/CNPJ">
                </div>

                <!-- PAGAMENTO -->
                <div>
                    <label class="form-label fw-semibold">Forma de Pagamento</label>

                    <div class="row g-2" id="formasPagamento">

                        <div class="col-6">
                            <div class="pagamento-card" onclick="PDV.selecionarPagamento('dinheiro')">
                                💵 Dinheiro
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pagamento-card" onclick="PDV.selecionarPagamento('credito')">
                                💳 Crédito
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pagamento-card" onclick="PDV.selecionarPagamento('debito')">
                                💳 Débito
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pagamento-card" onclick="PDV.selecionarPagamento('vale')">
                                🍽️ V. Refeição
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pagamento-card" onclick="PDV.selecionarPagamento('pix')">
                                ⚡ PIX
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="pagamento-card" onclick="PDV.selecionarPagamento('fidelidade')">
                                ⭐ Fidelidade
                            </div>
                        </div>

                    </div>

                    <hr>

                    <!-- VALOR -->
                    <div class="mb-2">
                        <strong>Total: R$ <span id="totalModal">0.00</span></strong>
                    </div>

                    <!-- VALOR DIGITADO -->
                    <div id="areaValor" class="mb-3 d-none">
                        <label>Valor recebido</label>
                        <input type="number" id="valorPagamento" class="form-control">
                    </div>

                    <!-- BOTÃO ADICIONAR -->
                    <button class="btn btn-primary w-100 mb-3" onclick="PDV.adicionarPagamento()">
                        Adicionar Pagamento
                    </button>

                    <!-- LISTA PAGAMENTOS -->
                    <div id="listaPagamentos"></div>

                    <!-- RESTANTE / TROCO -->
                    <div class="mt-2">
                        <strong id="resumoPagamento"></strong>
                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-success" onclick="PDV.confirmarVenda()">Confirmar</button>
            </div>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Renderiza layout
include __DIR__ . '/../templates/base_protected_v2.php';

// Footer (fecha HTML)
require __DIR__ . '/../templates/footer_protected_v2.php';
?>

<script src="/js/pdv.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/pdv.js') ?>"></script>
<script src="/js/pdv_nfce.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/pdv_nfce.js') ?>"></script>
<link rel="stylesheet" href="/styles/pdv.css">
<link rel="stylesheet" href="/styles/pdv_nfce.css">