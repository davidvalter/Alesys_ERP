<?php
/* /public/compras/notas_fiscais.php */

session_start();

$moduloAtual = 'compras.notasfiscais';
$tituloPagina = 'Notas Fiscais';
$tituloConteudo = 'Gestão de Notas Fiscais de Compra';

require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    $mercados = $pdo->query("
        SELECT Codigo, Nome 
        FROM Mercados 
        WHERE Ativo = 1 
        ORDER BY Nome
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $mercados = [];
}
?>

<div class="container-fluid">

    <!-- AÇÕES -->
    <div class="card card-default mb-3">
        <div class="card-body d-flex flex-wrap gap-2">

            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>

            <a href="importar_alenfe.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-cloud-arrow-down me-1"></i> Importar do AleNFE</a>

            <button id="btnNovaNota" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Nova Nota
            </button>

            <button id="btnExportar" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-export me-1"></i> Exportar
            </button>

            <button id="btnScanMobile" class="btn btn-outline-warning btn-sm">
                <i class="fas fa-camera me-1"></i> Scan Mobile
            </button>

        </div>
    </div>

    <!-- TABELA -->
    <div class="card card-default">
        <div class="card-body">

            <div class="table-responsive">
                <table id="tabelaNotas" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Número</th>
                            <th>Série</th>
                            <th>Tipo (XML)</th>
                            <th>Emissão</th>
                            <th>Mercado</th>
                            <th class="text-end">Valor</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- ========================= -->
<!-- MODAL NOTA FISCAL (NOVO DESIGN) -->
<!-- ========================= -->

