<section class="dashboard-panel dashboard-orders-panel" aria-labelledby="dashboard-orders-title">
    <div class="dashboard-panel-heading">
        <div>
            <span class="dashboard-eyebrow">Acompanhe a operação</span>
            <h2 id="dashboard-orders-title">Pedidos em aberto</h2>
        </div>
        <a class="dashboard-panel-link" href="/vendas/pdv.php">Abrir PDV <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="dashboard-orders-table-wrap">
        <table class="dashboard-orders-table">
            <thead>
                <tr>
                    <th scope="col">Pedido</th>
                    <th scope="col">Cliente / local</th>
                    <th scope="col">Aberto às</th>
                    <th scope="col" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody id="dashboard-open-orders" aria-live="polite">
                <tr><td colspan="4" class="dashboard-table-message">Carregando pedidos...</td></tr>
            </tbody>
        </table>
    </div>
</section>
