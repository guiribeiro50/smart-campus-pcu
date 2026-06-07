<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPerfil(['administrador', 'funcionario_administrativo', 'staff']);

require_once __DIR__ . '/dashboard-dados.php';

$dados = dadosDashboard();
$temAlertasPendentes = (int) ($dados['alertas_resumo']['pendentes'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="pt">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Painel de controlo da Plataforma Campus Universitário." />
    <title>Dashboard | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
  </head>

  <body>
    <svg class="svg-sprite" aria-hidden="true">
      <symbol id="icon-building" viewBox="0 0 24 24"><path d="M5 21V5.5A1.5 1.5 0 0 1 6.5 4h7A1.5 1.5 0 0 1 15 5.5V21" /><path d="M15 9h2.5A1.5 1.5 0 0 1 19 10.5V21M3 21h18M8 8h4M8 12h4M8 16h4" /></symbol>
      <symbol id="icon-grid" viewBox="0 0 24 24"><rect width="7" height="7" x="3" y="3" rx="1" /><rect width="7" height="7" x="14" y="3" rx="1" /><rect width="7" height="7" x="3" y="14" rx="1" /><rect width="7" height="7" x="14" y="14" rx="1" /></symbol>
      <symbol id="icon-wifi" viewBox="0 0 24 24"><path d="M5 10a10 10 0 0 1 14 0M8.5 13.5a5 5 0 0 1 7 0M12 17h.01" /></symbol>
      <symbol id="icon-calendar" viewBox="0 0 24 24"><rect width="18" height="17" x="3" y="4" rx="2" /><path d="M7 2v4M17 2v4M3 9h18" /></symbol>
      <symbol id="icon-pin" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></symbol>
      <symbol id="icon-leaf" viewBox="0 0 24 24"><path d="M20 4C11 4 5.5 7.8 5.5 13.2A5.4 5.4 0 0 0 11 18.5C16.4 18.5 20 13 20 4Z" /><path d="M4 21c2.2-5.3 6.2-8.4 11.7-10.3" /></symbol>
      <symbol id="icon-shield" viewBox="0 0 24 24"><path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10Z" /><path d="m9 12 2 2 4-4" /></symbol>
      <symbol id="icon-plus-square" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2" /><path d="M12 8v8M8 12h8" /></symbol>
      <symbol id="icon-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" /></symbol>
      <symbol id="icon-gauge" viewBox="0 0 24 24"><path d="M4.5 18a8 8 0 1 1 15 0M12 14l3-4M12 18h.01" /></symbol>
      <symbol id="icon-terminal" viewBox="0 0 24 24"><path d="m4 17 6-5-6-5M12 19h8" /></symbol>
      <symbol id="icon-logout" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-6" /></symbol>
      <symbol id="icon-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M14 21h-4" /></symbol>
      <symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></symbol>
      <symbol id="icon-warning" viewBox="0 0 24 24"><path d="m21 19-8-14a1.15 1.15 0 0 0-2 0L3 19a1.15 1.15 0 0 0 1 1.7h16a1.15 1.15 0 0 0 1-1.7Z" /><path d="M12 9v4M12 17h.01" /></symbol>
      <symbol id="icon-bolt" viewBox="0 0 24 24"><path d="m13 2-9 12h8l-1 8 9-12h-8l1-8Z" /></symbol>
      <symbol id="icon-thermometer" viewBox="0 0 24 24"><path d="M14 14.8V5a2 2 0 0 0-4 0v9.8a4 4 0 1 0 4 0Z" /></symbol>
      <symbol id="icon-droplets" viewBox="0 0 24 24"><path d="M8 3.2S4.5 7 4.5 9.4A3.5 3.5 0 0 0 11 11M14.5 2S20 8 20 12a5.5 5.5 0 0 1-11 0" /></symbol>
      <symbol id="icon-trending-down" viewBox="0 0 24 24"><path d="m3 7 6 6 4-4 8 8M15 17h6v-6" /></symbol>
    </svg>

    <?php include 'sidebar-admin.php'; ?>

    <div class="sidebar-overlay" data-sidebar-close></div>

    <div class="app-shell">
      <header class="topbar">
        <button class="menu-button" type="button" data-sidebar-open aria-label="Abrir menu">
          <svg aria-hidden="true"><use href="#icon-menu"></use></svg>
        </button>
        <a class="notifications-button" href="alertas.php" aria-label="Abrir alertas dos sensores">
          <svg aria-hidden="true"><use href="#icon-bell"></use></svg>
          <span class="notification-dot" data-notification-dot <?= $temAlertasPendentes ? '' : 'hidden' ?>></span>
        </a>
      </header>

      <main class="dashboard-content">
        <header class="page-heading">
          <h1>Painel de Controlo</h1>
          <p>Monitorização e gestão do campus</p>
        </header>

        <section class="summary-grid" aria-label="Resumo do campus">
          <?php foreach ($dados['resumo'] as $card): ?>
            <article class="summary-card <?= h($card['classe']) ?>" data-summary-card="<?= h($card['chave']) ?>">
              <div class="summary-title">
                <svg aria-hidden="true"><use href="#<?= h($card['icone']) ?>"></use></svg>
                <h2><?= h($card['titulo']) ?></h2>
              </div>
              <strong class="summary-value" data-summary-value><?= h($card['valor']) ?></strong>
              <p class="summary-description <?= h($card['descricao_classe']) ?>" data-summary-description>
                <?php if ($card['descricao_classe'] === 'decrease'): ?>
                  <svg aria-hidden="true"><use href="#icon-trending-down"></use></svg>
                <?php endif; ?>
                <?= h($card['descricao']) ?>
              </p>
            </article>
          <?php endforeach; ?>
        </section>

        <section class="dashboard-grid">
          <article class="panel alerts-panel">
            <header class="panel-heading">
              <h2>Alertas de Sensores</h2>
              <p>Situações que requerem atenção</p>
            </header>

            <div class="alerts-list" data-alerts-list>
              <?php if ($dados['alertas']): ?>
                <?php foreach ($dados['alertas'] as $alerta): ?>
                <section class="alert-item <?= h($alerta['classe']) ?>">
                  <svg aria-hidden="true"><use href="#icon-warning"></use></svg>
                  <div>
                    <h3><?= h($alerta['titulo']) ?></h3>
                    <p><?= h($alerta['detalhe']) ?></p>
                    <span><?= h($alerta['nivel']) ?></span>
                  </div>
                </section>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="empty-panel-message">Sem alertas pendentes neste momento.</p>
              <?php endif; ?>
            </div>
          </article>

          <article class="panel consumption-panel">
            <header class="panel-heading">
              <h2>Consumo por Edifício</h2>
              <p>Consumo energético atual (kW)</p>
            </header>

            <div class="buildings-list" data-buildings-list>
              <?php foreach ($dados['consumos'] as $consumo): ?>
                <section class="building-item">
                  <div class="building-heading">
                    <div>
                      <svg aria-hidden="true"><use href="#icon-bolt"></use></svg>
                      <h3><?= h($consumo['nome']) ?></h3>
                    </div>
                    <strong><?= h($consumo['valor']) ?></strong>
                  </div>
                  <span class="progress-track"><span class="progress-value" style="width: <?= h((string) $consumo['percentagem']) ?>%"></span></span>
                </section>
              <?php endforeach; ?>
            </div>
          </article>
        </section>
      </main>
    </div>

    <a class="notifications-popover visible" href="alertas.php" aria-label="Abrir alertas dos sensores">
      <svg aria-hidden="true"><use href="#icon-bell"></use></svg>
      <strong>Notificações</strong>
      <span class="popover-count danger-count" data-popover-critical><?= h((string) $dados['alertas_resumo']['criticos']) ?></span>
      <span class="popover-count warning-count" data-popover-warning><?= h((string) $dados['alertas_resumo']['avisos']) ?></span>
      <svg aria-hidden="true"><use href="#icon-bell"></use></svg>
    </a>

    <script src="assets/js/dashboard.js?v=2"></script>
  </body>
</html>
