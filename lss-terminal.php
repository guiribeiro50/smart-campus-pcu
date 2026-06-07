<?php
session_start();
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('acesso_lss');

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terminal LSS — PCU</title>
  <link rel="stylesheet" href="assets/css/lss-terminal.css">
  <!-- Lucide icons (UMD) -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>
</head>

<body>

  <!-- SIDEBAR -->
  <?php include 'sidebar-admin.php'; ?>

  <!-- MAIN -->
  <main class="main">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-title">
        PCU &rsaquo; <span>Terminal LSS</span>
      </div>
    </header>

    <!-- Page header -->
    <section class="page-header" aria-labelledby="pageTitle">
      <h1 id="pageTitle">Terminal de Comandos LSS</h1>
      <p>Linguagem Específica do Sistema para operações avançadas</p>
    </section>

    <!-- Feature cards -->
    <div class="feature-bar" role="list">
      <div class="feature-card" role="listitem">
        <div class="fc-icon">
          <i data-lucide="terminal" width="16" height="16"></i>
        </div>
        <div>
          <div class="fc-title">Sintaxe Simples</div>
          <div class="fc-desc">Linguagem intuitiva e fácil de aprender com comandos em português</div>
        </div>
      </div>
      <div class="feature-card" role="listitem">
        <div class="fc-icon">
          <i data-lucide="code-2" width="16" height="16"></i>
        </div>
        <div>
          <div class="fc-title">Automação</div>
          <div class="fc-desc">Execute scripts para automatizar tarefas repetitivas</div>
        </div>
      </div>
      <div class="feature-card" role="listitem">
        <div class="fc-icon">
          <i data-lucide="book-open" width="16" height="16"></i>
        </div>
        <div>
          <div class="fc-title">Documentação Integrada</div>
          <div class="fc-desc">Guia completo de sintaxe sempre disponível</div>
        </div>
      </div>
    </div>

    <!-- Main grid -->
    <div class="content-grid">

      <!-- Console panel -->
      <div class="panel" aria-label="Console / Terminal">
        <div class="panel-header">
          <div class="ph-title">
            <i data-lucide="terminal" width="15" height="15"></i>
            Console / Terminal
          </div>
          <div class="ph-desc">Execute scripts e comandos LSS</div>
        </div>

        <div class="terminal-screen"
          id="termScreen"
          role="log"
          aria-live="polite"
          aria-label="Saída do terminal">
        </div>

        <div class="terminal-input-area">
          <div class="terminal-input-wrap">
            <span class="prompt-sym">$</span>
            <input
              type="text"
              id="termInput"
              class="terminal-input"
              placeholder="Digite um comando..."
              autocomplete="off"
              spellcheck="false"
              aria-label="Entrada de comando LSS">
          </div>
          <button id="btnExec" class="btn-exec" type="button">
            <i data-lucide="play" width="14" height="14"></i>
            Executar
          </button>
        </div>

        <div class="quick-cmds">
          <div class="quick-cmds-label">Exemplos de comandos:</div>
          <div class="quick-cmds-list">
            <?php
            $quickCmds = [
              "RESERVA Sala_202 SEMANAL",
              "SENSOR Status Edifício_A",
              "ENERGIA Consumo Biblioteca",
              "ALERTA CO2 > 800",
              "MOBILIDADE Estacionamento Zona_A",
            ];
            foreach ($quickCmds as $qc): ?>
              <button class="qcmd" type="button" data-cmd="<?= htmlspecialchars($qc) ?>">
                <i data-lucide="chevrons-right" width="11" height="11"></i>
                <?= htmlspecialchars($qc) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div><!-- /console panel -->


      <!-- Guide panel -->
      <div class="panel" aria-label="Guia de Sintaxe LSS">
        <div class="panel-header">
          <div class="ph-title">
            <i data-lucide="book-open" width="15" height="15"></i>
            Guia de Sintaxe LSS
          </div>
          <div class="ph-desc">Referência completa de comandos e sintaxe</div>
        </div>

        <!-- Tabs — pill style -->
        <div class="guide-tabs" role="tablist" aria-label="Guia de sintaxe">
          <button class="guide-tab active" type="button" role="tab" aria-selected="true" data-tab="sintaxe">Sintaxe</button>
          <button class="guide-tab" type="button" role="tab" aria-selected="false" data-tab="referencia">Referência Rápida</button>
        </div>

        <!-- Sintaxe pane -->
        <div class="guide-body guide-pane" data-pane="sintaxe" role="tabpanel">
          <pre class="syntax-pre"># Guia de Sintaxe LSS (Linguagem Específica do Sistema)

