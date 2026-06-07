const filters = {
  query: document.querySelector('[data-filter-query]'),
  building: document.querySelector('[data-filter-building]'),
  type: document.querySelector('[data-filter-type]'),
  status: document.querySelector('[data-filter-status]'),
};

const sensorRows = [...document.querySelectorAll('[data-sensor-row]')];
const visibleCount = document.querySelector('[data-visible-count]');
const emptyRow = document.querySelector('.empty-row');
const refreshAllBtn = document.querySelector('[data-refresh-all]');

const applyFilters = () => {
  if (!filters.query) return;

  let matches = 0;
  const termo = filters.query.value.trim().toLowerCase();

  sensorRows.forEach((row) => {
    const isMatch =
      row.dataset.search.includes(termo) &&
      (!filters.building.value || row.dataset.building === filters.building.value) &&
      (!filters.type.value || row.dataset.type === filters.type.value) &&
      (!filters.status.value || row.dataset.status === filters.status.value);

    row.classList.toggle('hidden', !isMatch);
    if (isMatch) matches += 1;
  });

  if (visibleCount) visibleCount.textContent = String(matches);
  if (emptyRow) emptyRow.classList.toggle('visible', matches === 0 && sensorRows.length > 0);
};

Object.values(filters).forEach((filter) => {
  if (!filter) return;
  filter.addEventListener('input', applyFilters);
  filter.addEventListener('change', applyFilters);
});

const sensorAction = async (codigo, acao) => {
  const formData = new FormData();
  formData.append('codigo', codigo);
  formData.append('acao', acao);
  appendCsrf(formData);

  const resposta = await fetch('sensores-acao.php', {
    method: 'POST',
    body: formData,
  });

  if (!resposta.ok) {
    throw new Error('Ação inválida');
  }

  return resposta.json();
};

document.querySelectorAll('[data-toggle-sensor]').forEach((botao) => {
  botao.addEventListener('click', async () => {
    const linha = botao.closest('[data-sensor-row]');
    if (!linha) return;

    botao.disabled = true;

    try {
      const resultado = await sensorAction(linha.dataset.codigo, 'toggle');
      if (resultado.sucesso) {
        window.location.reload();
      }
    } catch (erro) {
      console.error(erro);
    } finally {
      botao.disabled = false;
    }
  });
});

document.querySelectorAll('[data-refresh-sensor]').forEach((botao) => {
  botao.addEventListener('click', async () => {
    const linha = botao.closest('[data-sensor-row]');
    if (!linha) return;

    botao.disabled = true;

    try {
      await sensorAction(linha.dataset.codigo, 'refresh');
    } catch (erro) {
      console.error(erro);
    } finally {
      botao.disabled = false;
    }
  });
});

if (refreshAllBtn) {
  refreshAllBtn.addEventListener('click', async () => {
    const textoOriginal = refreshAllBtn.innerHTML;
    refreshAllBtn.disabled = true;
    refreshAllBtn.textContent = 'A atualizar...';

    try {
      const formData = new FormData();
      appendCsrf(formData);
      const resposta = await fetch('recolher-dados.php', {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: formData,
      });
      const resultado = await resposta.json();
      if (!resultado.sucesso) throw new Error(resultado.mensagem || 'Erro');
      window.location.reload();
    } catch (erro) {
      console.error(erro);
      refreshAllBtn.textContent = 'Erro ao atualizar';
      setTimeout(() => {
        refreshAllBtn.innerHTML = textoOriginal;
        if (window.lucide) window.lucide.createIcons();
      }, 1500);
    } finally {
      refreshAllBtn.disabled = false;
    }
  });
}

if (window.lucide) window.lucide.createIcons();
applyFilters();
