<?php
/* /public/estoque/index.php */

$moduloAtual = 'estoque';
$tituloPagina = 'Visão de Estoque';
$tituloConteudo = 'Estoque Atual';

require_once '../templates/base_protected_v2.php';
?>

<div class="container-fluid estoque-page">

    <!-- HEADER -->
    <div class="page-header mb-4">
        <div>
            <i class="fas fa-boxes me-2"></i>
            <small class="text-muted">
                Controle e acompanhamento dos produtos
            </small>
        </div>
        <br>
        <div class="d-flex gap-2">
            <div class="search-wrapper">
                <input type="text" id="searchProduto" class="form-control" placeholder="Pesquisar produto...">
            </div>

            <button class="btn btn-success" onclick="abrirModalBaixa()">
                <i class="fas fa-minus-circle me-1"></i>
                Baixa Manual
            </button>
        </div>
    </div>


    <!-- CARD TABELA -->
    <div class="card card-default">

        <div class="card-header">
            Produtos em Estoque
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-modern align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Unidade</th>
                            <th class="text-end">Qtd. Atual</th>
                            <th class="text-end">Preço Médio (30 dias)</th>
                            <th>Última Movimentação</th>
                        </tr>
                    </thead>

                    <tbody id="estoque-lista">
                        <!-- Carregado via JS -->
                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>


<!-- MODAL BAIXA MANUAL (VERSÃO CUSTOM SEM BOOTSTRAP JS) -->
<div id="modalBaixa" class="modal-custom">

    <div class="modal-custom-dialog">

        <div class="modal-custom-content">

            <div class="modal-custom-header">
                <h5>
                    <i class="fas fa-minus-circle me-2 text-danger"></i>
                    Baixa Manual de Produto
                </h5>
                <button type="button" class="btn-close"
                        onclick="fecharModalBaixa()"></button>
            </div>

            <form id="formBaixaManual" onsubmit="salvarBaixaManual(event)">

                <div class="modal-custom-body">

                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <select id="produtoBaixa"
                                name="produtoId"
                                class="form-select"
                                required>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade</label>
                        <input type="number"
                               step="0.001"
                               id="quantidadeBaixa"
                               name="quantidade"
                               class="form-control text-end"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motivo / Observações</label>
                        <textarea id="observacoesBaixa"
                                  name="observacoes"
                                  class="form-control"
                                  rows="3"></textarea>
                    </div>

                </div>

                <div class="modal-custom-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            onclick="fecharModalBaixa()">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Confirmar Baixa
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>

<script src="/js/estoque.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/estoque.js') ?>"></script>

<link rel="stylesheet" href="/styles/estoque.css">
<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>