<div id="modalNota" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
        <div class="modal-content modal-nota-fiscal">

            <!-- HEADER FIXO -->
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Nota Fiscal</h5>
                <button type="button" class="btn-close close-modal"></button>
            </div>

            <!-- BODY COM SCROLL -->
            <div class="modal-body">
                <form id="formNota">

                    <input type="hidden" id="notaId">
                    <input type="hidden" id="pdfCaminhoExistente">

                    <!-- ========================= -->
                    <!-- BLOCO PDF (TOPO) -->
                    <!-- ========================= -->
                    <div class="card-nota p-2 mb-3 d-flex align-items-center justify-content-between">

                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-file-pdf text-danger"></i>
                            <small class="text-muted">
                                PDF da Nota
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <small id="pdfFileName" class="text-muted">
                                Nenhum arquivo selecionado
                            </small>

                        </div>

                    </div>

                    <div class="card card-nota mb-3">
                        <div class="card-body d-flex flex-column align-items-center gap-2">

                            <div id="ocrLoader" class="text-center py-2" style="display:none;">
                                <div class="spinner-border text-warning" role="status"></div>
                            </div>

                            <div id="ocrStatus" class="small text-muted text-center" style="display:none;"></div>

                            <div id="ocrPreview" class="text-center position-relative" style="display:none;">
                                <img id="ocrPreviewImg" class="img-fluid rounded shadow-sm" style="max-height:200px;">

                                <button type="button" id="btnRemoverPreview"
                                    class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 shadow-sm"
                                    style="display:none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <label class="btn btn-warning btn-sm mt-2">
                                <i class="fas fa-magic me-1"></i>
                                Escaneamento Inteligente
                                <input type="file" id="inputNotaOCR" accept="image/*,application/pdf"
                                    capture="environment" hidden>
                                <small class="text-muted">
                                    Máx: 5MB
                                </small>
                            </label>

                            <small class="text-muted text-center">
                                Tire uma foto ou envie o PDF da nota
                            </small>

                        </div>
                    </div>

                    <div id="pdfUploadError" class="text-danger small mb-2" style="display:none;"></div>

                    <!-- ========================= -->
                    <!-- DADOS DA NOTA -->
                    <!-- ========================= -->
                    <div class="card card-nota mb-3">
                        <div class="card-header">Dados da Nota</div>
                        <div class="card-body row g-3">

                            <div class="col-md-2">
                                <label>Número</label>
                                <input type="text" id="numero" class="form-control input-nota">
                            </div>

                            <div class="col-md-2">
                                <label>Série</label>
                                <input type="text" id="serie" class="form-control input-nota">
                            </div>

                            <div class="col-md-2">
                                <label>Tipo (XML do fornecedor)</label>
                                <select id="tipo" class="form-control input-nota">
                                    <option value="">Selecione</option>
                                    <option value="0">0 · Entrada (XML do fornecedor)</option>
                                    <option value="1">1 · Saída (XML do fornecedor)</option>
                                </select>
                                <small class="text-muted">Campo fiscal da nota do fornecedor; não define compra ou venda no Alesys.</small>
                                <small class="text-muted">Campo fiscal da nota do fornecedor; não define compra ou venda no Alesys.</small>
                            </div>

                            <div class="col-md-3">
                                <label>Mercado</label>
                                <select id="mercado" class="form-control input-nota">
                                    <option value="">Selecione um mercado</option>
                                    <?php foreach ($mercados as $m): ?>
                                        <option value="<?= $m['Codigo'] ?>">
                                            <?= $m['Nome'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>Data Emissão</label>
                                <input type="date" id="dataEmissao" class="form-control input-nota">
                            </div>

                            <div class="col-md-3">
                                <label>Data Recebimento</label>
                                <input type="date" id="dataRecebimento" class="form-control input-nota">
                            </div>

                            <div class="col-md-6">
                                <label>Natureza da Operação</label>
                                <input type="text" id="naturezaOperacao" class="form-control input-nota">
                            </div>

                            <div class="col-md-6">
                                <label>Chave de Acesso</label>
                                <input type="text" id="chaveAcesso" class="form-control input-nota">
                            </div>

                            <div class="col-md-12">
                                <label>Observações</label>
                                <textarea id="observacoes" class="form-control input-nota" rows="2"></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- ========================= -->
                    <!-- ITENS -->
                    <!-- ========================= -->
                    <div class="card card-nota mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Itens da Nota</span>
                            <button type="button" id="btnAdicionarItem" class="btn btn-sm btn-success">
                                + Item
                            </button>
                        </div>

                        <div class="card-body">

                            <!-- CABEÇALHO TIPO TABELA -->
                            <div class="row fw-bold small text-muted mb-2 px-1">
                                <div class="col-md-4">Produto</div>
                                <div class="col-md-1 text-end">Qtd</div>
                                <div class="col-md-2 text-end">Unitário</div>
                                <div class="col-md-2 text-end">Total</div>
                                <div class="col-md-1 text-center">UN</div>
                                <div class="col-md-1"></div>
                            </div>

                            <div id="itensContainer"></div>

                        </div>
                    </div>

                    <!-- TEMPLATE ITEM -->
                    <script type="text/template" id="itemTemplate">
                        <div class="item-nota row align-items-center g-2 py-1 border-bottom">

                            <div class="col-md-4">
                                <input type="hidden" class="produto-id">
                                <input type="text" class="form-control produto-search-input input-nota" placeholder="Buscar produto...">
                            </div>

                            <div class="col-md-1">
                                <input type="text" class="form-control quantidade input-nota text-end" placeholder="0">
                            </div>

                            <div class="col-md-2">
                                <input type="text" class="form-control valor-unitario input-nota text-end" placeholder="0,00">
                            </div>

                            <div class="col-md-2">
                                <input type="text" class="form-control valor-total input-nota text-end" readonly>
                            </div>

                            <div class="col-md-1">
                                <input type="text" class="form-control unidade-medida input-nota text-center" placeholder="UN">
                            </div>

                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remover-item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                        </div>
                    </script>

                    <div class="col-12">
                        <input type="text" class="form-control descricao-complementar input-nota mt-1"
                            placeholder="Descrição complementar...">
                    </div>

                    <!-- ========================= -->
                    <!-- TOTAL -->
                    <!-- ========================= -->
                    <div class="card card-nota">
                        <div class="card-body">

                            <div class="row justify-content-end g-3">

                                <div class="col-md-3">
                                    <label>Desconto</label>
                                    <input type="text" id="descontoNota" class="form-control input-nota text-end">
                                </div>

                                <div class="col-md-3">
                                    <label>Total</label>
                                    <input type="text" id="valorTotal" class="form-control text-end fw-bold fs-5"
                                        readonly>
                                </div>

                            </div>

                        </div>
                    </div>

                </form>
            </div>

            <!-- FOOTER FIXO -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal">
                    Cancelar
                </button>
                <button type="submit" form="formNota" class="btn btn-success">
                    Salvar
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ========================= -->
<!-- MODAL VISUALIZAR NOTA -->
<!-- ========================= -->

<div id="modalVisualizar" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Visualizar Nota</h5>
                <button type="button" class="btn-close btn-fechar-visualizar"></button>
            </div>

            <!-- BODY (dinâmico via JS) -->
            <div class="modal-body">
                <!-- JS vai injetar conteúdo aqui -->
            </div>

            <!-- FOOTER (ações) -->
            <div class="modal-footer justify-content-between">

                <div>
                    <button class="btn btn-outline-danger" id="btnCancelarNota">
                        <i class="fas fa-ban"></i> Cancelar
                    </button>
                </div>

                <div class="d-flex gap-2">

                    <button id="btnVerPdf" class="btn btn-sm btn-outline-danger" ${data.pdf_path ? '' : 'disabled' }>
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>

                    <button class="btn btn-success" id="btnGerarLancamento">
                        <i class="fas fa-dollar-sign"></i> Gerar Lançamento
                    </button>

                    <button class="btn btn-light btn-fechar-visualizar">
                        Fechar
                    </button>

                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer_protected_v2.php'; ?>

<!-- ========================= -->
<!-- JS - ORDEM IMPORTA MUITO -->
<!-- ========================= -->

<script src="/js/nfe/nfe.utils.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.utils.js') ?>"></script>
<script src="/js/nfe/nfe.api.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.api.js') ?>"></script>
<script src="/js/nfe/nfe.table.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.table.js') ?>"></script>
<script src="/js/nfe/nfe.modal.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.modal.js') ?>"></script>
<script src="/js/nfe/nfe.produto.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.produto.js') ?>"></script>
<script src="/js/nfe/nfe.modal.visualizar.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.modal.visualizar.js') ?>"></script>
<script src="/js/nfe/nfe.service.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.service.js') ?>"></script>
<script src="/js/nfe/nfe.index.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/nfe/nfe.index.js') ?>"></script>