const dadosSustentabilidade = JSON.parse(document.getElementById('sustainability-data').textContent);
const tabButtons = [...document.querySelectorAll('[data-sustainability-tab]')];

const chartTitle = document.querySelector('[data-chart-title]');
const chartDescription = document.querySelector('[data-chart-description]');
const scaleLabels = [...document.querySelectorAll('[data-scale-label]')];
const barScaleLabels = [...document.querySelectorAll('[data-bar-scale]')];
const primaryLine = document.querySelector('[data-primary-line]');
const secondaryLine = document.querySelector('[data-secondary-line]');
const primaryLegend = document.querySelector('[data-primary-legend]');
const secondaryLegend = document.querySelector('[data-secondary-legend]');
const periodTitle = document.querySelector('[data-period-title]');
const bars = document.querySelector('.bars');
const trendState = document.querySelector('[data-trend-state]');
const trendDescription = document.querySelector('[data-trend-description]');
const trendFactors = document.querySelector('[data-trend-factors]');
const trendProjection = document.querySelector('[data-trend-projection]');

const escapeHtml = (value) => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

const pontosGrafico = (evolucao, campo, min, max) => {
  const xInicial = 75;
  const xPasso = 178;
  const yTopo = 30;
  const altura = 285;

  return evolucao.map((linha, indice) => {
    const valor = Math.max(min, Math.min(max, Number(linha[campo])));
    const x = Math.round(xInicial + (xPasso * indice));
    const y = Math.round(yTopo + (((max - valor) / (max - min)) * altura));

    return `${x},${y}`;
  }).join(' ');
};

const renderizarBarras = (evolucao) => {
  bars.innerHTML = evolucao.map((linha) => `
    <div><span style="height:${Number(linha.barra)}%"></span><small>${escapeHtml(linha.mes)}</small></div>
  `).join('');
};

const renderizarFatores = (fatores) => {
  trendFactors.innerHTML = fatores.map((fator) => `<li>${escapeHtml(fator)}</li>`).join('');
};

const atualizarGrafico = (chave) => {
  const grafico = dadosSustentabilidade[chave];
  if (!grafico) return;

  tabButtons.forEach((button) => {
    button.classList.toggle('active', button.dataset.sustainabilityTab === chave);
  });

  chartTitle.textContent = grafico.titulo;
  chartDescription.textContent = grafico.descricao;
  primaryLine.setAttribute('points', pontosGrafico(grafico.evolucao, 'principal', grafico.min_principal, grafico.max_principal));
  secondaryLine.setAttribute('points', pontosGrafico(grafico.evolucao, 'secundaria', grafico.min_secundaria, grafico.max_secundaria));
  primaryLegend.textContent = grafico.legenda_principal;
  secondaryLegend.textContent = grafico.legenda_secundaria;
  periodTitle.textContent = grafico.titulo_periodo;

  scaleLabels.forEach((label, indice) => {
    label.textContent = grafico.escala[indice] ?? '';
  });

  barScaleLabels.forEach((label, indice) => {
    label.textContent = indice < grafico.escala.length ? grafico.escala[indice] : '0';
  });

  renderizarBarras(grafico.evolucao);
  trendState.textContent = grafico.tendencia.estado;
  trendDescription.textContent = grafico.tendencia.descricao;
  renderizarFatores(grafico.tendencia.fatores);
  trendProjection.textContent = grafico.tendencia.projecao;
};

tabButtons.forEach((button) => {
  button.addEventListener('click', () => atualizarGrafico(button.dataset.sustainabilityTab));
});
