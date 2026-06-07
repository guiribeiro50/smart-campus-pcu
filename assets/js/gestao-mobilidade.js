let tabAtiva = "estacionamento";

const titulosNovo = {
  estacionamento: {
    titulo: "Novo Estacionamento",
    subtitulo: "Registar um lugar de estacionamento",
  },
  bicicleta: {
    titulo: "Nova Bicicleta",
    subtitulo: "Adicionar bicicletas ao sistema de partilha",
  },
  trotinete: {
    titulo: "Nova Trotinete",
    subtitulo: "Adicionar trotinetes ao sistema de partilha",
  },
};

const titulosEditar = {
  estacionamento: "Editar Estacionamento",
  bicicleta: "Editar Bicicleta",
  trotinete: "Editar Trotinete",
};

document.addEventListener("DOMContentLoaded", () => {
  lucide.createIcons();
  preencherLocalidades(
    "novo-localidade-opcoes",
    "novo_localidade_id",
    "novo-localidade-text",
    "wrapper-localidade-novo",
  );
  preencherLocalidades(
    "editar-localidade-opcoes",
    "editar_localidade_id",
    "editar-localidade-text",
    "wrapper-localidade-editar",
  );

  const alerta = document.getElementById("alerta-pagina");
  if (alerta && window.location.search) {
    const url = new URL(window.location.href);
    url.searchParams.delete("sucesso");
    url.searchParams.delete("erro");
    window.history.replaceState({}, "", url.pathname + (url.search || ""));
    setTimeout(() => {
      alerta.style.transition = "opacity 0.3s ease";
      alerta.style.opacity = "0";
      setTimeout(() => alerta.remove(), 300);
    }, 5000);
  }

  document
    .getElementById("formNovoRecurso")
    .addEventListener("submit", submeterNovo);
  document
    .getElementById("formEditarRecurso")
    .addEventListener("submit", submeterEditar);
  document
    .getElementById("btn-confirmar-eliminar")
    .addEventListener("click", confirmarEliminar);
  document.addEventListener("click", fecharTodosDropdowns);
});

function mudarTab(tipo, botao) {
  tabAtiva = tipo;
  document
    .querySelectorAll(".tab-btn")
    .forEach((btn) => btn.classList.remove("active"));
  botao.classList.add("active");
  document
    .querySelectorAll(".tab-painel")
    .forEach((painel) => painel.classList.remove("active"));
  document.getElementById(`painel-${tipo}`).classList.add("active");
}

function alternarDropdownMobilidade(event, wrapperId) {
  event.stopPropagation();
  fecharTodosDropdowns();
  document.getElementById(wrapperId).classList.toggle("open");
}

function fecharTodosDropdowns() {
  document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
    wrapper.classList.remove("open");
  });
}

function selecionarEstado(elemento, inputId, textId, wrapperId) {
  const valor = elemento.getAttribute("data-value");
  const texto = elemento.querySelector("span").innerText;
  const wrapper = document.getElementById(wrapperId);
  document.getElementById(inputId).value = valor;
  document.getElementById(textId).innerText = texto;
  wrapper
    .querySelectorAll(".custom-option")
    .forEach((opt) => opt.classList.remove("selected"));
  elemento.classList.add("selected");
  wrapper.classList.remove("open");
}

function selecionarFiltroZona(elemento, inputId, textId, wrapperId, tipo) {
  const valor = elemento.getAttribute("data-value");
  const texto = elemento.querySelector("span").innerText;
  const wrapper = document.getElementById(wrapperId);
  document.getElementById(inputId).value = valor;
  document.getElementById(textId).innerText = texto;
  wrapper
    .querySelectorAll(".custom-option")
    .forEach((opt) => opt.classList.remove("selected"));
  elemento.classList.add("selected");
  wrapper.classList.remove("open");
  filtrarTabela(tipo);
}

function preencherLocalidades(
  containerId,
  inputId,
  textId,
  wrapperId,
  valorSelecionado = "",
) {
  const container = document.getElementById(containerId);
  if (!container || !LOCALIDADES) return;
  container.innerHTML = "";
  LOCALIDADES.forEach((loc) => {
    const opt = document.createElement("div");
    opt.className =
      "custom-option" +
      (String(loc.id) === String(valorSelecionado) ? " selected" : "");
    opt.setAttribute("data-value", loc.id);
    opt.onclick = function () {
      document.getElementById(inputId).value = loc.id;
      document.getElementById(textId).innerText = loc.nome;
      document.getElementById(wrapperId).classList.remove("open");
      container
        .querySelectorAll(".custom-option")
        .forEach((o) => o.classList.remove("selected"));
      opt.classList.add("selected");
    };
    opt.innerHTML = `<span>${loc.nome}</span><i data-lucide="check" class="check-icon"></i>`;
    container.appendChild(opt);
  });
  if (valorSelecionado) {
    const loc = LOCALIDADES.find(
      (l) => String(l.id) === String(valorSelecionado),
    );
    if (loc) {
      document.getElementById(inputId).value = loc.id;
      document.getElementById(textId).innerText = loc.nome;
    }
  } else {
    document.getElementById(inputId).value = "";
    document.getElementById(textId).innerText = "Selecionar localidade";
  }
  lucide.createIcons();
}

