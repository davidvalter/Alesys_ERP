<section class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="mb-0"><i class="fas fa-sliders me-2 text-success"></i>Filtros da análise</h6>
    </div>
    <div class="card-body pt-2">
        <form id="formFiltros" novalidate>
            <fieldset class="border-0 m-0 p-0 mb-3">
                <legend class="form-label fw-semibold fs-6 mb-2">1. Período da análise</legend>
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="dataInicio" class="form-label small text-muted">Data inicial</label>
                        <input type="date" id="dataInicio" name="dataInicio" class="form-control" required>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="dataFim" class="form-label small text-muted">Data final</label>
                        <input type="date" id="dataFim" name="dataFim" class="form-control" required>
                    </div>
                </div>
            </fieldset>

            <hr class="text-muted opacity-25">

            <div class="row g-3 align-items-start">
                <div class="col-12 col-xl-6">
                    <label class="form-label fw-semibold">2. Produtos <span class="text-danger">*</span></label>
                    <div class="multi-picker" data-picker="produto">
                        <input type="search" class="form-control multi-picker-search" placeholder="Buscar e selecionar produtos..." autocomplete="off" aria-label="Buscar produtos" aria-expanded="false">
                        <div class="multi-picker-menu" hidden><div class="multi-picker-options"></div></div>
                        <div class="multi-picker-values"></div>
                        <div class="multi-picker-selected" aria-live="polite"></div>
                    </div>
                    <div class="form-text">Selecione um ou mais produtos para comparar marcas e apresentações.</div>
                </div>

                <div class="col-12 col-xl-6">
                    <label class="form-label fw-semibold">3. Fornecedores</label>
                    <div class="multi-picker" data-picker="fornecedor">
                        <input type="search" class="form-control multi-picker-search" placeholder="Todos os fornecedores" autocomplete="off" aria-label="Buscar fornecedores" aria-expanded="false">
                        <div class="multi-picker-menu" hidden><div class="multi-picker-options"></div></div>
                        <div class="multi-picker-values"></div>
                        <div class="multi-picker-selected" aria-live="polite"></div>
                    </div>
                    <div class="form-text">Opcional: sem seleção, serão incluídos todos.</div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="submit" id="btnGerarComparacao" class="btn btn-success">
                    <i class="fas fa-chart-line me-1"></i>Comparar preços
                </button>
                <button type="button" id="btnExportar" class="btn btn-outline-secondary" disabled>
                    <i class="fas fa-file-csv me-1"></i>Exportar CSV
                </button>
                <span class="small text-muted ms-md-2">Valores calculados com base nas notas de compra ativas.</span>
            </div>
            <div id="erroFiltros" class="alert alert-danger py-2 mt-3 mb-0 d-none" role="alert"></div>
        </form>
    </div>
</section>
