// public/js/financeiro_balanco.js
document.addEventListener('DOMContentLoaded', () => {
    initBalancoPage();
});

function initBalancoPage() {
    const btnAtualizar = document.getElementById('btnAtualizarBalanco');
    const btnExportar = document.getElementById('btnExportarCsv');

    carregarBalanco();

    if (btnAtualizar) btnAtualizar.addEventListener('click', (e) => { 
        e.preventDefault(); 
        btnAtualizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        carregarBalanco(); 
        btnAtualizar.innerHTML = '<i class="fas fa-filter"></i>';
    });

    if (btnExportar) btnExportar.addEventListener('click', exportarCsv);
}

async function carregarBalanco() {
    const mesEl = document.getElementById('selectMes');
    const anoEl = document.getElementById('selectAno');
    const mes = mesEl ? mesEl.value : (new Date()).getMonth() + 1;
    const ano = anoEl ? anoEl.value : (new Date()).getFullYear();

    const tabela = document.getElementById('tabelaBalanco');
    const thead = tabela.querySelector('thead tr');
    const tbody = document.getElementById('balancoBody');

    tbody.innerHTML = '<tr><td colspan="32" class="text-center">Carregando dados...</td></tr>';

    try {
        const res = await fetch(`../api/get_balanco_mensal.php?mes=${encodeURIComponent(mes)}&ano=${encodeURIComponent(ano)}`);
        const text = await res.text();

        // tenta parse seguro: se veio HTML, loga o HTML para debug e lança erro
        let json;
        try {
            json = JSON.parse(text);
        } catch (err) {
            console.error('Resposta da API não é JSON. Conteúdo recebido:');
            console.log(text);
            throw new Error('Resposta da API não é JSON. Veja console para detalhes.');
        }

        if (!json.success) {
            console.error('API retornou success=false:', json);
            tbody.innerHTML = `<tr><td colspan="32" class="text-center">Erro: ${json.message || 'Erro ao carregar dados'}</td></tr>`;
            return;
        }

        const dias = json.dias || [];
        const fmt = v => (Number(v || 0)).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Cabeçalho (limpa dias anteriores)
        while (thead.children.length > 1) thead.removeChild(thead.lastChild);
        dias.forEach(d => {
            const th = document.createElement('th');
            th.textContent = d;
            th.style.textAlign = 'right';
            thead.appendChild(th);
        });

        tbody.innerHTML = '';

        // helper para criar linha de categoria/total
        function criarLinha(nome, valores, corFundo = '#fff') {
            const tr = document.createElement('tr');
            const tdNome = document.createElement('td');
            tdNome.textContent = nome;
            tdNome.style.position = 'sticky';
            tdNome.style.left = '0';
            tdNome.style.background = corFundo;
            tdNome.style.textAlign = 'left';
            tr.appendChild(tdNome);

            dias.forEach((_, dIndex) => {
                const td = document.createElement('td');
                td.style.textAlign = 'right';
                const val = valores[dIndex + 1] || 0;
                td.textContent = (val >= 0 ? 'R$ ' : '-R$ ') + fmt(Math.abs(val));
                if (val > 0) {
                    td.classList.add('positivo');   // verde
                } else if (val < 0) {
                    td.classList.add('negativo');   // vermelho
                }
                // ZERO → sem classe → cor padrão

                tr.appendChild(td);
            });

            return tr;
        }

        // render entradas
        if (json.categorias_entrada && json.categorias_entrada.length) {
            json.categorias_entrada.forEach(cat => {
                tbody.appendChild(criarLinha(cat.nome, cat.valores, '#f8fff8'));
            });
        }

        // total entradas
        tbody.appendChild(criarLinha('Total de Entradas', json.totais_entradas_dia, '#e8f8e8'));

        // render saídas
        if (json.categorias_saida && json.categorias_saida.length) {
            json.categorias_saida.forEach(cat => {
                tbody.appendChild(criarLinha(cat.nome, cat.valores, '#fff8f8'));
            });
        }

        // total saídas
        tbody.appendChild(criarLinha('Total de Saídas', json.totais_saidas_dia, '#f8e8e8'));

        // separador visual antes do balanço diário
        const sep = document.createElement('tr');
        const sepTd = document.createElement('td');
        sepTd.colSpan = dias.length + 1;
        sepTd.style.height = '8px';
        sepTd.style.borderTop = '2px solid #ddd';
        sepTd.style.background = 'transparent';
        sep.appendChild(sepTd);
        tbody.appendChild(sep);

        // Balanço Diário
        const trBal = document.createElement('tr');
        const tdBal = document.createElement('td');
        tdBal.textContent = 'Balanço Diário';
        tdBal.style.position = 'sticky';
        tdBal.style.left = '0';
        tdBal.style.background = '#f2f2f2';
        trBal.appendChild(tdBal);

        dias.forEach((_, i) => {
            const td = document.createElement('td');
            const v = json.balanco_diario[i + 1] || 0;
            td.style.textAlign = 'right';
            td.textContent = 'R$ ' + fmt(v);
            if (v < 0) td.style.color = 'red';

            if (v > 0) {
                td.classList.add('positivo');
            } else if (v < 0) {
                td.classList.add('negativo');
            }

            trBal.appendChild(td);
        });
        tbody.appendChild(trBal);

        // Balanço acumulado
        const trAc = document.createElement('tr');
        const tdAc = document.createElement('td');
        tdAc.textContent = 'Balanço Acumulado';
        tdAc.style.position = 'sticky';
        tdAc.style.left = '0';
        tdAc.style.background = '#ddd';
        trAc.appendChild(tdAc);

        let acumulado = 0;
        dias.forEach((_, i) => {
            const td = document.createElement('td');
            const v = json.balanco_diario[i + 1] || 0;
            acumulado += v;
            td.style.textAlign = 'right';
            td.textContent = 'R$ ' + fmt(acumulado);
            if (acumulado < 0) td.style.color = 'red';

            if (acumulado > 0) {
                td.classList.add('positivo');
            } else if (acumulado < 0) {
                td.classList.add('negativo');
            }

            trAc.appendChild(td);
        });
        tbody.appendChild(trAc);

    } catch (err) {
        console.error('Erro ao carregar balanço:', err);
        tbody.innerHTML = '<tr><td colspan="32" class="text-center">Erro ao carregar dados</td></tr>';
    }
}

function exportarCsv(e) {
    e.preventDefault();
    const tabela = document.getElementById('tabelaBalanco');
    if (!tabela) return;
    let csv = [];
    tabela.querySelectorAll('tr').forEach(r => {
        let cols = [];
        r.querySelectorAll('th, td').forEach(c => cols.push('"' + c.innerText.replace(/"/g, '""') + '"'));
        csv.push(cols.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `balanco_${Date.now()}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
}
