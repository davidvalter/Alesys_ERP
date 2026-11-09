<?php
/* /public/financeiro/formaspagamento.php */

$moduloAtual = 'financeiro';
$tituloPagina = 'Financeiro - Formas de Pagamento';
$tituloConteudo = 'Formas de Pagamento';

require_once '../templates/base_protected_v2.php';
?>

<div class="financeiro-dashboard">

    <!-- AÇÕES -->

    <div class="card card-default mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>
                    Voltar
                </a>
            </div>
            <div>
                <button id="btnNovaForma" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i>
                    Nova Forma
                </button>
            </div>
        </div>
    </div>


    <!-- TABELA -->

    <div class="card card-default">
        <div class="card-header">
            <i class="fas fa-credit-card me-2"></i>
            Formas de Pagamento
        </div>
        <div class="table-responsive">
            <table id="tabelaFormasPagamento" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th style="width:120px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            Carregando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- MODAL -->

<div class="modal fade" id="modalForma">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Forma de Pagamento</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formFormaPagamento">
                <div class="modal-body">
                    <input type="hidden" id="formaId">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" id="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select id="tipo" class="form-select">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="../js/financeiro_formaspagamento.js"></script>

<?php require_once '../templates/footer_protected_v2.php'; ?>