const filters = {
  search: document.querySelector('[data-filter-search]'),
  level: document.querySelector('[data-filter-level]'),
  status: document.querySelector('[data-filter-status]'),
  building: document.querySelector('[data-filter-building]'),
};

const rows = [...document.querySelectorAll('[data-alert-row]')];

const applyFilters = () => {
  const term = (filters.search?.value || '').trim().toLowerCase();

  rows.forEach((row) => {
    const match =
      row.dataset.search.includes(term) &&
      (!filters.level?.value || row.dataset.level === filters.level.value) &&
      (!filters.status?.value || row.dataset.status === filters.status.value) &&
      (!filters.building?.value || row.dataset.building.includes(filters.building.value));

    row.classList.toggle('hidden', !match);
  });
};

Object.values(filters).forEach((filter) => {
  if (!filter) return;
  filter.addEventListener('input', applyFilters);
  filter.addEventListener('change', applyFilters);
});

document.querySelectorAll('[data-resolve-alert]').forEach((button) => {
  button.addEventListener('click', async () => {
    const id = button.dataset.resolveAlert;
    button.disabled = true;

    try {
      const formData = new FormData();
      formData.append('id', id);
      appendCsrf(formData);

      const resposta = await fetch('alertas-resolver.php', {
        method: 'POST',
        body: formData,
      });

      const resultado = await resposta.json();
      if (resultado.sucesso) {
        window.location.reload();
      }
    } catch (erro) {
      console.error(erro);
      button.disabled = false;
    }
  });
});

if (window.lucide) window.lucide.createIcons();
applyFilters();