## 1. COMANDOS DE RESERVA

RESERVA &lt;sala&gt; &lt;periodo&gt;
  Reserva uma sala para um período específico
  Exemplos:
    RESERVA Sala_202 HOJE
    RESERVA Lab_Informatica_1 AMANHA
    RESERVA Sala_305 SEMANAL

CANCELAR_RESERVA &lt;id_reserva&gt;
  Cancela uma reserva existente
  Exemplo:
    CANCELAR_RESERVA RES-001

LISTAR_RESERVAS [edificio]
  Lista reservas ativas
  Exemplos:
    LISTAR_RESERVAS
    LISTAR_RESERVAS Edifício_A

## 2. COMANDOS DE SENSORES

SENSOR &lt;acao&gt; &lt;localizacao&gt;
  Gerencia sensores IoT
  Exemplos:
    SENSOR Status Sala_101
    SENSOR Ativar Lab_3
    SENSOR Leitura Bloco_C

## 3. ALERTAS E ENERGIA

ALERTA &lt;tipo&gt; [operador] [valor]
  Configura alertas de sensor
  Exemplo:
    ALERTA CO2 > 800
    ALERTA TEMPERATURA > 26

ENERGIA Consumo &lt;local&gt;
  Consulta consumo energético
  Exemplo:
    ENERGIA Consumo Biblioteca

## 4. MOBILIDADE

MOBILIDADE ESTACIONAMENTO [zona]
MOBILIDADE BICICLETAS
MOBILIDADE TROTINETES

## 5. UTILITÁRIOS

STATUS      — Estado geral do sistema
AJUDA       — Lista todos os comandos
LIMPAR      — Limpar o terminal</pre>
        </div>

        <!-- Referência Rápida pane -->
        <div class="guide-body guide-pane hidden" data-pane="referencia" role="tabpanel">

          <!-- Comandos Básicos — ícone check-circle verde -->
          <div class="ref-group">
            <div class="ref-group-header">
              <i data-lucide="check-circle" width="16" height="16" class="rg-icon-green"></i>
              Comandos Básicos
            </div>
            <ul>
              <li><code>STATUS</code> — Status geral do sistema</li>
              <li><code>AJUDA</code> — Lista de comandos</li>
              <li><code>LIMPAR</code> — Limpar terminal</li>
            </ul>
          </div>

          <!-- Gestão de Reservas — ícone info azul -->
          <div class="ref-group">
            <div class="ref-group-header">
              <i data-lucide="info" width="16" height="16" class="rg-icon-blue"></i>
              Gestão de Reservas
            </div>
            <ul>
              <li><code>RESERVA</code> — Criar reserva</li>
              <li><code>CANCELAR_RESERVA</code> — Cancelar reserva</li>
              <li><code>LISTAR_RESERVAS</code> — Listar reservas</li>
            </ul>
          </div>

          <!-- Controlo IoT — ícone terminal roxo -->
          <div class="ref-group">
            <div class="ref-group-header">
              <i data-lucide="terminal" width="16" height="16" class="rg-icon-purple"></i>
              Controlo IoT
            </div>
            <ul>
              <li><code>SENSOR</code> — Gerir sensores</li>
              <li><code>ALERTA</code> — Configurar alertas</li>
              <li><code>ENERGIA</code> — Consultar consumo</li>
            </ul>
          </div>

          <!-- Mobilidade — ícone code âmbar -->
          <div class="ref-group">
            <div class="ref-group-header">
              <i data-lucide="code" width="16" height="16" class="rg-icon-amber"></i>
              Mobilidade
            </div>
            <ul>
              <li><code>MOBILIDADE ESTACIONAMENTO</code> — Ver estacionamento</li>
              <li><code>MOBILIDADE BICICLETAS</code> — Ver bicicletas</li>
              <li><code>MOBILIDADE TROTINETES</code> — Ver trotinetes</li>
            </ul>
          </div>

          <div class="note-box">
            <div class="note-title">
              <i data-lucide="info" width="15" height="15"></i>
              Nota Importante
            </div>
            <p>
              Este terminal é uma interface de linha de comandos que permite executar
              operações avançadas no sistema PCU. Os comandos são processados em tempo
              real e integram-se com todos os módulos da plataforma.
            </p>
          </div>

        </div><!-- /referencia pane -->
      </div><!-- /guide panel -->

    </div><!-- /content-grid -->
  </main><!-- /main -->

  <script src="assets/js/lss-terminal.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>
</body>

</html>
