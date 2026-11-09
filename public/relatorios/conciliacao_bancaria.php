<?php
/**
 * Relatório de Conciliação Bancária
 * Padrão Alesys - Template base_protected_v2.php
 */

session_start();

// --------------------------------------------------------------------
// 1. Definições e configurações da página
// --------------------------------------------------------------------

$moduloAtual = 'relatorio.conciliacao';
$tituloPagina = 'Relatório de Conciliação Bancária';
$tituloConteudo = 'Relatório de Conciliação Bancária';
$page_icon = 'fa-file-invoice-dollar';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';


// --------------------------------------------------------------------
// 2. Autenticação e permissão
// --------------------------------------------------------------------
verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

// --------------------------------------------------------------------
// 3. Processamento e consulta ao banco de dados
// --------------------------------------------------------------------
try {
    $pdo = getDB();

    $filtro_data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
    $filtro_data_fim = $_GET['data_fim'] ?? date('Y-m-t');
    $filtro_conta = $_GET['conta_bancaria_id'] ?? '';
    $filtro_status = $_GET['status'] ?? '';

    $sql = "
        SELECT
            cb.id,
            cb.data_transacao,
            cb.data_conciliacao,
            cb.descricao,
            cb.valor,
            cb.tipo,
            cb.status,
            cb.referencia,
            cb.observacao,
            cb.created_at,
            cb.updated_at,
            c.id AS conta_id,
            c.nome AS conta_nome,
            c.banco AS conta_banco,
            c.agencia,
            c.conta
        FROM conciliacao_bancaria cb
        LEFT JOIN contas_bancarias c ON c.id = cb.conta_bancaria_id
        WHERE cb.data_transacao BETWEEN :data_inicio AND :data_fim
    ";

    $params = [
        ':data_inicio' => $filtro_data_inicio,
        ':data_fim' => $filtro_data_fim
    ];

    if (!empty($filtro_conta)) {
        $sql .= " AND cb.conta_bancaria_id = :conta_bancaria_id ";
        $params[':conta_bancaria_id'] = $filtro_conta;
    }

    if (!empty($filtro_status)) {
        $sql .= " AND cb.status = :status ";
        $params[':status'] = $filtro_status;
    }

    $sql .= " ORDER BY cb.data_transacao DESC, cb.id DESC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resumo
    $stmtTotal = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN tipo = 'C' THEN valor ELSE 0 END), 0) AS total_creditos,
            COALESCE(SUM(CASE WHEN tipo = 'D' THEN valor ELSE 0 END), 0) AS total_debitos,
            COALESCE(SUM(CASE WHEN tipo = 'C' THEN valor ELSE -valor END), 0) AS saldo
        FROM conciliacao_bancaria
        WHERE data_transacao BETWEEN :data_inicio AND :data_fim
    ");
    $stmtTotal->execute([
        ':data_inicio' => $filtro_data_inicio,
        ':data_fim' => $filtro_data_fim
    ]);
    $resumo = $stmtTotal->fetch(PDO::FETCH_ASSOC);

    // Lista de contas bancárias para filtro
    $stmtContas = $pdo->query("SELECT id, nome, banco FROM contas_bancarias WHERE ativo = 1 ORDER BY nome ASC");
    $contas = $stmtContas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erro conciliacao_bancaria.php: ' . $e->getMessage());
    $erro = 'Ocorreu um erro ao carregar os dados. Tente novamente mais tarde.';
    $registros = [];
    $resumo = ['total_creditos' => 0, 'total_debitos' => 0, 'saldo' => 0];
    $contas = [];
}

// --------------------------------------------------------------------
// 4. Captura do conteúdo principal
// --------------------------------------------------------------------
ob_start();
?>

<!-- Cabeçalho da página -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">
                    <i class="fas <?= htmlspecialchars($page_icon) ?> mr-2"></i>
                    <?= htmlspecialchars($page_title) ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/dashboard.php">Início</a></li>
                    <li class="breadcrumb-item"><a href="#">Relatórios</a></li>
                    <li class="breadcrumb-item active">Conciliação Bancária</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Conteúdo principal -->
<section class="content">
    <div class="container-fluid">

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Erro</h5>
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="form-filtros">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="data_inicio">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio"
                                       value="<?= htmlspecialchars($filtro_data_inicio) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="data_fim">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim"
                                       value="<?= htmlspecialchars($filtro_data_fim) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="conta_bancaria_id">Conta Bancária</label>
                                <select class="form-control select2" id="conta_bancaria_id" name="conta_bancaria_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($contas as $conta): ?>
                                        <option value="<?= htmlspecialchars($conta['id']) ?>"
                                            <?= ($filtro_conta == $conta['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($conta['nome'] . ' - ' . $conta['banco']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendente" <?= ($filtro_status == 'pendente') ? 'selected' : '' ?>>Pendente</option>
                                    <option value="conciliado" <?= ($filtro_status == 'conciliado') ? 'selected' : '' ?>>Conciliado</option>
                                    <option value="divergente" <?= ($filtro_status == 'divergente') ? 'selected' : '' ?>>Divergente</option>
                                    <option value="ignorado" <?= ($filtro_status == 'ignorado') ? 'selected' : '' ?>>Ignorado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Filtrar
                            </button>
                            <a href="conciliacao_bancaria.php" class="btn btn-secondary">
                                <i class="fas fa-eraser mr-1"></i> Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumo -->
        <div class="row">
            <div class="col-md-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>R$ <?= number_format((float) $resumo['total_creditos'], 2, ',', '.') ?></h3>
                        <p>Total Créditos</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-up"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>R$ <?= number_format((float) $resumo['total_debitos'], 2, ',', '.') ?></h3>
                        <p>Total Débitos</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-down"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>R$ <?= number_format((float) $resumo['saldo'], 2, ',', '.') ?></h3>
                        <p>Saldo</p>
                    </div>
                    <div class="icon"><i class="fas fa-balance-scale"></i></div>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lançamentos</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" id="btn-exportar-excel">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover" id="tabela-conciliacao">
                    <thead>
                        <tr>
                            <th>Data Transação</th>
                            <th>Conta</th>
                            <th>Descrição</th>
                            <th>Referência</th>
                            <th>Tipo</th>
                            <th class="text-right">Valor</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr data-id="<?= htmlspecialchars($registro['id']) ?>">
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($registro['data_transacao']))) ?></td>
                                    <td><?= htmlspecialchars($registro['conta_nome'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($registro['descricao']) ?></td>
                                    <td><?= htmlspecialchars($registro['referencia'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($registro['tipo'] == 'C'): ?>
                                            <span class="badge badge-success">Crédito</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Débito</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        R$ <?= number_format((float) $registro['valor'], 2, ',', '.') ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'badge-secondary';
                                        switch ($registro['status']) {
                                            case 'conciliado':
                                                $badgeClass = 'badge-success';
                                                break;
                                            case 'pendente':
                                                $badgeClass = 'badge-warning';
                                                break;
                                            case 'divergente':
                                                $badgeClass = 'badge-danger';
                                                break;
                                            case 'ignorado':
                                                $badgeClass = 'badge-dark';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($registro['status'])) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-info btn-sm btn-detalhes" title="Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm btn-conciliar" title="Conciliar">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum registro encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php
$content = ob_get_clean();

// --------------------------------------------------------------------
// 5. Scripts adicionais da página
// --------------------------------------------------------------------
$page_scripts = '<script src="/js/relatorio_conciliacao_bancaria.js"></script>';

// --------------------------------------------------------------------
// 6. Inclusão do template base
// --------------------------------------------------------------------
require __DIR__ . '/../templates/footer_protected_v2.php'; ?>