<?php
// public/financeiro/importacao_ofx_conferencia.php

session_start();

$moduloAtual = 'financeiro.importacao_ofx';
$tituloPagina = 'Conferência de Importação OFX';
$tituloConteudo = 'Importação de OFX';

require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

// Verifica permissão
verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

$importacaoId = (int) ($_GET['id'] ?? 0);
if (!$importacaoId) {
    die('Importação inválida');
}

$db = getDB();

/**
 * =========================
 * Importação (cabeçalho)
 * =========================
 */
$stmt = $db->prepare("
    SELECT *
    FROM FinanceiroImportacaoOfx
    WHERE Id = :id
");
$stmt->execute(['id' => $importacaoId]);
$importacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$importacao) {
    die('Importação não encontrada');
}

/**
 * =========================
 * Lançamentos pendentes
 * =========================
 */
$stmt = $db->prepare("
    SELECT *
    FROM FinanceiroImportacaoOfxLancamento
    WHERE ImportacaoOfxId = :id
    AND Status = 'pendente'
    ORDER BY DataLancamento
");
$stmt->execute(['id' => $importacaoId]);
$lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * =========================
 * Categorias
 * =========================
 */
$categorias = $db->query("
    SELECT Id, Nome
    FROM FinanceiroCategorias
    WHERE Ativo = 1
    ORDER BY Nome
")->fetchAll(PDO::FETCH_ASSOC);

/**
 * =========================
 * Formas de pagamento
 * =========================
 */
$formasPagamento = $db->query("
    SELECT Id, Nome
    FROM FinanceiroFormasPagamento
    WHERE Ativo = 1
    ORDER BY Nome
")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="card card-default">

    <!-- HEADER -->
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold">
            Conferência da Importação OFX
        </h5>

        <a href="/financeiro/importar_ofx.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card-body">

        <!-- INFO IMPORTAÇÃO -->
        <div class="card-default mb-4 p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <small class="text-muted">Arquivo</small>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($importacao['NomeArquivo']) ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <small class="text-muted">Banco</small>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($importacao['Banco']) ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <small class="text-muted">Conta</small>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($importacao['Conta']) ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <small class="text-muted">Importado em</small>
                    <div class="fw-semibold">
                        <?= date('d/m/Y H:i', strtotime($importacao['DataImportacao'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS GLOBAIS -->
        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <label class="form-label">Categoria (aplicar a todos)</label>
                <select id="categoriaGlobal" class="form-select">
                    <option value="">Selecione</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['Id'] ?>">
                            <?= htmlspecialchars($c['Nome']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Forma de Pagamento (aplicar a todos)</label>
                <select id="formaPagamentoGlobal" class="form-select">
                    <option value="">Selecione</option>
                    <?php foreach ($formasPagamento as $f): ?>
                        <option value="<?= $f['Id'] ?>">
                            <?= htmlspecialchars($f['Nome']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

        </div>

        <!-- TABELA -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tabelaConferencia">
                <thead class="table-light">
                    <tr>
                        <th>
                            <input type="checkbox" id="checkAll">
                        </th>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Forma Pgto</th>
                        <th class="text-end">Valor</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($lancamentos as $l): ?>
                        <tr data-id="<?= $l['Id'] ?>">

                            <td>
                                <input type="checkbox" class="chk-lancamento" checked>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($l['DataLancamento'])) ?>
                            </td>

                            <td>
                                <span class="badge <?= $l['Tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?> px-3 py-2">
                                    <?= ucfirst($l['Tipo']) ?>
                                </span>
                            </td>

                            <td>
                                <input type="text" class="form-control form-control-sm descricao"
                                    value="<?= htmlspecialchars($l['Descricao']) ?>">
                            </td>

                            <td>
                                <select class="form-select form-select-sm categoria">
                                    <option value="">Selecione</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= $c['Id'] ?>">
                                            <?= htmlspecialchars($c['Nome']) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </td>

                            <td>
                                <select class="form-select form-select-sm forma-pagamento">
                                    <option value="">Selecione</option>
                                    <?php foreach ($formasPagamento as $f): ?>
                                        <option value="<?= $f['Id'] ?>">
                                            <?= htmlspecialchars($f['Nome']) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </td>

                            <td class="text-end">
                                <?= number_format($l['Valor'], 2, ',', '.') ?>
                            </td>

                        </tr>
                    <?php endforeach ?>

                </tbody>
            </table>
        </div>

        <!-- AÇÕES -->
        <div class="mt-4 d-flex justify-content-end gap-2">

            <button class="btn btn-outline-danger" id="btnCancelar">
                <i class="fas fa-times-circle"></i> Cancelar
            </button>

            <button class="btn btn-success fw-semibold" id="btnConfirmar">
                <i class="fas fa-check-circle"></i> Confirmar
            </button>

        </div>

    </div>
</div>

<script>
    const IMPORTACAO_ID = <?= (int) $importacaoId ?>;
</script>

<script
    src="/js/importacao_ofx_conferencia.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/importacao_ofx_conferencia.js') ?>"></script>