document.addEventListener("DOMContentLoaded", () => {
  lucide.createIcons();
  atualizarCoresIconesGlobais();
  atualizarDados();
  setInterval(atualizarDados, 30000);
});

function atualizarCoresIconesGlobais() {
  document.querySelectorAll('[data-lucide="bike"]').forEach((el) => {
    if (el.tagName === "svg") {
      el.style.setProperty("stroke", "#2563eb", "important");
      el.style.setProperty("color", "#2563eb", "important");
    }
  });
  document.querySelectorAll('[data-lucide="navigation"]').forEach((el) => {
    if (el.tagName === "svg") {
      el.style.setProperty("stroke", "#7c3aed", "important");
      el.style.setProperty("color", "#7c3aed", "important");
    }
  });
}

async function atualizarDados() {
  try {
    const res = await fetch("mobilidade-dados.php");
    const dados = await res.json();

    const cards = document.querySelectorAll(".reserve-card");
    if (cards[0] && dados.lugares_por_zona) {
      const totalLugares = dados.lugares_por_zona.reduce(
        (s, z) => s + parseInt(z.total_lugares || 0),
        0,
      );
      cards[0].setAttribute(
        "onclick",
        `abrirModalEstacionamento(JSON.parse(decodeURIComponent('${encodeURIComponent(JSON.stringify(dados.lugares_por_zona))}')))`,
      );
      cards[0].querySelector(".reserve-sub").textContent =
        `${totalLugares} lugares registados`;
    }
    if (cards[1]) {
      cards[1].setAttribute(
        "onclick",
        `abrirModal('bicicleta', ${dados.total_bicicletas}, JSON.parse(decodeURIComponent('${encodeURIComponent(JSON.stringify(dados.bicicletas_por_zona))}')), ${dados.tem_reserva_ativa})`,
      );
      cards[1].querySelector(".reserve-sub").textContent =
        `${dados.total_bicicletas} bicicletas disponíveis`;
    }
    if (cards[2]) {
      cards[2].setAttribute(
        "onclick",
        `abrirModal('trotinete', ${dados.total_trotinetes}, JSON.parse(decodeURIComponent('${encodeURIComponent(JSON.stringify(dados.trotinetes_por_zona))}')), ${dados.tem_reserva_ativa})`,
      );
      cards[2].querySelector(".reserve-sub").textContent =
        `${dados.total_trotinetes} trotinetes disponíveis`;
    }

    atualizarBarras(dados);
    return dados;
  } catch (e) {
    console.warn("Erro ao atualizar dados:", e);
    return null;
  }
}

function atualizarBarras(dados) {
  const cards = document.querySelectorAll(".overview-card");

  if (cards[1] && dados.bicicletas_por_zona) {
    const items = cards[1].querySelectorAll(".parking-item");
    dados.bicicletas_por_zona.forEach((zona, i) => {
      if (!items[i]) return;
      const total = parseInt(zona.total) || 0;
      const em_uso = parseInt(zona.em_uso) || 0;
      const pct = total > 0 ? (em_uso / total) * 100 : 0;
      const cor =
        pct <= 40
          ? "green"
          : pct <= 60
            ? "yellow"
            : pct <= 80
              ? "orange"
              : "red";
      const fill = items[i].querySelector(".progress-fill");
      const label = items[i].querySelector(
        ".parking-item-header span:last-child",
      );
      if (fill) {
        fill.className = `progress-fill ${cor}`;
        fill.style.width = pct + "%";
      }
      if (label) label.textContent = `${zona.disponiveis}/${total}`;
    });
  }

  if (cards[2] && dados.trotinetes_por_zona) {
    const items = cards[2].querySelectorAll(".parking-item");
    dados.trotinetes_por_zona.forEach((zona, i) => {
      if (!items[i]) return;
      const total = parseInt(zona.total) || 0;
      const em_uso = parseInt(zona.em_uso) || 0;
      const pct = total > 0 ? (em_uso / total) * 100 : 0;
      const cor =
        pct <= 40
          ? "green"
          : pct <= 60
            ? "yellow"
            : pct <= 80
              ? "orange"
              : "red";
      const fill = items[i].querySelector(".progress-fill");
      const label = items[i].querySelector(
        ".parking-item-header span:last-child",
      );
      if (fill) {
        fill.className = `progress-fill ${cor}`;
        fill.style.width = pct + "%";
      }
      if (label) label.textContent = `${zona.disponiveis}/${total}`;
    });
  }

  const statCards = document.querySelectorAll(".stat-card-value");
  if (statCards[1]) statCards[1].textContent = dados.total_bicicletas;
  if (statCards[2]) statCards[2].textContent = dados.total_trotinetes;
}

