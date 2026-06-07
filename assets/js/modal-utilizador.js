function alternarModal(status) {
  const modal = document.getElementById("modalNovoUtilizador");
  if (status) {
    modal.classList.add("active");
    setTimeout(() => document.getElementById("nome_completo").focus(), 50);
  } else {
    modal.classList.remove("active");
    fecharTodosDropdowns();
    document.getElementById("formNovoUtilizador").reset();
    document.getElementById("perfil_acesso").value = "Estudante";
    document.getElementById("custom-select-text").innerText = "Estudante";
    const alerta = document.getElementById("alerta-admin-novo");
    if (alerta) alerta.style.display = "none";
    resetOpcoesPerfil("wrapper-perfil-novo", "Estudante");
  }
}

function fecharModalNovo(event) {
  if (event.target.id === "modalNovoUtilizador") {
    alternarModal(false);
  }
}

function alternarDropdownPerfil(event, wrapperId) {
  event.stopPropagation();
  fecharTodosDropdowns();
  document.getElementById(wrapperId).classList.toggle("open");
}

function selecionarPerfil(elemento, inputId, textId, wrapperId, alertaId) {
  const valor = elemento.getAttribute("data-value");
  const wrapper = document.getElementById(wrapperId);
  document.getElementById(inputId).value = valor;
  document.getElementById(textId).innerText = valor;
  wrapper.querySelectorAll(".custom-option").forEach((opt) => opt.classList.remove("selected"));
  elemento.classList.add("selected");
  wrapper.classList.remove("open");
  const alerta = document.getElementById(alertaId);
  if (alerta) {
    alerta.style.display = valor === "Administrador" ? "flex" : "none";
  }
}

function resetOpcoesPerfil(wrapperId, valor) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.querySelectorAll(".custom-option").forEach((opt) => {
    opt.classList.toggle("selected", opt.getAttribute("data-value") === valor);
  });
}

function fecharTodosDropdowns() {
  document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
    wrapper.classList.remove("open");
  });
}

document.addEventListener("click", fecharTodosDropdowns);

function alternarDropdownFiltro(event) {
  event.stopPropagation();
  fecharTodosDropdowns();
  const wrapper = event.currentTarget.closest(".custom-select-wrapper");
  wrapper.classList.toggle("open");
}

function selecionarOpcaoFiltro(elemento) {
  const valor = elemento.getAttribute("data-value");
  const texto = elemento.querySelector("span").innerText;
  const wrapper = elemento.closest(".custom-select-wrapper");
  document.getElementById("filter-perfil").value = valor;
  document.getElementById("custom-filter-text").innerText = texto;
  wrapper.querySelectorAll(".custom-option").forEach((opt) => opt.classList.remove("selected"));
  elemento.classList.add("selected");
  wrapper.classList.remove("open");
  filtrarTabela();
}

function filtrarTabela() {
  const termo = (document.getElementById("search").value || "").toLowerCase().trim();
  const perfil = document.getElementById("filter-perfil").value;
  document.querySelectorAll("#users-tbody tr").forEach((row) => {
    const nome = row.getAttribute("data-nome") || "";
    const email = row.getAttribute("data-email") || "";
    const rowPerfil = row.getAttribute("data-perfil") || "";
    const matchTexto = !termo || nome.includes(termo) || email.includes(termo);
    const matchPerfil = !perfil || rowPerfil === perfil;
    row.style.display = matchTexto && matchPerfil ? "" : "none";
  });
}

async function abrirEditar(id) {
  try {
    const res = await fetch(`utilizador-ajax.php?acao=obter&id=${id}`);
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao carregar utilizador.");
      return;
    }
    const u = json.utilizador;
    document.getElementById("editar_id").value = u.id;
    document.getElementById("editar_nome").value = u.nome;
    document.getElementById("editar_email").value = u.email;
    document.getElementById("editar_login").value = u.login;
    document.getElementById("editar_perfil").value = u.perfil_ui;
    document.getElementById("editar-perfil-text").innerText = u.perfil_ui;
    resetOpcoesPerfil("wrapper-perfil-editar", u.perfil_ui);
    const alerta = document.getElementById("alerta-admin-editar");
    if (alerta) {
      alerta.style.display = u.perfil_ui === "Administrador" ? "flex" : "none";
    }
    document.getElementById("modalEditarUtilizador").classList.add("active");
    setTimeout(() => document.getElementById("editar_nome").focus(), 50);
    lucide.createIcons();
  } catch (e) {
    alert("Erro de ligação ao servidor.");
  }
}

