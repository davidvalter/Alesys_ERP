<?php
session_start();

$moduloAtual = 'vendas.cardapio';
$tituloPagina = 'Cardápio';
$tituloConteudo = 'Gerenciamento de Cardápio';

require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    $categorias = $pdo->query("
        SELECT DISTINCT Categoria 
        FROM Cardapio 
        WHERE Categoria IS NOT NULL 
        ORDER BY Categoria
    ")->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    error_log($e->getMessage());
    $categorias = [];
}
?>

<div class="container-fluid">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">Cardápio</h4>
            <small class="text-muted">Gerencie os itens disponíveis para venda no PDV</small>
        </div>
    </div>

    <!-- ====================== AÇÕES / FILTROS ====================== -->
    <div class="card card-default mb-3">
        <div class="card-body d-flex flex-wrap gap-2">

            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>

            <button id="btnNovo" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i>
                Novo Item
            </button>

            <select id="filtroCategoria" class="form-select form-select-sm" style="max-width: 220px;">
                <option value="">Todas Categorias</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= htmlspecialchars($categoria) ?>">
                        <?= htmlspecialchars($categoria) ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>
    </div>

    <!-- ====================== TABELA ====================== -->
    <div class="card card-default">
        <div class="card-body">

            <div class="table-responsive">
                <table id="tabelaCardapio" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Preço</th>
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

<!-- ====================== MODAL ====================== -->
<div class="modal fade" id="modalCardapio" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list me-1"></i>
                    <span id="modalTitle">Novo Item</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
                <form id="formCardapio">

                    <input type="hidden" id="Id">

                    <div class="mb-3">
                        <label class="form-label">Nome*</label>
                        <input type="text" id="Nome" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo*</label>
                        <select id="TipoItem" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="produto">Produto</option>
                            <option value="prato">Prato</option>
                        </select>
                    </div>

                    <div class="mb-3" id="boxProduto" style="display:none;">
                        <label class="form-label">Produto</label>
                        <select id="ProdutoId" class="form-select"></select>
                    </div>

                    <div class="mb-3" id="boxPrato" style="display:none;">
                        <label class="form-label">Prato</label>
                        <select id="PratoId" class="form-select"></select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria</label>
                            <input type="text" id="Categoria" class="form-control" list="categorias">
                            <datalist id="categorias">
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preço*</label>
                            <input type="number" step="0.01" id="PrecoVenda" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Imagem do produto ou prato</label>
                        <div class="d-flex align-items-center gap-3">
                            <img id="previewImagem" src="/images/no-image.png" alt="Miniatura herdada do produto ou prato"
                                width="96" height="96" style="object-fit:contain;border-radius:8px;">
                            <small class="text-muted">A imagem é herdada do produto ou prato vinculado. Para alterá-la, edite o cadastro correspondente.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="Ativo" class="form-select">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>

                </form>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" form="formCardapio" class="btn btn-success fw-semibold">
                    <i class="fas fa-save me-1"></i>
                    Salvar
                </button>
            </div>

        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toastGlobal" class="toast align-items-center text-bg-primary border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMensagem">
                Mensagem
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="/js/cardapio.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/cardapio.js') ?>"></script>

<?php require_once __DIR__ . '/../templates/footer_protected_v2.php'; ?>