async function abrirModal(tipo, disponiveis, zonas, temReservaAtiva) {
  const overlay = document.getElementById("modal");
  const box = overlay.querySelector(".modal");
  disponiveis = parseInt(disponiveis, 10) || 0;
  temReservaAtiva = temReservaAtiva === true || temReservaAtiva === "true";

  const cor = tipo === "bicicleta" ? "#2563eb" : "#7c3aed";
  const iconName = tipo === "bicicleta" ? "bike" : "navigation";
  const labelPlural = tipo === "bicicleta" ? "bicicletas" : "trotinetes";
  const labelSingular = tipo === "bicicleta" ? "Bicicleta" : "Trotinete";

  if (temReservaAtiva) {
    const response = await fetch("templates/reservas/reserva-ativa.html");
    box.innerHTML = await response.text();
    document.getElementById("modal-icon-bg-ativa").style.backgroundColor =
      `${cor}15`;
    document
      .getElementById("modal-icon-ativa")
      .setAttribute("data-lucide", iconName);
    document.getElementById("modal-icon-ativa").style.color = cor;
    document.getElementById("modal-title-ativa-text").textContent =
      "Reserva já ativa";
    overlay.classList.add("active");
    lucide.createIcons();
    atualizarCoresIconesGlobais();
    return;
  } else if (disponiveis <= 0) {
    const response = await fetch("templates/reservas/reserva-indisponivel.html");
    box.innerHTML = await response.text();
    document.getElementById("modal-icon-bg-unavail").style.backgroundColor =
      `${cor}15`;
    document
      .getElementById("modal-icon-unavail")
      .setAttribute("data-lucide", iconName);
    document.getElementById("modal-icon-unavail").style.color = cor;
    document.getElementById("modal-title-unavail-text").textContent =
      `Sem ${labelPlural} disponíveis`;
    document
      .getElementById("modal-icon-center-unavail")
      .setAttribute("data-lucide", iconName);
    document.getElementById("modal-icon-center-unavail").style.color = cor;
    document.getElementById("unavail-vehicle-label").textContent = labelPlural;
  } else {
    const response = await fetch("templates/reservas/reserva-form.html");
    box.innerHTML = await response.text();

    document.getElementById("modal-icon-bg").style.backgroundColor = `${cor}15`;
    document
      .getElementById("modal-icon-main")
      .setAttribute("data-lucide", iconName);
    document.getElementById("modal-icon-main").style.color = cor;
    document.getElementById("modal-title-text").textContent =
      `Reservar ${labelSingular}`;
    document.getElementById("modal-sub-text").textContent =
      `${disponiveis} ${labelPlural} disponíveis`;

    const zonaSelect = document.createElement("div");
    zonaSelect.className = "form-group";
    zonaSelect.innerHTML = `
      <label class="form-label" for="reserva-zona">Zona</label>
      <div class="input-wrap">
        <i data-lucide="map-pin" class="input-icon"></i>
        <select id="reserva-zona" class="form-input" required>
          <option value="">Selecione uma zona...</option>
          ${zonas
            .filter((z) => parseInt(z.disponiveis) > 0)
            .map(
              (z) =>
                `<option value="${z.localidade_id}">${z.localidade_nome} (${z.disponiveis} disponíveis)</option>`,
            )
            .join("")}
        </select>
      </div>
    `;
    document
      .querySelector(".modal-body")
      .insertBefore(
        zonaSelect,
        document.querySelector(".modal-body").firstChild,
      );

    document
      .getElementById("btn-submit-reserva")
      .addEventListener("click", () => confirmarReserva(tipo));
  }

  overlay.classList.add("active");
  lucide.createIcons();
  atualizarCoresIconesGlobais();
}

