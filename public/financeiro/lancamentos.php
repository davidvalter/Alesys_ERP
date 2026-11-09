<?php
/* /public/financeiro/lancamentos.php */

session_start();

$moduloAtual = 'financeiro.lancamentos';
$tituloPagina = 'Lançamentos Financeiros';
$tituloConteudo = 'Lançamentos Financeiros';

require_once '../templates/base_protected_v2.php';
require_once '../../configs/database.php';
?>

<div class="card card-default">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold">
            Lançamentos Financeiros
        </h5>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card-body">

        <!-- FILTROS -->
        <div class="row g-2 mb-3">
            <div class="col-md-2">
                <select id="filtroConta" class="form-select">
                    <option value="">Todas as contas</option>
                </select>
            </div>

            <div class="col-md-2">
                <select id="filtroStatus" class="form-select">
                    <option value="">Todos status</option>
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" id="filtroDataInicio" class="form-control">
            </div>

            <div class="col-md-2">
                <input type="date" id="filtroDataFim" class="form-control">
            </div>

            <div class="col-md-2">
                <button id="btnFiltrarLanc" class="btn btn-outline-success w-100">
                    Filtrar
                </button>
            </div>

            <div class="col-md-2 text-end">
                <button class="btn btn-success w-100" onclick="abrirModalLancamento()">
                    + Novo Lançamento
                </button>
            </div>
        </div>

        <!-- TABELA -->

        <div class="table-responsive">
            <table id="tabelaLanc" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Conta</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th class="text-end">Valor Bruto</th>
                        <th class="text-end">Taxas</th>
                        <th class="text-end">Líquido</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="lancBody">
                    <tr>
                        <td colspan="10" class="text-center">
                            Carregando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination-wrapper mt-3 d-flex justify-content-between align-items-center">
            <span class="pagination-info"></span>
            <div class="pagination-controls"></div>
        </div>
    </div>
</div>

<!-- MODAL BAIXA -->
<div class="modal fade" id="modalBaixa" tabindex="-1" aria-labelledby="modalBaixaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalBaixaLabel">
                    Registrar Baixa
                </h5>

                <button type="button" class="btn-close" onclick="fecharModalBaixa()"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
                <form id="formBaixa">

                    <input type="hidden" id="baixalancId" name="lancId">

                    <div class="mb-3">
                        <label for="dataBaixa" class="form-label">
                            Data da Baixa
                        </label>
                        <input type="date" id="dataBaixa" name="dataBaixa" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="obsBaixa" class="form-label">
                            Observações
                        </label>
                        <input type="text" id="obsBaixa" name="observacoes" class="form-control" placeholder="Opcional">
                    </div>

                </form>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="fecharModalBaixa()">
                    Cancelar
                </button>

                <button type="button" class="btn btn-success fw-semibold"
                    onclick="document.getElementById('formBaixa').requestSubmit()">
                    Confirmar Baixa
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL LANÇAMENTO -->

<div class="modal fade" id="modalLancamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Novo Lançamento</h5>
                <button type="button" class="btn-close" onclick="fecharModalLancamento()"></button>
            </div>

            <div class="modal-body">
                <form id="formLancamento">

                    <input type="hidden" id="lancamentoId" name="id">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <select id="tipo" name="tipo" class="form-select" required>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Valor</label>
                            <input type="text" id="valorBruto" name="valorBruto" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Data</label>
                            <input type="date" id="dataLancamento" name="dataLancamento" class="form-control bg-light"
                                readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <input type="text" id="descricao" name="descricao" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Categoria</label>
                            <select id="categoria" name="categoriaId" class="form-select"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Conta</label>
                            <select id="contaModal" name="contaId" class="form-select"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Forma de Pagamento</label>
                            <select id="formaPagamento" name="formaPagamentoId" class="form-select"></select>
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" onclick="fecharModalLancamento()">
                    Cancelar
                </button>
                <button class="btn btn-success fw-semibold"
                    onclick="document.getElementById('formLancamento').requestSubmit()">
                    Salvar
                </button>
            </div>

        </div>
    </div>
</div>

<div id="toast-container" class="toast-container"></div>

<link rel="stylesheet" href="/styles/financeiro_lancamentos.css">

<?php require_once '../templates/footer_protected_v2.php'; ?>

<script
    src="/js/financeiro_lancamentos.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/financeiro_lancamentos.js') ?>"></script>