function fecharModalEditarBtn() {
  document.getElementById("modalEditarUtilizador").classList.remove("active");
  fecharTodosDropdowns();
}

function fecharModalEditar(event) {
  if (event.target.id === "modalEditarUtilizador") {
    fecharModalEditarBtn();
  }
}

const formEditarUtilizador = document.getElementById("formEditarUtilizador");
if (formEditarUtilizador) {
  formEditarUtilizador.addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = e.target.querySelector(".btn-modal-submit");
  btn.disabled = true;
  const body = new URLSearchParams({
    acao: "editar",
    id: document.getElementById("editar_id").value,
    nome: document.getElementById("editar_nome").value,
    email: document.getElementById("editar_email").value,
    login: document.getElementById("editar_login").value,
    perfil: document.getElementById("editar_perfil").value,
  });
  appendCsrf(body);
  try {
    const res = await fetch("utilizador-ajax.php", { method: "POST", body });
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao atualizar.");
      btn.disabled = false;
      return;
    }
    window.location.reload();
  } catch (err) {
    alert("Erro de ligação ao servidor.");
    btn.disabled = false;
  }
  });
}

async function abrirPermissoes(id) {
  try {
    const res = await fetch(`utilizador-ajax.php?acao=permissoes&id=${id}`);
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao carregar permissões.");
      return;
    }
    const u = json.utilizador;
    document.getElementById("permissoes_utilizador_id").value = u.id;
    document.getElementById("permissoes-titulo").textContent = `Permissões — ${u.nome}`;
    document.getElementById("permissoes-avatar").textContent = u.iniciais;
    document.getElementById("permissoes-nome").textContent = u.nome;
    document.getElementById("permissoes-email").textContent = u.email;
    const badge = document.getElementById("permissoes-badge");
    badge.textContent = u.perfil_ui;
    badge.className = `badge ${u.badge_class}`;
    document.getElementById("permissoes-info-perfil").textContent =
      `Perfil base: ${u.perfil_ui} — permissões padrão já incluídas`;
    renderPermissoesAtivas(json.permissoes_ativas);
    renderPermissoesDisponiveis(json.permissoes_disponiveis);
    renderHistorico(json.permissoes_ativas);
    document.getElementById("modalPermissoes").classList.add("active");
    lucide.createIcons();
  } catch (e) {
    alert("Erro de ligação ao servidor.");
  }
}

function renderPermissoesAtivas(ativas) {
  const container = document.getElementById("permissoes-ativas");
  const vazio = document.getElementById("permissoes-vazio");
  container.innerHTML = "";
  if (!ativas.length) {
    vazio.style.display = "block";
    return;
  }
  vazio.style.display = "none";
  ativas.forEach((p) => {
    const tag = document.createElement("span");
    tag.className = "perm-tag";
    tag.append(document.createTextNode((p.label || "") + " "));
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = "\u00d7";
    btn.addEventListener("click", () => removerPermissao(p.permissao_id));
    tag.appendChild(btn);
    container.appendChild(tag);
  });
}

function renderPermissoesDisponiveis(disponiveis) {
  const container = document.getElementById("permissoes-opcoes");
  document.getElementById("permissao_add_id").value = "";
  document.getElementById("permissao-add-text").innerText = "Selecionar permissão";
  container.innerHTML = "";
  disponiveis.forEach((p) => {
    const opt = document.createElement("div");
    opt.className = "custom-option";
    opt.setAttribute("data-value", p.id);
    opt.onclick = function () {
      document.getElementById("permissao_add_id").value = p.id;
      document.getElementById("permissao-add-text").innerText = p.label;
      document.getElementById("wrapper-permissao-add").classList.remove("open");
    };
    const label = document.createElement("span");
    label.textContent = p.label || "";
    const check = document.createElement("i");
    check.setAttribute("data-lucide", "check");
    check.className = "check-icon";
    opt.append(label, check);
    container.appendChild(opt);
  });
  lucide.createIcons();
}

