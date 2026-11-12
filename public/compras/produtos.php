<?php
/* /public/compras/produtos.php */
session_start();
$moduloAtual = 'compras.produtos';
$tituloPagina = 'Produtos';
$tituloConteudo = 'Gerenciamento de Produtos';
require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../../configs/database.php';
$pdo = getDB();

// Lista fixa de CFOPs mais usados (Convênio SINIEF - Anexo II)
$cfopLista = [
    '1101' => 'Compra para industrialização',
    '1102' => 'Compra para comercialização',
    '1201' => 'Devolução de compra para industrialização',
    '1202' => 'Devolução de compra para comercialização',
    '5101' => 'Venda de produção do estabelecimento',
    '5102' => 'Venda de mercadoria adquirida de terceiros',
    '5201' => 'Devolução de venda de produção do estabelecimento',
    '5202' => 'Devolução de venda de mercadoria adquirida de terceiros',
    '5915' => 'Transferência de produção do estabelecimento',
    '5916' => 'Transferência de mercadoria adquirida',
    '1949' => 'Outras entradas',
    '5949' => 'Outras saídas'
];

// CSOSN completo (Simples Nacional) - 10 códigos
$csosnLista = [
    '101' => 'Tributada pelo Simples Nacional com permissão de crédito',
    '102' => 'Tributada pelo Simples Nacional sem permissão de crédito',
    '103' => 'Isenção do IPI para a faixa de receita bruta',
    '201' => 'Tributada pelo Simples Nacional com permissão de crédito e ICMS-ST',
    '202' => 'Tributada pelo Simples Nacional sem permissão de crédito e ICMS-ST',
    '203' => 'Isenção do IPI para a faixa de receita bruta e ICMS-ST',
    '300' => 'Imune',
    '400' => 'Não tributada pelo Simples Nacional',
    '500' => 'ICMS cobrado anteriormente por ST ou antecipação',
    '900' => 'Outros'
];

// Lista de NCMs para o dropdown (vinda da tabela importada)
$ncmLista = $pdo->query("SELECT Codigo, Descricao FROM ProdutosNCM ORDER BY Codigo")->fetchAll(PDO::FETCH_ASSOC);

