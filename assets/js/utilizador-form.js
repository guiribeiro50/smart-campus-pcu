function alternarModal(status) {
  const modal = document.getElementById("modalNovoUtilizador");
  if (status) {
    modal.classList.add("active");
    setTimeout(() => document.getElementById("nome_completo").focus(), 50);
  } else {
    modal.classList.remove("active");
    if (typeof fecharTodosDropdowns === "function") fecharTodosDropdowns();
    document.getElementById("formNovoUtilizador").reset();
    document.getElementById("perfil_acesso").value = "Estudante";
    document.getElementById("custom-select-text").innerText = "Estudante";
    const alerta = document.getElementById("alerta-admin-novo");
    if (alerta) alerta.style.display = "none";
    if (typeof resetOpcoesPerfil === "function") resetOpcoesPerfil("wrapper-perfil-novo", "Estudante");
  }
}

function fecharModalNovo(event) {
  if (event.target.id === "modalNovoUtilizador") {
    alternarModal(false);
  }
}

function fecharModalSet(event) {
  fecharModalNovo(event);
}

function alternarDropdown(event) {
  alternarDropdownPerfil(event, "wrapper-perfil-novo");
}

function selecionarOpcao(elemento) {
  selecionarPerfil(elemento, "perfil_acesso", "custom-select-text", "wrapper-perfil-novo", "alerta-admin-novo");
}
