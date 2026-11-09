<?php
/* /public/financeiro/contasbancarias.php */

$moduloAtual = 'financeiro';
$tituloPagina = 'Contas Bancárias';
$tituloConteudo = 'Gestão de Contas Bancárias';

require_once '../templates/base_protected_v2.php';
?>

<div class="financeiro-dashboard">

    <!-- ====================== AÇÕES ====================== -->

    <div class="card card-default mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i>
                    Voltar
                </a>
                <button id="btnNovaConta" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i>
                    Nova Conta
                </button>
            </div>
        </div>
    </div>


    <!-- ====================== TABELA ====================== -->

    <div class="card card-default">
        <div class="card-header">
            <i class="fas fa-university me-2"></i>
            Contas Bancárias
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaContas">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Banco</th>
                        <th>Agência</th>
                        <th>Conta</th>
                        <th>Saldo Inicial</th>
                        <th>Saldo Atual</th>
                        <th>Status</th>
                        <th style="width:120px">Ações</th>
                    </tr>
                </thead>
                <tbody id="contasBody"></tbody>
            </table>
        </div>
    </div>
</div>


<!-- ====================== MODAL ====================== -->

<div id="modalConta" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Conta Bancária</h5>
                <button type="button" class="btn-close" onclick="fecharModalConta()"></button>
            </div>
            <form id="formConta">
                <div class="modal-body">
                    <input type="hidden" id="contaId" name="Id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome da Conta</label>
                            <input type="text" id="nomeConta" name="Nome" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select id="tipoConta" name="Tipo" class="form-select">
                                <option value="caixa">Caixa</option>
                                <option value="banco">Banco</option>
                                <option value="maquininha">Maquininha</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ativo</label>
                            <select id="ativoConta" name="Ativo" class="form-select">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>


                    <!-- CAMPOS BANCO -->

                    <div id="bancoFields" class="row g-3 mt-2" style="display:none;">
                        <div class="col-md-4">
                            <label class="form-label">Banco</label>
                            <input type="text" id="banco" name="Banco" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Agência</label>
                            <input type="text" id="agencia" name="Agencia" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Conta</label>
                            <input type="text" id="conta" name="Conta" class="form-control">
                        </div>
                    </div>


                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Saldo Inicial</label>
                            <input type="number" step="0.01" id="saldoInicial" name="SaldoInicial" class="form-control"
                                value="0.00">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="fecharModalConta()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        Salvar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="../js/financeiro_contas.js"></script>

<?php require_once '../templates/footer_protected_v2.php'; ?>