function renderHistorico(ativas) {
  const container = document.getElementById("permissoes-historico");
  container.innerHTML = "";
  if (!ativas.length) {
    container.innerHTML = '<p class="historico-vazio">Sem alterações registadas.</p>';
    return;
  }
  ativas.forEach((p) => {
    const item = document.createElement("div");
    item.className = "historico-item";
    const data = p.atribuido_em ? new Date(p.atribuido_em.replace(" ", "T")).toLocaleString("pt-PT") : "";
    item.innerHTML = `<span>${p.label}</span><span>${data}</span>`;
    container.appendChild(item);
  });
}

async function adicionarPermissao() {
  const utilizadorId = document.getElementById("permissoes_utilizador_id").value;
  const permissaoId = document.getElementById("permissao_add_id").value;
  if (!permissaoId) return;
  const body = new URLSearchParams({
    acao: "adicionar_permissao",
    utilizador_id: utilizadorId,
    permissao_id: permissaoId,
  });
  appendCsrf(body);
  try {
    const res = await fetch("utilizador-ajax.php", { method: "POST", body });
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao adicionar permissão.");
      return;
    }
    abrirPermissoes(utilizadorId);
  } catch (e) {
    alert("Erro de ligação ao servidor.");
  }
}

async function removerPermissao(permissaoId) {
  const utilizadorId = document.getElementById("permissoes_utilizador_id").value;
  const body = new URLSearchParams({
    acao: "remover_permissao",
    utilizador_id: utilizadorId,
    permissao_id: permissaoId,
  });
  appendCsrf(body);
  try {
    const res = await fetch("utilizador-ajax.php", { method: "POST", body });
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao remover permissão.");
      return;
    }
    abrirPermissoes(utilizadorId);
  } catch (e) {
    alert("Erro de ligação ao servidor.");
  }
}

function fecharModalPermissoesBtn() {
  document.getElementById("modalPermissoes").classList.remove("active");
  fecharTodosDropdowns();
}

function fecharModalPermissoes(event) {
  if (event.target.id === "modalPermissoes") {
    fecharModalPermissoesBtn();
  }
}

function toggleHistorico() {
  const lista = document.getElementById("permissoes-historico");
  const chevron = document.getElementById("historico-chevron");
  const aberto = lista.style.display !== "none";
  lista.style.display = aberto ? "none" : "block";
  if (chevron) chevron.style.transform = aberto ? "" : "rotate(180deg)";
}

function eliminarUtilizador(id, nome) {
  document.getElementById("eliminar_utilizador_id").value = id;
  document.getElementById("eliminar-mensagem").textContent =
    `Tem a certeza que deseja eliminar o utilizador "${nome}"?`;
  document.getElementById("modalEliminarUtilizador").classList.add("active");
  lucide.createIcons();
}

function fecharModalEliminarBtn() {
  document.getElementById("modalEliminarUtilizador").classList.remove("active");
  document.getElementById("eliminar_utilizador_id").value = "";
}

function fecharModalEliminar(event) {
  if (event.target.id === "modalEliminarUtilizador") {
    fecharModalEliminarBtn();
  }
}

const btnConfirmarEliminar = document.getElementById("btn-confirmar-eliminar");
if (btnConfirmarEliminar) {
  btnConfirmarEliminar.addEventListener("click", async () => {
    const id = document.getElementById("eliminar_utilizador_id").value;
    if (!id) return;
    btnConfirmarEliminar.disabled = true;
    btnConfirmarEliminar.textContent = "A eliminar...";
    const body = new URLSearchParams({ acao: "eliminar", id: id });
    appendCsrf(body);
    try {
      const res = await fetch("utilizador-ajax.php", { method: "POST", body });
      const json = await res.json();
      if (!json.sucesso) {
        alert(json.mensagem || "Erro ao eliminar.");
        btnConfirmarEliminar.disabled = false;
        btnConfirmarEliminar.textContent = "Eliminar Utilizador";
        return;
      }
      window.location.reload();
    } catch (e) {
      alert("Erro de ligação ao servidor.");
      btnConfirmarEliminar.disabled = false;
      btnConfirmarEliminar.textContent = "Eliminar Utilizador";
    }
  });
}
