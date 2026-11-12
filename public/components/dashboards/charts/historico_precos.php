<?php
/* Histórico de preços de produtos vinculados a notas de compra. */
?>
<div class="chart-card">
    <div class="chart-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 id="tituloHistorico" class="mb-0">Histórico de preços</h6>
        <select id="selectProduto" class="form-select form-select-sm w-auto" aria-label="Selecionar produto" disabled>
            <option>Carregando produtos...</option>
        </select>
    </div>
    <div class="chart-card-body position-relative">
        <canvas id="chartHistorico"></canvas>
        <div id="historicoEstado" class="position-absolute top-50 start-50 translate-middle text-muted text-center d-none"></div>
    </div>
</div>

<script>
(() => {
    const seletor = document.getElementById('selectProduto');
    const titulo = document.getElementById('tituloHistorico');
    const estado = document.getElementById('historicoEstado');
    const chart = new Chart(document.getElementById('chartHistorico'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Preço médio',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: context => 'R$ ' + Number(context.raw).toLocaleString('pt-BR', {minimumFractionDigits: 2})
                } },
                emptyState: false
            },
            scales: {
                y: { ticks: { callback: value => 'R$ ' + Number(value).toLocaleString('pt-BR') } }
            }
        }
    });

    function mostrarEstado(mensagem) {
        estado.textContent = mensagem;
        estado.classList.remove('d-none');
        chart.canvas.classList.add('invisible');
    }

    function ocultarEstado() {
        estado.classList.add('d-none');
        chart.canvas.classList.remove('invisible');
    }

    async function carregarHistorico(productId) {
        seletor.disabled = true;
        titulo.textContent = 'Carregando histórico...';
        try {
            const resposta = await fetch('/api/get_historico_precos.php?product_id=' + encodeURIComponent(productId));
            const dados = await resposta.json();
            if (!resposta.ok || !dados.success) {
                throw new Error(dados.message || 'Não foi possível carregar o histórico.');
            }

            chart.data.labels = dados.labels || [];
            chart.data.datasets[0].data = dados.values || [];
            chart.update();
            titulo.textContent = 'Histórico - ' + dados.productName;

            if (!dados.labels || dados.labels.length === 0) {
                mostrarEstado('Este produto ainda não possui histórico de preços em notas ativas.');
            } else {
                ocultarEstado();
            }
        } catch (error) {
            titulo.textContent = 'Histórico de preços';
            mostrarEstado(error.message || 'Erro ao carregar histórico.');
        } finally {
            seletor.disabled = false;
        }
    }

    fetch('/api/get_produtos_dashboard.php')
        .then(async resposta => {
            const dados = await resposta.json();
            if (!resposta.ok || !dados.success) {
                throw new Error(dados.message || 'Não foi possível carregar os produtos.');
            }
            return dados.data || [];
        })
        .then(produtos => {
            seletor.replaceChildren();
            if (produtos.length === 0) {
                seletor.add(new Option('Sem produtos com compras', ''));
                seletor.disabled = true;
                titulo.textContent = 'Histórico de preços';
                mostrarEstado('Ainda não há produtos vinculados a notas de compra ativas.');
                return;
            }

            produtos.forEach(produto => seletor.add(new Option(produto.Nome, produto.Codigo)));
            seletor.disabled = false;
            seletor.addEventListener('change', () => carregarHistorico(seletor.value));
            carregarHistorico(seletor.value);
        })
        .catch(error => {
            seletor.replaceChildren(new Option('Erro ao carregar produtos', ''));
            seletor.disabled = true;
            mostrarEstado(error.message || 'Não foi possível carregar os produtos.');
        });
})();
</script>