async function confirmarReserva(tipo) {
  const zonaEl = document.getElementById("reserva-zona");
  const dataEl = document.getElementById("reserva-data");
  const inicioEl = document.getElementById("reserva-inicio");
  const fimEl = document.getElementById("reserva-fim");

  let valido = true;
  [zonaEl, dataEl, inicioEl, fimEl].forEach((el) => {
    if (el && !el.value) {
      el.classList.add("input-error");
      valido = false;
      el.addEventListener("input", () => el.classList.remove("input-error"), {
        once: true,
      });
    }
  });
  if (!valido) return;

  if (inicioEl.value >= fimEl.value) {
    fimEl.classList.add("input-error");
    fimEl.addEventListener(
      "input",
      () => fimEl.classList.remove("input-error"),
      { once: true },
    );
    return;
  }

  const btn = document.getElementById("btn-submit-reserva");
  btn.disabled = true;
  btn.textContent = "A processar...";

  const body = new URLSearchParams({
    tipo: tipo,
    zona_id: zonaEl.value,
    data: dataEl.value,
    inicio: inicioEl.value,
    fim: fimEl.value,
  });
  appendCsrf(body);

  let json;
  try {
    const res = await fetch("reserva-mobilidade-ajax.php", {
      method: "POST",
      body,
    });
    const text = await res.text();
    try {
      json = JSON.parse(text);
    } catch {
      console.error("Resposta não é JSON:", text);
      mostrarErroModal("Erro de servidor. Verifica os logs PHP.");
      btn.disabled = false;
      btn.textContent = "Confirmar Reserva";
      return;
    }
  } catch (e) {
    mostrarErroModal("Erro de ligação ao servidor.");
    btn.disabled = false;
    btn.textContent = "Confirmar Reserva";
    return;
  }

  if (!json.sucesso) {
    if (json.mensagem === "reserva_ativa") {
      const overlay = document.getElementById("modal");
      const box = overlay.querySelector(".modal");
      const cor = tipo === "bicicleta" ? "#2563eb" : "#7c3aed";
      const iconName = tipo === "bicicleta" ? "bike" : "navigation";
      const response = await fetch("templates/reservas/reserva-ativa.html");
      box.innerHTML = await response.text();
      document.getElementById("modal-icon-bg-ativa").style.backgroundColor =
        `${cor}15`;
      document
        .getElementById("modal-icon-ativa")
        .setAttribute("data-lucide", iconName);
      document.getElementById("modal-icon-ativa").style.color = cor;
      lucide.createIcons();
      atualizarCoresIconesGlobais();
    } else {
      mostrarErroModal(json.mensagem || "Erro ao criar reserva.");
      btn.disabled = false;
      btn.textContent = "Confirmar Reserva";
    }
    return;
  }

  const inicioVal = inicioEl.value;
  const fimVal = fimEl.value;
  const dataVal = dataEl.value;

  const dados = await atualizarDados();

  const box = document.getElementById("modal").querySelector(".modal");
  const response = await fetch("templates/reservas/reserva-sucesso.html");
  box.innerHTML = await response.text();

  const labelSingular = tipo === "bicicleta" ? "Bicicleta" : "Trotinete";
  const iconName = tipo === "bicicleta" ? "bike" : "navigation";
  const cor = tipo === "bicicleta" ? "#2563eb" : "#7c3aed";

  const dataFormatada = new Date(dataVal + "T12:00:00").toLocaleDateString(
    "pt-PT",
    {
      day: "2-digit",
      month: "long",
      year: "numeric",
    },
  );

  document.getElementById("success-sub-text").textContent =
    `${labelSingular} reservada com sucesso`;
  document.getElementById("success-data").textContent = dataFormatada;
  document.getElementById("success-horas").textContent =
    `${inicioVal} – ${fimVal}`;
  const sIcon = document.getElementById("success-vehicle-icon");
  sIcon.setAttribute("data-lucide", iconName);
  sIcon.style.color = cor;
  document.getElementById("success-vehicle-text").textContent =
    `${labelSingular} – ${json.codigo}`;

  lucide.createIcons();
  atualizarCoresIconesGlobais();
}

