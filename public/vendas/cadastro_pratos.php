<?php
// public/vendas/cadastro_pratos.php
session_start();

$moduloAtual = 'vendas.cadastropratos';
$tituloPagina = 'Cadastro de Pratos';
$tituloConteudo = 'Cadastro de Pratos';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

verificarPermissao($moduloAtual);

try {
    $db = getDB();
    $stmt = $db->query("SELECT Id, Nome, Categoria, Preco, Foto, Status FROM Pratos ORDER BY Nome ASC");
    $pratos = $stmt->fetchAll();
} catch (Exception $e) {
    $pratos = [];
}
?>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a href="/vendas/index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Voltar
            </a>
            <div>
                <small class="text-muted">Gerenciamento de pratos, receitas e preços</small>
            </div>
        </div>
    </div>
    <!-- Grid Produtos -->
    <div class="row g-4">
        <!-- Novo -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card produto-card-novo h-100" onclick="abrirModalNovo()">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                    <i class="fas fa-plus-circle fa-3x mb-2 text-success"></i>
                    <strong>Novo Produto</strong>
                </div>
            </div>
        </div>
        <!-- Produtos -->
        <?php foreach ($pratos as $prato): ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="card produto-card h-100">
                    <div class="produto-thumb" onclick="abrirModalEditar(<?= $prato['Id'] ?>)">
                        <img src="<?= !empty($prato['Foto'])
                            ? '/uploads/pratos/' . htmlspecialchars($prato['Foto'])
                            : '/uploads/pratos/sem-foto.png' ?>">
                    </div>
                    <div class="card-body p-2 text-center">
                        <strong class="produto-nome"><?= htmlspecialchars($prato['Nome']) ?></strong>
                        <div class="produto-preco">
                            R$ <?= number_format($prato['Preco'], 2, ',', '.') ?>
                        </div>
                    </div>
                    <div class="produto-acoes">
                        <?php if ($prato['Status'] == 1): ?>
                            <button class="btn btn-sm btn-outline-warning"
                                onclick="event.stopPropagation(); inativarPrato(<?= $prato['Id'] ?>)">
                                Inativar
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-success"
                                onclick="event.stopPropagation(); restorePrato(<?= $prato['Id'] ?>)">
                                Ativar
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="event.stopPropagation(); excluirPrato(<?= $prato['Id'] ?>)">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- Modal Produto -->
<div class="modal fade" id="modalExemplo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastrar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProduto" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <button type="button" class="nav-link active tab-btn" onclick="abrirAba('dados')">Dados do
                                Prato</button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-btn"
                                onclick="abrirAba('receita')">Receita</button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link tab-btn" onclick="abrirAba('fiscal')">Dados
                                Fiscais</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <input type="hidden" id="produtoId" name="id">
                        <!-- Aba Dados -->
                        <div id="aba-dados" class="tab-pane active">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome</label>
                                    <input type="text" id="nome" name="nome" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Categoria</label>
                                    <select id="categoria" name="categoria" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <option value="prato_principal">Prato Principal</option>
                                        <option value="entrada">Entrada</option>
                                        <option value="sobremesa">Sobremesa</option>
                                        <option value="bebida">Bebida</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Preço</label>
                                    <input type="number" id="preco" name="preco" step="0.01" class="form-control"
                                        required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Foto</label>
                                    <input type="file" id="foto" name="foto" class="form-control">
                                </div>
                            </div>
                        </div>
                        <!-- Aba Receita -->
                        <div id="aba-receita" class="tab-pane">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Ingrediente</label>
                                    <select id="ingredienteId" class="form-select"></select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qtd</label>
                                    <input type="number" id="quantidade" step="0.001" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Unidade</label>
                                    <input type="text" id="unidade" class="form-control" placeholder="g, ml, un">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Perda (%)</label>
                                    <input type="number" id="perda" step="0.01" value="0" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Baixa</label>
                                    <select id="metodo" class="form-select">
                                        <option value="auto">Automática</option>
                                        <option value="manual">Manual</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success mt-3" onclick="adicionarIngrediente()">
                                <i class="fas fa-plus me-2"></i> Adicionar Ingrediente
                            </button>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ingrediente</th>
                                            <th>Qtd</th>
                                            <th>Unid</th>
                                            <th>Perda</th>
                                            <th>Baixa</th>
                                            <th width="70"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="receita-lista"></tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Aba Fiscal -->
                        <div id="aba-fiscal" class="tab-pane">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">NCM</label>
                                    <div class="input-group">
                                        <input type="text" id="ncm" name="ncm" class="form-control" readonly
                                            placeholder="Selecione o NCM">
                                        <button type="button" class="btn btn-outline-secondary" title="Buscar NCM"
                                            onclick="abrirBuscaNcm()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="ncmDescricao"></small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CEST</label>
                                    <select id="cest" name="cest" class="form-select" disabled>
                                        <option value="">— Selecione o NCM primeiro —</option>
                                    </select>
                                    <small class="text-muted" id="cestDescricao"></small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CFOP</label>
                                    <select id="cfop" name="cfop" class="form-select">
                                        <option value="">— Selecione —</option>
                                        <option value="5101">5101 - Venda de produção do estabelecimento</option>
                                        <option value="5102">5102 - Venda de mercadoria adquirida</option>
                                        <option value="5103">5103 - Venda de produção (produto sujeito a ST)</option>
                                        <option value="5105">5105 - Venda de mercadoria adquirida (ST)</option>
                                        <option value="5405">5405 - Venda com entrega futura</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CSOSN</label>
                                    <select id="csosn" name="csosn" class="form-select">
                                        <option value="">Selecione</option>
                                        <option value="101">101 - Tributada com permissão de crédito</option>
                                        <option value="102">102 - Tributada sem permissão de crédito</option>
                                        <option value="103">103 - Isenção</option>
                                        <option value="300">300 - Imune</option>
                                        <option value="400">400 - Não tributada</option>
                                        <option value="500">500 - ICMS cobrado por ST</option>
                                        <option value="900">900 - Outros</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Unidade de Medida</label>
                                    <input type="text" id="unidade_medida" name="unidade_medida" class="form-control"
                                        maxlength="10" placeholder="UN">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i> Salvar Produto
                    </button>
                </div>
            </form>
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

<div class="toast-container" id="toastContainer"></div>
<script src="/js/cadastro_pratos.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/cadastro_pratos.js') ?>"></script>
<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>