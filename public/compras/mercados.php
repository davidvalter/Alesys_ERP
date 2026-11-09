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

<!-- ====================== MODAL MERCADO ====================== -->
<div id="modalMercado" class="modal fade" tabindex="-1" aria-labelledby="modalMercadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form id="formMercado">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMercadoLabel">
                        <i class="fas fa-store me-2"></i>
                        <span id="modalTitle">Novo Mercado</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <input type="hidden" id="mercadoCodigo">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nome*</label>
                            <input type="text" id="nome" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Razão Social*</label>
                            <input type="text" id="razaoSocial" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CNPJ*</label>
                            <input type="text" id="cnpj" class="form-control cnpj-mask" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Inscrição Estadual</label>
                            <input type="text" id="inscricaoEstadual" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" id="telefone" class="form-control phone-mask">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Endereço</label>
                            <textarea id="endereco" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">CEP</label>
                            <input type="text" id="cep" class="form-control cep-mask">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Município</label>
                            <input type="text" id="municipio" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select id="estado" class="form-select">
                                <option value="">Selecione</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="fas fa-save me-1"></i>
                        Salvar
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer_protected_v2.php'; ?>

<script src="/js/mercados.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/mercados.js') ?>"></script>