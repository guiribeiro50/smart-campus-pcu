const sidebarOpen = document.querySelector('[data-sidebar-open]');
const sidebarClose = document.querySelector('[data-sidebar-close]');
const DASHBOARD_REFRESH_MS = 60000;

sidebarOpen?.addEventListener('click', () => document.body.classList.add('sidebar-open'));
sidebarClose?.addEventListener('click', () => document.body.classList.remove('sidebar-open'));

const atualizarDescricaoResumo = (elemento, card) => {
  elemento.className = `summary-description ${card.descricao_classe || ''}`.trim();
  elemento.textContent = card.descricao || '';
};

const atualizarResumo = (resumo = []) => {
  resumo.forEach((card) => {
    const elemento = document.querySelector(`[data-summary-card="${card.chave}"]`);
    if (!elemento) return;

    const valor = elemento.querySelector('[data-summary-value]');
    const descricao = elemento.querySelector('[data-summary-description]');

    if (valor) valor.textContent = card.valor;
    if (descricao) atualizarDescricaoResumo(descricao, card);
  });
};

const criarAlerta = (alerta) => {
  const item = document.createElement('section');
  item.className = `alert-item ${alerta.classe || ''}`.trim();

  const icon = document.createElement('i');
  icon.setAttribute('data-lucide', 'alert-triangle');

  const content = document.createElement('div');
  const titulo = document.createElement('h3');
  titulo.textContent = alerta.titulo || '';
  const detalhe = document.createElement('p');
  detalhe.textContent = alerta.detalhe || '';
  const nivel = document.createElement('span');
  nivel.textContent = alerta.nivel || '';

  content.append(titulo, detalhe, nivel);
  item.append(icon, content);
  return item;
};

const atualizarAlertas = (alertas = []) => {
  const lista = document.querySelector('[data-alerts-list]');
  if (!lista) return;

  lista.textContent = '';

  if (!alertas.length) {
    const vazio = document.createElement('p');
    vazio.className = 'empty-panel-message';
    vazio.textContent = 'Sem alertas pendentes neste momento.';
    lista.append(vazio);
    return;
  }

  lista.append(...alertas.map(criarAlerta));
  if (window.lucide) window.lucide.createIcons();
};

const criarConsumo = (consumo) => {
  const item = document.createElement('section');
  item.className = 'building-item';

  const heading = document.createElement('div');
  heading.className = 'building-heading';

  const left = document.createElement('div');
  const icon = document.createElement('i');
  icon.setAttribute('data-lucide', 'zap');
  const nome = document.createElement('h3');
  nome.textContent = consumo.nome || '';
  left.append(icon, nome);

  const valor = document.createElement('strong');
  valor.textContent = consumo.valor || '';

  heading.append(left, valor);

  const track = document.createElement('span');
  track.className = 'progress-track';
  const bar = document.createElement('span');
  bar.className = 'progress-value';
  const percentagem = Math.max(0, Math.min(100, Number(consumo.percentagem) || 0));
  bar.style.width = `${percentagem}%`;
  track.append(bar);

  item.append(heading, track);
  return item;
};

const atualizarConsumos = (consumos = []) => {
  const lista = document.querySelector('[data-buildings-list]');
  if (!lista) return;

  lista.textContent = '';
  lista.append(...consumos.map(criarConsumo));
  if (window.lucide) window.lucide.createIcons();
};

const atualizarNotificacoes = (resumo = {}) => {
  const ponto = document.querySelector('[data-notification-dot]');
  if (ponto) ponto.hidden = Number(resumo.pendentes || 0) === 0;
};

const atualizarDashboard = async () => {
  try {
    const resposta = await fetch('dashboard-ajax.php', { cache: 'no-store' });
    if (!resposta.ok) throw new Error('Resposta inválida da Dashboard.');
    const dados = await resposta.json();
    atualizarResumo(dados.resumo);
    atualizarAlertas(dados.alertas);
    atualizarConsumos(dados.consumos);
    atualizarNotificacoes(dados.alertas_resumo);
  } catch (erro) {
    console.warn('Não foi possível atualizar a Dashboard.', erro);
  }
};

if (window.lucide) window.lucide.createIcons();
atualizarDashboard();
window.setInterval(atualizarDashboard, DASHBOARD_REFRESH_MS);