function filtrarTabela(tipo) {
  const termo = (document.getElementById(`search-${tipo}`).value || "")
    .toLowerCase()
    .trim();
  const zona = document.getElementById(`filter-${tipo}`).value;
  document.querySelectorAll(`#tbody-${tipo} tr`).forEach((row) => {
    const codigo = row.getAttribute("data-codigo") || "";
    const localidade = row.getAttribute("data-localidade") || "";
    const rowZona = row.getAttribute("data-zona") || "";
    const matchTexto =
      !termo || codigo.includes(termo) || localidade.includes(termo);
    const matchZona = !zona || rowZona === zona;
    row.style.display = matchTexto && matchZona ? "" : "none";
  });
}

function atualizarFormularioNovo(tipo) {
  document.getElementById("novo_tipo").value = tipo;
  document.getElementById("modal-novo-titulo").textContent =
    titulosNovo[tipo].titulo;
  document.getElementById("modal-novo-subtitulo").textContent =
    titulosNovo[tipo].subtitulo;

  const grupoCodigo = document.getElementById("grupo-codigo-novo");
  const grupoQuantidade = document.getElementById("grupo-quantidade-novo");
  const grupoEstado = document.getElementById("grupo-estado-novo");
  const inputCodigo = document.getElementById("novo_codigo");

  if (tipo === "estacionamento") {
    grupoCodigo.style.display = "flex";
    grupoQuantidade.style.display = "none";
    grupoEstado.style.display = "none";
    inputCodigo.placeholder = "Ex: P1-04";
    inputCodigo.required = true;
  } else {
    grupoCodigo.style.display = "none";
    grupoQuantidade.style.display = "flex";
    grupoEstado.style.display = "flex";
    inputCodigo.required = false;
    inputCodigo.value = "";
    document.getElementById("novo_quantidade").value = "1";
    document.getElementById("novo_estado").value = "disponivel";
    document.getElementById("novo-estado-text").innerText = "Disponível";
    resetOpcoesEstado("wrapper-estado-novo", "disponivel");
  }

  preencherLocalidades(
    "novo-localidade-opcoes",
    "novo_localidade_id",
    "novo-localidade-text",
    "wrapper-localidade-novo",
  );
}

function resetOpcoesEstado(wrapperId, valor) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.querySelectorAll(".custom-option").forEach((opt) => {
    opt.classList.toggle("selected", opt.getAttribute("data-value") === valor);
  });
}

function abrirModalNovo() {
  document.getElementById("formNovoRecurso").reset();
  document.getElementById("btn-criar-recurso").disabled = false;
  atualizarFormularioNovo(tabAtiva);
  document.getElementById("modalNovoRecurso").classList.add("active");
  lucide.createIcons();
}

function fecharModalNovoBtn() {
  document.getElementById("modalNovoRecurso").classList.remove("active");
  fecharTodosDropdowns();
}

function fecharModalNovo(event) {
  if (event.target.id === "modalNovoRecurso") {
    fecharModalNovoBtn();
  }
}

async function submeterNovo(e) {
  e.preventDefault();
  const btn = document.getElementById("btn-criar-recurso");
  const tipo = document.getElementById("novo_tipo").value;
  const localidadeId = document.getElementById("novo_localidade_id").value;

  if (!localidadeId) {
    alert("Selecione uma localidade.");
    return;
  }

  btn.disabled = true;
  const body = new URLSearchParams({
    acao: "criar",
    tipo: tipo,
    localidade_id: localidadeId,
  });

  if (tipo === "estacionamento") {
    const codigo = document.getElementById("novo_codigo").value.trim();
    if (!codigo) {
      alert("Indique o código do estacionamento.");
      btn.disabled = false;
      return;
    }
    body.append("codigo", codigo);
  } else {
    body.append(
      "quantidade",
      document.getElementById("novo_quantidade").value || "1",
    );
    body.append("estado", document.getElementById("novo_estado").value);
  }

  appendCsrf(body);

  try {
    const res = await fetch("gestao-mobilidade-ajax.php", {
      method: "POST",
      body,
    });
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao criar recurso.");
      btn.disabled = false;
      return;
    }
    window.location.href = "gestao-mobilidade.php?sucesso=criar";
  } catch (err) {
    alert("Erro de ligação ao servidor.");
    btn.disabled = false;
  }
}

