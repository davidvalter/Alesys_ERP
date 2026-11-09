<?php
/* /public/components/sidebar_v2.php */

function renderSidebar($currentPage = '')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../conf/urls_redirect.php';

    $isAdmin = ($_SESSION['Usuario']['perfil_nome'] ?? '') === 'admin';

    $menuCompleto = [
        'dashboard' => [
            'icon' => 'fa-house',
            'text' => 'Início',
            'link' => url('dashboard.php'),
            'modulo' => 'dashboard',
            'active' => false
        ],
        'vendas' => [
            'icon' => 'fa-cash-register',
            'text' => 'Vendas',
            'link' => vendas_url('/'),
            'modulo' => 'vendas',
            'active' => ($currentPage === 'vendas')
        ],
        'compras' => [
            'icon' => 'fa-cart-shopping',
            'text' => 'Compras',
            'link' => compras_url('/'),
            'modulo' => 'compras',
            'active' => ($currentPage === 'vendas')
        ],
        'estoque' => [
            'icon' => 'fa-boxes-stacked',
            'text' => 'Estoque',
            'link' => estoque_url('/'),
            'modulo' => 'estoque',
            'active' => ($currentPage === 'estoque')
        ],
        'financeiro' => [
            'icon' => 'fa-chart-line',
            'text' => 'Financeiro',
            'link' => financeiro_url('/'),
            'modulo' => 'financeiro',
            'active' => ($currentPage === 'financeiro')
        ],
        'relatorios' => [
            'icon' => 'fa-file-lines',
            'text' => 'Relatórios',
            'link' => relatorios_url('/'),
            'modulo' => 'relatorios',
            'active' => ($currentPage === 'relatorios')
        ],
        'configuracoes' => [
            'icon' => 'fa-gear',
            'text' => 'Configurações',
            'link' => url('configuracoes.php'),
            'modulo' => 'configuracoes',
            'active' => ($currentPage === 'configuracoes'),
            'admin_only' => true,
            'submenu' => [
                'empresas' => [
                    'text' => 'Empresas',
                    'link' => admin_url('empresas/'),
                    'modulo' => 'admin.empresas',
                    'active' => ($currentPage === 'empresas')
                ],
                'perfis' => [
                    'text' => 'Perfis',
                    'link' => admin_url('perfis/'),
                    'modulo' => 'admin.perfis',
                    'active' => ($currentPage === 'perfis')
                ],
                'usuarios' => [
                    'text' => 'Usuários',
                    'link' => admin_url('usuarios/'),
                    'modulo' => 'admin.usuarios',
                    'active' => ($currentPage === 'usuarios')
                ],
                'permissoes' => [
                    'text' => 'Permissões',
                    'link' => admin_url('permissoes/'),
                    'modulo' => 'admin.permissoes',
                    'active' => ($currentPage === 'permissoes')
                ],
                'parametros' => [
                    'text' => 'Parâmetros',
                    'link' => admin_url('parametros/'),
                    'modulo' => 'admin.parametros',
                    'active' => ($currentPage === 'parametros')
                ]
            ]
        ]
    ];

    $menuFiltrado = array_filter($menuCompleto, function ($item) use ($isAdmin) {
        if ($isAdmin && !($item['admin_only'] ?? false))
            return true;
        if ($item['admin_only'] ?? false)
            return $isAdmin;
        return in_array($item['modulo'], $_SESSION['Usuario']['permissoes'] ?? []);
    });
    ?>

    <aside class="sidebar-saas d-flex flex-column">

        <!-- Logo -->
        <div class="sidebar-header d-flex align-items-center justify-content-center">
            <?php
            $logoPath = '/uploads/logo/logo.png';
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath)) {
                $logoPath = '/uploads/logo/logo.jpg';
            }
            ?>

            <img src="<?= $logoPath ?>" class="sidebar-logo" alt="Logo">
        </div>

        <!-- Menu -->
        <nav class="sidebar-nav flex-grow-1">
            <?php foreach ($menuFiltrado as $item): ?>

                <?php if (isset($item['submenu'])): ?>
                    <?php
                    $subPermitidos = $isAdmin ? $item['submenu'] : array_filter(
                        $item['submenu'],
                        fn($s) => in_array($s['modulo'], $_SESSION['Usuario']['permissoes'] ?? [])
                    );

                    if (!$subPermitidos)
                        continue;
                    ?>

                    <div class="nav-group">
                        <a class="nav-link has-submenu <?= $item['active'] ? 'active' : '' ?>" data-bs-toggle="collapse"
                            href="#submenu-<?= $item['modulo'] ?>">

                            <i class="fas <?= $item['icon'] ?>"></i>
                            <span><?= $item['text'] ?></span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>

                        <div class="collapse submenu <?= $item['active'] ? 'show' : '' ?>" id="submenu-<?= $item['modulo'] ?>">

                            <?php foreach ($subPermitidos as $sub): ?>
                                <a href="<?= $sub['link'] ?>" class="submenu-link <?= $sub['active'] ? 'active' : '' ?>">
                                    <?= $sub['text'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php else: ?>

                    <a href="<?= $item['link'] ?>" class="nav-link <?= $item['active'] ? 'active' : '' ?>">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <span><?= $item['text'] ?></span>
                    </a>

                <?php endif; ?>

            <?php endforeach; ?>
        </nav>

        <!-- Rodapé -->
        <div class="sidebar-footer">
            <small>© <?= date('Y') ?> Alesys ERP</small>
        </div>

    </aside>

<?php } ?>