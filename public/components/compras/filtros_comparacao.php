<?php
/* /public/components/compras/filtros_comparacao.php */
?>

<div class="card mb-3">

    <div class="card-header">
        <strong>Filtros</strong>
    </div>

    <div class="card-body">

        <form id="formFiltros">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Produto</label>
                    <select id="produto" name="produto" class="form-select" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fornecedores</label>
                    <select id="fornecedores" name="fornecedores[]" class="form-select" multiple></select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" id="dataInicio" name="dataInicio" class="form-control" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" id="dataFim" name="dataFim" class="form-control" required>
                </div>

            </div>

            <div class="mt-3 d-flex gap-2">

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-search me-1"></i>
                    Gerar Comparação
                </button>

                <button type="button" id="btnExportar" class="btn btn-outline-secondary">
                    <i class="fas fa-file-export me-1"></i>
                    Exportar
                </button>

            </div>

        </form>

    </div>

</div>