async function abrirEditar(tipo, id) {
  try {
    const res = await fetch(
      `gestao-mobilidade-ajax.php?acao=obter&tipo=${tipo}&id=${id}`,
    );
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao carregar recurso.");
      return;
    }
    const r = json.recurso;
    document.getElementById("editar_id").value = r.id;
    document.getElementById("editar_tipo").value = tipo;
    document.getElementById("editar_codigo").value = r.codigo;
    document.getElementById("modal-editar-titulo").textContent =
      titulosEditar[tipo];

    const grupoEstado = document.getElementById("grupo-estado-editar");
    if (tipo === "estacionamento") {
      grupoEstado.style.display = "none";
    } else {
      grupoEstado.style.display = "flex";
      document.getElementById("editar_estado").value = r.estado;
      document.getElementById("editar-estado-text").innerText =
        r.estado_label || r.estado;
      resetOpcoesEstado("wrapper-estado-editar", r.estado);
    }

    preencherLocalidades(
      "editar-localidade-opcoes",
      "editar_localidade_id",
      "editar-localidade-text",
      "wrapper-localidade-editar",
      r.localidade_id,
    );
    document
      .getElementById("formEditarRecurso")
      .querySelector(".btn-modal-submit").disabled = false;
    document.getElementById("modalEditarRecurso").classList.add("active");
    lucide.createIcons();
  } catch (e) {
    alert("Erro de ligação ao servidor.");
  }
}

function fecharModalEditarBtn() {
  document.getElementById("modalEditarRecurso").classList.remove("active");
  fecharTodosDropdowns();
}

function fecharModalEditar(event) {
  if (event.target.id === "modalEditarRecurso") {
    fecharModalEditarBtn();
  }
}

async function submeterEditar(e) {
  e.preventDefault();
  const btn = e.target.querySelector(".btn-modal-submit");
  btn.disabled = true;
  const body = new URLSearchParams({
    acao: "editar",
    tipo: document.getElementById("editar_tipo").value,
    id: document.getElementById("editar_id").value,
    codigo: document.getElementById("editar_codigo").value,
    localidade_id: document.getElementById("editar_localidade_id").value,
  });
  if (document.getElementById("editar_tipo").value !== "estacionamento") {
    body.append("estado", document.getElementById("editar_estado").value);
  }
  appendCsrf(body);
  try {
    const res = await fetch("gestao-mobilidade-ajax.php", {
      method: "POST",
      body,
    });
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao atualizar.");
      btn.disabled = false;
      return;
    }
    window.location.href = "gestao-mobilidade.php?sucesso=editar";
  } catch (err) {
    alert("Erro de ligação ao servidor.");
    btn.disabled = false;
  }
}

function eliminarRecurso(tipo, id, codigo) {
  document.getElementById("eliminar_id").value = id;
  document.getElementById("eliminar_tipo").value = tipo;
  const labels = {
    estacionamento: "estacionamento",
    bicicleta: "bicicleta",
    trotinete: "trotinete",
  };
  document.getElementById("eliminar-mensagem").textContent =
    `Tem a certeza que deseja eliminar ${labels[tipo]} "${codigo}"?`;
  document.getElementById("modalEliminarRecurso").classList.add("active");
  lucide.createIcons();
}

function fecharModalEliminarBtn() {
  document.getElementById("modalEliminarRecurso").classList.remove("active");
  document.getElementById("eliminar_id").value = "";
  document.getElementById("eliminar_tipo").value = "";
}

function fecharModalEliminar(event) {
  if (event.target.id === "modalEliminarRecurso") {
    fecharModalEliminarBtn();
  }
}

async function confirmarEliminar() {
  const id = document.getElementById("eliminar_id").value;
  const tipo = document.getElementById("eliminar_tipo").value;
  if (!id || !tipo) return;

  const btn = document.getElementById("btn-confirmar-eliminar");
  btn.disabled = true;
  btn.textContent = "A eliminar...";

  const body = new URLSearchParams({ acao: "eliminar", tipo: tipo, id: id });
  appendCsrf(body);
  try {
    const res = await fetch("gestao-mobilidade-ajax.php", {
      method: "POST",
      body,
    });
    const json = await res.json();
    if (!json.sucesso) {
      alert(json.mensagem || "Erro ao eliminar.");
      btn.disabled = false;
      btn.textContent = "Eliminar Recurso";
      return;
    }
    window.location.href = "gestao-mobilidade.php?sucesso=eliminar";
  } catch (e) {
    alert("Erro de ligação ao servidor.");
    btn.disabled = false;
    btn.textContent = "Eliminar Recurso";
  }
}
