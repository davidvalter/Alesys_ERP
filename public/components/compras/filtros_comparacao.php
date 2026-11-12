<section class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="mb-0"><i class="fas fa-sliders me-2 text-success"></i>Filtros da análise</h6>
    </div>
    <div class="card-body pt-2">
        <form id="formFiltros">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl-5">
                    <label for="produto" class="form-label fw-semibold">Produtos</label>
                    <select id="produto" name="produto[]" class="form-select" multiple required aria-describedby="ajudaProdutos"></select>
                    <div id="ajudaProdutos" class="form-text">Selecione um ou mais produtos. Ex.: compare marcas diferentes.</div>
                </div>
                <div class="col-12 col-xl-3">
                    <label for="fornecedores" class="form-label fw-semibold">Fornecedores</label>
                    <select id="fornecedores" name="fornecedores[]" class="form-select" multiple></select>
                    <div class="form-text">Opcional: vazio inclui todos.</div>
                </div>
                <div class="col-6 col-xl-1">
                    <label for="dataInicio" class="form-label fw-semibold">De</label>
                    <input type="date" id="dataInicio" name="dataInicio" class="form-control" required>
                </div>
                <div class="col-6 col-xl-1">
                    <label for="dataFim" class="form-label fw-semibold">Até</label>
                    <input type="date" id="dataFim" name="dataFim" class="form-control" required>
                </div>
                <div class="col-12 col-xl-2 d-flex gap-2">
                    <button type="submit" id="btnGerarComparacao" class="btn btn-success flex-grow-1">
                        <i class="fas fa-chart-line me-1"></i>Comparar
                    </button>
                    <button type="button" id="btnExportar" class="btn btn-outline-secondary" disabled title="Gere uma comparação para exportar" aria-label="Exportar comparação em CSV">
                        <i class="fas fa-file-csv"></i>
                    </button>
                </div>
            </div>
            <div id="erroFiltros" class="alert alert-danger py-2 mt-3 mb-0 d-none" role="alert"></div>
        </form>
    </div>
</section>
