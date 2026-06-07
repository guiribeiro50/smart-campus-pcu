let sensoresAtuais = [];

const grid = document.querySelector('[data-sensor-grid]');
const filtroEdificio = document.querySelector('[data-filter-building]');
const filtroTipo = document.querySelector('[data-filter-type]');
const filtroPesquisa = document.querySelector('[data-filter-search]');
const ultimaAtualizacao = document.querySelector('[data-last-update]');
const botaoRecolherLeituras = document.querySelector('[data-collect-readings]');

const escapeHtml = (value) => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

const atualizarResumo = (dados) => {
  document.querySelector('[data-summary-total]').textContent = dados.total_sensores;
  document.querySelector('[data-summary-active]').textContent = dados.ativos;
  document.querySelector('[data-summary-active-text]').textContent = dados.percentagem_ativos;
  document.querySelector('[data-summary-critical]').textContent = dados.criticos;
  document.querySelector('[data-summary-warning]').textContent = dados.avisos;
  if (ultimaAtualizacao) {
    ultimaAtualizacao.textContent = `Última atualização: ${dados.atualizado_em}`;
  }
};

const iconeLucide = (nome) => {
  const mapa = {
    thermometer: 'thermometer',
    wind: 'wind',
    droplets: 'droplets',
    zap: 'zap',
    gauge: 'gauge',
    check: 'check-circle',
    alert: 'alert-circle',
  };
  return mapa[nome] || 'activity';
};

const sensorCard = (sensor) => `
  <article class="sensor-card" data-building="${escapeHtml(sensor.edificio_key)}" data-type="${escapeHtml(sensor.tipo)}" data-search="${escapeHtml(sensor.pesquisa)}">
    <header>
      <span class="sensor-icon ${escapeHtml(sensor.classe)}"><i data-lucide="${iconeLucide(sensor.icone)}"></i></span>
      <div><h3>${escapeHtml(sensor.codigo)}</h3><p>${escapeHtml(sensor.tipo_label)}</p></div>
      <span class="status ${escapeHtml(sensor.estado)}">${escapeHtml(sensor.estado_label)}</span>
    </header>
    <strong class="value">${escapeHtml(sensor.valor_formatado)} <small>${escapeHtml(sensor.unidade)}</small></strong>
    <p>Recurso: <b>${escapeHtml(sensor.localizacao)}</b></p>
    <p>Edifício: <b>${escapeHtml(sensor.edificio)}</b></p>
    <p>Piso: <b>${escapeHtml(sensor.piso)}</b></p>
    <small>Atualizado: ${escapeHtml(sensor.atualizado)}</small>
  </article>
`;

const aplicarFiltros = () => {
  const edificio = filtroEdificio.value;
  const tipo = filtroTipo.value;
  const pesquisa = filtroPesquisa.value.trim().toLowerCase();

  if (!sensoresAtuais.length && grid.querySelectorAll('.sensor-card').length) {
    grid.querySelectorAll('.sensor-card').forEach((card) => {
      const match =
        (!edificio || card.dataset.building === edificio) &&
        (!tipo || card.dataset.type === tipo) &&
        (!pesquisa || (card.dataset.search || '').includes(pesquisa));
      card.classList.toggle('hidden', !match);
    });
    return;
  }

  const filtrados = sensoresAtuais.filter((sensor) => {
    return (!edificio || sensor.edificio_key === edificio) &&
      (!tipo || sensor.tipo === tipo) &&
      (!pesquisa || sensor.pesquisa.includes(pesquisa));
  });

  if (filtrados.length === 0) {
    grid.innerHTML = '<p class="empty-state">Nenhum sensor corresponde aos filtros selecionados.</p>';
    return;
  }

  grid.innerHTML = filtrados.map(sensorCard).join('');
  if (window.lucide) window.lucide.createIcons();
};

const carregarSensores = async () => {
  try {
    const resposta = await fetch('monitorizacao-ajax.php', { headers: { Accept: 'application/json' } });
    if (!resposta.ok) throw new Error('Resposta AJAX inválida');

    const dados = await resposta.json();
    sensoresAtuais = dados.sensores;
    atualizarResumo(dados);
    aplicarFiltros();
  } catch (erro) {
    console.error('Não foi possível atualizar os sensores IoT.', erro);
    aplicarFiltros();
  }
};

const recolherLeituras = async () => {
  if (!botaoRecolherLeituras) return;

  const textoOriginal = botaoRecolherLeituras.textContent;

  try {
    botaoRecolherLeituras.disabled = true;
    botaoRecolherLeituras.textContent = 'A recolher...';

    const formData = new FormData();
    appendCsrf(formData);
    const resposta = await fetch('recolher-dados.php', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: formData,
    });
    if (!resposta.ok) throw new Error('Recolha inválida');

    const resultado = await resposta.json();
    if (!resultado.sucesso) throw new Error(resultado.mensagem || 'Não foi possível recolher leituras');

    await carregarSensores();
    botaoRecolherLeituras.textContent = 'Leituras atualizadas';
  } catch (erro) {
    console.error('Não foi possível recolher novas leituras.', erro);
    botaoRecolherLeituras.textContent = 'Erro ao atualizar';
  } finally {
    setTimeout(() => {
      botaoRecolherLeituras.disabled = false;
      botaoRecolherLeituras.textContent = textoOriginal;
    }, 1400);
  }
};

[filtroEdificio, filtroTipo, filtroPesquisa].forEach((filtro) => {
  if (!filtro) return;
  filtro.addEventListener('input', aplicarFiltros);
  filtro.addEventListener('change', aplicarFiltros);
});

if (botaoRecolherLeituras) {
  botaoRecolherLeituras.addEventListener('click', recolherLeituras);
}

if (window.lucide) window.lucide.createIcons();
carregarSensores();
setInterval(carregarSensores, 60000);
