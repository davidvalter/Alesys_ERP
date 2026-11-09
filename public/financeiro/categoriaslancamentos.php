<?php
/* /public/financeiro/categoriaslancamentos.php */

$moduloAtual = 'financeiro';
$tituloPagina = 'Financeiro - Categorias de Lançamento';
$tituloConteudo = 'Categorias de Lançamento';

require_once '../templates/base_protected_v2.php';
?>

<div class="financeiro-dashboard">

    <!-- AÇÕES / FILTROS -->

    <div class="card card-default mb-3">
        <div class="card-body d-flex justify-content-between flex-wrap gap-2">
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>
                    Voltar
                </a>
                <select id="filtro-tipo" class="form-select form-select-sm" style="width:170px;">
                    <option value="">Todos os tipos</option>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                    <option value="nenhum">Nenhum</option>
                </select>
                <select id="filtro-ativo" class="form-select form-select-sm" style="width:170px;">
                    <option value="">Todos os Status</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>
            </div>
            <div>
                <button id="btnNovaCategoria" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i>
                    Nova Categoria
                </button>
            </div>
        </div>
    </div>


    <!-- TABELA -->

    <div class="card card-default">
        <div class="card-header">
            <i class="fas fa-tags me-2"></i>
            Categorias de Lançamento
        </div>
        <div class="table-responsive">
            <table id="tabelaCategorias" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Grupo</th>
                        <th>Linha DRE</th>
                        <th>Status</th>
                        <th style="width:120px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            Carregando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- MODAL -->

<div class="modal fade" id="modalCategoria">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Categoria</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategoria">
                <div class="modal-body">
                    <input type="hidden" id="categoriaId">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" id="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select id="tipo" class="form-select">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                            <option value="nenhum">Nenhum</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grupo</label>
                        <input type="text" id="grupo" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Linha da DRE</label>
                        <select id="dreLinha" class="form-select">
                            <option value="">— Sem classificação DRE —</option>
                        </select>
                        <div class="form-text">Define onde esta categoria entra na DRE. Deixe vazio para lançamentos
                            fora do relatório.</div>
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

<script src="../js/financeiro_categoriaslancamentos.js?v=<?= @filemtime(__DIR__ . '/../js/financeiro_categoriaslancamentos.js') ?>"></script>

<?php require_once '../templates/footer_protected_v2.php'; ?>