try {
    $produtos = $pdo->query("
        SELECT 
            Codigo,
            Nome,
            CodigoBarras,
            Categoria,
            DataCadastro,
            TipoProduto,
            Ativo
        FROM Produtos
        ORDER BY Nome
    ")->fetchAll(PDO::FETCH_ASSOC);
    $categorias = $pdo->query("
        SELECT DISTINCT Categoria 
        FROM Produtos 
        WHERE Categoria IS NOT NULL 
        ORDER BY Categoria
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log($e->getMessage());
    $produtos = [];
    $categorias = [];
}
?>
<div class="container-fluid">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">Produtos</h4>
            <small class="text-muted">Gerencie seus produtos cadastrados</small>
        </div>
    </div>
    <!-- ====================== AÇÕES / FILTROS ====================== -->
    <div class="card card-default mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
            <button id="btnNovoProduto" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i>
                Novo Produto
            </button>
            <button id="btnExportar" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-export me-1"></i>
                Exportar
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
                <table id="tabelaProdutos" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cód.</th>
                            <th>Nome</th>
                            <th>Cód. Barras</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Cadastro</th>
                            <th>Ativo</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?= $produto['Codigo'] ?></td>
                                <td><?= htmlspecialchars($produto['Nome']) ?></td>
                                <td><?= htmlspecialchars($produto['CodigoBarras']) ?></td>
                                <td><?= htmlspecialchars($produto['Categoria']) ?></td>
                                <td><?= htmlspecialchars($produto['TipoProduto']) ?></td>
                                <td><?= date('d/m/Y', strtotime($produto['DataCadastro'])) ?></td>
                                <td>
                                    <?php if ($produto['Ativo'] == 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end acoes-tabela">
                                    <button class="btn-action btn-edit btn-editar" data-id="<?= $produto['Codigo'] ?>"
                                        title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-action btn-deactivate btn-desativar"
                                        data-id="<?= $produto['Codigo'] ?>" title="Desativar"><i
                                            class="fas fa-eye-slash"></i>
                                    </button>
                                    <button class="btn-action btn-delete btn-excluir" data-id="<?= $produto['Codigo'] ?>"
                                        title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- ====================== MODAL PRODUTO ====================== -->
<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- HEADER -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalProdutoLabel">
                    <i class="fas fa-box me-1"></i>
                    <span id="modalTitle">Novo Produto</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- BODY -->
            <div class="modal-body">
                <form id="formProduto" enctype="multipart/form-data">
                    <input type="hidden" id="produtoCodigo" name="Codigo">
                    <div class="mb-3">
                        <label class="form-label">Nome*</label>
                        <input type="text" id="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagem do produto/insumo</label>
                        <input type="file" id="imagemProdutoFile" name="ImagemFile" class="form-control"
                            accept="image/jpeg,image/png,image/webp">
                        <div class="mt-2">
                            <img id="imagemProdutoPreview" src="/images/no-image.png" alt="Miniatura do produto"
                                width="120" height="120" style="object-fit:contain;border-radius:8px;">
                        </div>
                        <small class="text-muted">A imagem será redimensionada e armazenada no servidor.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" id="codigoBarras" class="form-control barcode-mask">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea id="descricao" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unidade*</label>
                            <select id="unidadeMedida" class="form-select" required>
                                <option value="UN">UN</option>
                                <option value="KG">KG</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria</label>
                            <input type="text" id="categoria" class="form-control" list="categorias">
                            <datalist id="categorias">
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Produto*</label>
                            <select id="tipoProduto" class="form-select" required>
                                <option value="insumo">Insumo</option>
                                <option value="vendavel">Vendável</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria de Venda</label>
                            <select id="categoriaVenda" class="form-select">
                                <option value="">— Selecione —</option>
                                <option value="prato">Prato</option>
                                <option value="bebida">Bebida</option>
                                <option value="sobremesa">Sobremesa</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">NCM</label>
                            <div class="input-group">
                                <input type="text" id="ncm" class="form-control" readonly placeholder="Selecione o NCM">
                                <button type="button" id="btnBuscarNcm" class="btn btn-outline-secondary"
                                    title="Buscar NCM">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="ncmDescricao"></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CFOP</label>
                            <select id="cfop" class="form-select">
                                <option value="">— Selecione —</option>
                                <?php foreach ($cfopLista as $cod => $desc): ?>
                                    <option value="<?= $cod ?>">
                                        <?= $cod ?> -
                                        <?= htmlspecialchars($desc) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CSOSN* <small class="text-muted">(obrigatório - Simples
                                    Nacional)</small></label>
                            <select id="csosn" class="form-select" required>
                                <option value="">— Selecione —</option>
                                <?php foreach ($csosnLista as $cod => $desc): ?>
                                    <option value="<?= $cod ?>">
                                        <?= $cod ?> -
                                        <?= htmlspecialchars($desc) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CEST</label>
                            <select id="cest" class="form-select">
                                <option value="">— Selecione o NCM primeiro —</option>
                            </select>
                            <small class="text-muted" id="cestDescricao"></small>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="ativo" checked>
                        <label class="form-check-label" for="ativo">Produto ativo</label>
                    </div>
                </form>
            </div>
            <!-- FOOTER -->
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" form="formProduto" class="btn btn-success fw-semibold">
                    <i class="fas fa-save me-1"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ====================== MODAL BUSCA NCM ====================== -->
<div class="modal fade" id="modalBuscaNcm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-search me-1"></i>
                    Buscar NCM
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="ncmBusca" class="form-control"
                        placeholder="Digite o código ou descrição do NCM...">
                    <button type="button" id="btnPesquisarNcm" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>
                        Pesquisar
                    </button>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 110px;">Código</th>
                                <th>Descrição</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="ncmResultados">
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Digite um termo e clique em Pesquisar
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted" id="ncmContador"></small>
                    <div>
                        <button type="button" id="ncmAnterior" class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" id="ncmProximo" class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/footer_protected_v2.php'; ?>
<!-- Scripts -->
<script src="/js/produtos.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/produtos.js') ?>"></script>