function mostrarErroModal(msg) {
  let div = document.getElementById("modal-erro");
  if (!div) {
    div = document.createElement("div");
    div.id = "modal-erro";
    div.style.cssText =
      "margin: 0 24px 16px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; font-size: 13px; color: #dc2626;";
    const footer = document.querySelector(".modal-footer");
    if (footer) footer.parentNode.insertBefore(div, footer);
  }
  div.textContent = msg;
  setTimeout(() => div.remove(), 5000);
}

async function abrirModalEstacionamento(zonas) {
  const overlay = document.getElementById("modal");
  const box = overlay.querySelector(".modal");

  const response = await fetch("templates/reservas/reserva-form.html");
  box.innerHTML = await response.text();

  document.getElementById("modal-icon-bg").style.backgroundColor = "#0f274415";
  document.getElementById("modal-icon-main").setAttribute("data-lucide", "car");
  document.getElementById("modal-icon-main").style.color = "#0f2744";
  document.getElementById("modal-title-text").textContent =
    "Encontrar Estacionamento";
  document.getElementById("modal-sub-text").textContent =
    "Selecione uma zona para ver os lugares";

  document.querySelector(".modal-body").innerHTML = `
    <div class="form-group">
      <label class="form-label" for="est-zona">Zona</label>
      <div class="input-wrap">
        <i data-lucide="map-pin" class="input-icon"></i>
        <select id="est-zona" class="form-input" required>
          <option value="">Selecione uma zona...</option>
          ${zonas
            .map(
              (z) =>
                `<option value="${z.id}" data-lugares='${JSON.stringify(z.lugares)}' data-nome="${z.nome}">${z.nome}</option>`,
            )
            .join("")}
        </select>
      </div>
    </div>
  `;

  document.querySelector(".modal-footer").innerHTML = `
    <button type="button" class="btn-cancel" onclick="fecharModalBtn()">Cancelar</button>
    <button type="button" class="btn-confirm" id="btn-ver-lugares">Ver Lugares</button>
  `;
  document
    .getElementById("btn-ver-lugares")
    .addEventListener("click", verEstacionamento);

  overlay.classList.add("active");
  lucide.createIcons();
}

async function verEstacionamento() {
  const zonaEl = document.getElementById("est-zona");
  if (!zonaEl || !zonaEl.value) {
    zonaEl.classList.add("input-error");
    zonaEl.addEventListener(
      "input",
      () => zonaEl.classList.remove("input-error"),
      { once: true },
    );
    return;
  }

  const zonaNome =
    zonaEl.options[zonaEl.selectedIndex].getAttribute("data-nome");
  const lugares = JSON.parse(
    zonaEl.options[zonaEl.selectedIndex].getAttribute("data-lugares") || "[]",
  );

  const box = document.getElementById("modal").querySelector(".modal");
  const response = await fetch("templates/reservas/estacionamento-disponivel.html");
  box.innerHTML = await response.text();

  document.getElementById("est-zona-nome").textContent = zonaNome;
  const lista = document.getElementById("est-lista");
  const vazio = document.getElementById("est-vazio");

  if (lugares.length === 0) {
    lista.style.display = "none";
    vazio.style.display = "block";
  } else {
    lugares.forEach((l) => {
      const item = document.createElement("div");
      item.className = "est-item";
      item.innerHTML = `
        <div class="est-item-codigo">
          <i data-lucide="parking-square"></i>
          ${l.codigo}
        </div>
        <span class="est-badge">Disponível</span>
      `;
      lista.appendChild(item);
    });
  }
  lucide.createIcons();
}

function fecharModalBtn() {
  document.getElementById("modal").classList.remove("active");
}

function fecharModal(event) {
  if (event.target === document.getElementById("modal")) {
    document.getElementById("modal").classList.remove("active");
  }
}

