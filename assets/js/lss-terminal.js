(function () {
  "use strict";

  const state = {
    history: [],
    historyIndex: -1,
  };

  const screen = document.getElementById("termScreen");
  const input = document.getElementById("termInput");
  const btnExec = document.getElementById("btnExec");

  function boot() {
    printLine("SISTEMA INICIADO", "t-system");
    printLine("Terminal LSS v1.0.0 — PCU Universidade Portucalense", "t-data");
    printLine("Digite AJUDA para ver os comandos disponíveis", "t-info");
    printBlank();
  }

  function printLine(text, cls) {
    const span = document.createElement("span");
    span.className = "terminal-line" + (cls ? " " + cls : "");
    span.textContent = text;
    screen.appendChild(span);
    scrollToBottom();
  }

  function printHTML(html, cls) {
    const span = document.createElement("span");
    span.className = "terminal-line" + (cls ? " " + cls : "");
    span.innerHTML = html;
    screen.appendChild(span);
    scrollToBottom();
  }

  function printBlank() {
    const br = document.createElement("span");
    br.className = "terminal-line";
    br.innerHTML = "&nbsp;";
    screen.appendChild(br);
    scrollToBottom();
  }

  function printPrompt(cmd) {
    printHTML(
      `<span class="t-cmd">$ </span><span class="t-data">${escHtml(cmd)}</span>`,
      ""
    );
  }

  function escHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function scrollToBottom() {
    screen.scrollTop = screen.scrollHeight;
  }

  function renderLinhas(linhas) {
    linhas.forEach((linha) => {
      if (linha.texto === "__LIMPAR__") {
        screen.innerHTML = "";
        boot();
        return;
      }
      printLine(linha.texto, linha.classe || "");
    });
  }

  async function executarComandoServidor(cmd) {
    const formData = new FormData();
    formData.append("comando", cmd);
    appendCsrf(formData);

    const resposta = await fetch("lss-comandos.php", {
      method: "POST",
      body: formData,
    });

    const resultado = await resposta.json();
    if (!resultado.sucesso && resultado.linhas) {
      renderLinhas(resultado.linhas);
      return;
    }

    if (resultado.linhas) {
      renderLinhas(resultado.linhas);
    }
  }

  function cmdAjudaLocal() {
    printLine("═══════════════════════════════════════════", "t-info");
    printLine("  Comandos disponíveis — LSS Terminal PCU  ", "t-system");
    printLine("═══════════════════════════════════════════", "t-info");
    printBlank();

    const groups = [
      {
        title: "Comandos Básicos",
        cmds: [
          ["STATUS", "Estado geral do sistema"],
          ["AJUDA", "Lista de comandos disponíveis"],
          ["LIMPAR", "Limpar o terminal"],
        ],
      },
      {
        title: "Gestão de Reservas",
        cmds: [
          ["RESERVA <sala> <periodo>", "Criar reserva de sala"],
          ["CANCELAR_RESERVA <id>", "Cancelar reserva existente"],
          ["LISTAR_RESERVAS [edificio]", "Listar reservas ativas"],
        ],
      },
      {
        title: "Controlo IoT",
        cmds: [
          ["SENSOR <acao> <localizacao>", "Gerir sensores IoT"],
          ["ALERTA <tipo> [>|<] <valor>", "Consultar alertas"],
          ["ENERGIA Consumo <local>", "Consultar consumo energético"],
        ],
      },
      {
        title: "Mobilidade",
        cmds: [
          ["MOBILIDADE ESTACIONAMENTO <zona>", "Ver lugares de estacionamento"],
          ["MOBILIDADE BICICLETAS", "Ver disponibilidade de bicicletas"],
          ["MOBILIDADE TROTINETES", "Ver disponibilidade de trotinetes"],
        ],
      },
    ];

    groups.forEach((g) => {
      printLine("  " + g.title, "t-key");
      g.cmds.forEach(([c, d]) => {
        printHTML(
          `  <span class="t-cmd">${escHtml(c)}</span>  <span class="t-info">${escHtml(d)}</span>`,
          ""
        );
      });
      printBlank();
    });
  }

  async function execute(raw) {
    const cmd = raw.trim();
    if (!cmd) return;

    state.history.unshift(cmd);
    state.historyIndex = -1;

    printBlank();
    printPrompt(cmd);

    const verb = cmd.toUpperCase().split(/\s+/)[0];

    if (verb === "AJUDA") {
      cmdAjudaLocal();
      printBlank();
      return;
    }

    if (verb === "LIMPAR") {
      screen.innerHTML = "";
      boot();
      return;
    }

    try {
      await executarComandoServidor(cmd);
    } catch (erro) {
      printLine("Erro de comunicação com o servidor.", "t-error");
    }

    printBlank();
  }

  btnExec.addEventListener("click", () => {
    const val = input.value.trim();
    if (!val) return;
    execute(val);
    input.value = "";
  });

  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      btnExec.click();
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      if (state.historyIndex < state.history.length - 1) {
        state.historyIndex++;
        input.value = state.history[state.historyIndex];
      }
    } else if (e.key === "ArrowDown") {
      e.preventDefault();
      if (state.historyIndex > 0) {
        state.historyIndex--;
        input.value = state.history[state.historyIndex];
      } else {
        state.historyIndex = -1;
        input.value = "";
      }
    }
  });

  document.querySelectorAll(".qcmd").forEach((btn) => {
    btn.addEventListener("click", () => {
      const cmd = btn.dataset.cmd;
      if (cmd) {
        input.value = cmd;
        input.focus();
      }
    });
  });

  document.querySelectorAll(".guide-tab").forEach((tab) => {
    tab.addEventListener("click", () => {
      document.querySelectorAll(".guide-tab").forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      const target = tab.dataset.tab;
      document.querySelectorAll(".guide-pane").forEach((p) =>
        p.classList.toggle("hidden", p.dataset.pane !== target)
      );
    });
  });

  boot();
  input.focus();
})();
