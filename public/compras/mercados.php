<?php
/* /public/compras/mercados.php */

session_start();

$moduloAtual = 'compras.mercados';
$tituloPagina = 'Mercados';
$tituloConteudo = 'Gerenciamento de Fornecedores / Mercados';

require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    $mercados = $pdo->query("
        SELECT 
            Codigo,
            Nome,
            CNPJ,
            InscricaoEstadual,
            Email
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

            <button id="btnNovoMercado" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i>
                Novo Mercado
            </button>

            <button id="btnExportar" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-export me-1"></i>
                Exportar
            </button>

        </div>
    </div>

    <!-- TABELA -->
    <div class="card card-default">
        <div class="card-body">

            <div class="table-responsive">
                <table id="tabelaMercados" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cód.</th>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Inscrição Estadual</th>
                            <th>Email</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mercados as $mercado): ?>
                            <tr>
                                <td><?= $mercado['Codigo'] ?></td>
                                <td><?= htmlspecialchars($mercado['Nome']) ?></td>
                                <td><?= htmlspecialchars($mercado['CNPJ']) ?></td>
                                <td><?= htmlspecialchars($mercado['InscricaoEstadual']) ?></td>
                                <td><?= htmlspecialchars($mercado['Email']) ?></td>

                                <td class="text-end acoes-tabela">

                                    <button class="btn-action btn-edit btn-editar"
                                        data-id="<?= $mercado['Codigo'] ?>" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <button class="btn-action btn-deactivate btn-desativar"
                                        data-id="<?= $mercado['Codigo'] ?>" title="Desativar">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>

                                    <button class="btn-action btn-delete btn-excluir"
                                        data-id="<?= $mercado['Codigo'] ?>" title="Excluir">
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

<?php require __DIR__ . '/partials/modal_mercado.php'; ?>

<?php require_once __DIR__ . '/../templates/footer_protected_v2.php'; ?>

<script src="/js/mercados.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/mercados.js') ?>"></script>