<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/sensores-dados.php';
$sensor = obterSensorGestao($_GET['id'] ?? null);

if ($sensor === null) {
    header('Location: sensores-listar.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerir Sensor | PCU - UPT</title>
  <link rel="stylesheet" href="assets/css/sidebar-admin.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/page-layout.css">
  <link rel="stylesheet" href="assets/css/sensores.css">
</head>

<body>

  <?php include 'sidebar-admin.php'; ?>

  <main class="main-content sensores-page">
      <div class="page-header-flex">
        <div class="page-header-text">
          <h1>Gerir Sensor IoT</h1>
          <p>Configuração do equipamento <?= h($sensor['codigo']) ?></p>
        </div>
      </div>

      <section class="details-card">
        <h2>Resumo do Sensor</h2>
        <div class="details-grid">
          <div><span>ID</span><strong><?= h($sensor['codigo']) ?></strong></div>
          <div><span>Tipo</span><strong><?= h($sensor['tipo_label']) ?></strong></div>
          <div><span>Recurso</span><strong><?= h($sensor['localizacao']) ?>, <?= h($sensor['edificio_label']) ?></strong></div>
          <div><span>Firmware</span><strong><?= h($sensor['firmware']) ?></strong></div>
        </div>
      </section>

      <section class="management-grid">
        <article class="management-card">
          <span class="management-icon"><i data-lucide="pencil"></i></span>
          <h2>Editar Dados Gerais</h2>
          <p>Alterar recurso associado, estado, firmware ou intervalo de recolha.</p>
          <a class="secondary-button" href="sensores-editar.php?id=<?= urlencode($sensor['codigo']) ?>">Editar sensor</a>
        </article>
        <article class="management-card">
          <span class="management-icon"><i data-lucide="settings"></i></span>
          <h2>Configurar Limites</h2>
          <p>Definir os valores mínimos e máximos que originam avisos ou alertas críticos.</p>
          <a class="secondary-button" href="alertas-configurar-limites.php?sensor=<?= urlencode($sensor['codigo']) ?>">Configurar limites</a>
        </article>
        <article class="management-card danger-card">
          <span class="management-icon danger-icon"><i data-lucide="trash-2"></i></span>
          <h2>Remover Sensor</h2>
          <p>Retirar o sensor da operação mantendo o histórico técnico para relatórios e auditoria.</p>
          <a class="danger-button" href="sensores-remover.php?id=<?= urlencode($sensor['codigo']) ?>">Remover sensor</a>
        </article>
      </section>

      <div class="form-actions"><a class="secondary-button" href="sensores-listar.php">Voltar à lista de sensores</a></div>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>if (window.lucide) window.lucide.createIcons();</script>
</body>
</html>
