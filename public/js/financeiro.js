/* public/js/financeiro.js */

let tabela;

// ==============================
// Inicialização
// ==============================
document.addEventListener('DOMContentLoaded', () => {

  $.fn.dataTable.ext.errMode = 'none';

  tabela = $('#tabelaBalanco').DataTable({

    pageLength: 20,

    lengthMenu: [10, 20, 50, 100],

    order: [[0, 'desc']],

    autoWidth: false,

    columnDefs: [
      { width: "110px", targets: 0 },   // Data
      { width: "120px", targets: 1 },  // Conta
      { width: "150px", targets: 2 },  // Categoria
      { width: "auto", targets: 3 },   // Descrição (maior)
      { width: "90px", targets: 4 },   // Tipo
      { width: "120px", targets: 5 },  // Valor bruto
      { width: "110px", targets: 6 },  // Taxas
      { width: "130px", targets: 7 }   // Valor líquido
    ],

    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
    }

  });

  carregarBalanco();

  document.getElementById('btnFiltrar').addEventListener('click', () => {
    carregarBalanco();
  });

});


// ==============================
// 1. CARREGAR BALANÇO
// ==============================
async function carregarBalanco() {

  const mes = parseInt(document.getElementById('mes').value);
  const ano = parseInt(document.getElementById('ano').value);
  const conta = document.getElementById('conta').value;

  try {

    const atualReq = fetch(`../api/get_balanco.php?mes=${mes}&ano=${ano}&conta=${conta}`);

    // Mês anterior
    let mesAnterior = mes - 1;
    let anoAnterior = ano;

    if (mesAnterior === 0) {
      mesAnterior = 12;
      anoAnterior--;
    }

    const anteriorReq = fetch(`../api/get_balanco.php?mes=${mesAnterior}&ano=${anoAnterior}&conta=${conta}`);

    const [resAtual, resAnterior] = await Promise.all([atualReq, anteriorReq]);

    const dataAtual = await resAtual.json();
    const dataAnterior = await resAnterior.json();

    if (!dataAtual.data || dataAtual.data.length === 0) {

      tabela.clear();

      tabela.row.add([
        '',
        '',
        '',
        'Nenhum lançamento encontrado para o período selecionado.',
        '',
        '',
        '',
        ''
      ]);

      tabela.draw();

      mostrarAviso("Nenhum lançamento encontrado para o período selecionado.");

      return;
    }

    tabela.clear();

    let totalEntradas = 0;
    let totalSaidas = 0;
    let totalDiferencaAtual = 0;

    dataAtual.data.forEach(l => {

      const valorBruto = parseFloat(l.ValorBruto);
      const taxas = parseFloat(l.Taxas);
      const valorLiquido = parseFloat(l.ValorLiquido);

      tabela.row.add([
        l.DataBaixa,
        l.Conta,
        l.Categoria,
        l.Descricao,
        l.Tipo,
        formatarMoeda(valorBruto),
        formatarMoeda(taxas),
        formatarMoeda(valorLiquido)
      ]);

      const valor = Math.abs(valorLiquido);

      if (l.Tipo === 'entrada') {
        totalEntradas += valor;
      } else {
        totalSaidas += valor;
      }

    });

    tabela.draw();

    totalDiferencaAtual = totalEntradas - totalSaidas;

    // ==============================
    // Atualiza resumo
    // ==============================
    document.getElementById('totalEntradas').innerText = formatarMoeda(totalEntradas);
    document.getElementById('totalSaidas').innerText = formatarMoeda(totalSaidas);
    document.getElementById('totalDiferenca').innerText = formatarMoeda(totalDiferencaAtual);


    // ==============================
    // Cálculo mês anterior
    // ==============================

    let totalDiferencaAnterior = 0;

    if (dataAnterior.success) {

      let eAnt = 0;
      let sAnt = 0;

      dataAnterior.data.forEach(l => {

        const valorAnt = Math.abs(parseFloat(l.ValorLiquido));

        if (l.Tipo === 'entrada') {
          eAnt += valorAnt;
        } else {
          sAnt += valorAnt;
        }

      });

      totalDiferencaAnterior = eAnt - sAnt;

    }

    // ==============================
    // % Variação
    // ==============================

    const percent = calcularVariacaoPercentual(totalDiferencaAtual, totalDiferencaAnterior);

    aplicarVariacaoVisual(percent);

  } catch (err) {

    console.error(err);

    tabela.clear();

    tabela.row.add([
      'Erro',
      '-',
      '-',
      'Erro ao carregar dados',
      '-',
      '-',
      '-',
      '-'
    ]);

    tabela.draw();

  }

}


// ==============================
// Formatar moeda
// ==============================

function formatarMoeda(valor) {

  return valor.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  });

}


// ==============================
// 1.1 Cálculo de variação
// ==============================

function calcularVariacaoPercentual(atual, anterior) {

  if (anterior === 0) {
    return atual >= 0 ? 100 : -100;
  }

  return ((atual - anterior) / Math.abs(anterior)) * 100;

}


// ==============================
// Aplicar visual da variação
// ==============================

function aplicarVariacaoVisual(percent) {

  const el = document.getElementById('percentVariacao');

  const formatted = percent.toFixed(1).replace('.', ',') + '%';

  if (percent > 0) {

    el.innerHTML = `↑ ${formatted}`;
    el.style.color = '#009900';

  } else if (percent < 0) {

    el.innerHTML = `↓ ${formatted}`;
    el.style.color = '#cc0000';

  } else {

    el.innerHTML = `0%`;
    el.style.color = '#666';

  }

}


//MOSTRAR AVISOS

function mostrarAviso(mensagem) {

  const container = document.getElementById('toast-container');

  const toast = document.createElement('div');
  toast.className = 'toast toast-warning';
  toast.innerText = mensagem;

  container.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 4000);

}