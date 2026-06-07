<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('acesso_iot');

require_once __DIR__ . '/monitorizacao-dados.php';

if (!function_exists('h')) {
    function h(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

$dados = dadosIot();
$podeAtualizarLeituras = utilizadorTemPermissao('gestao_espacos');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitorização IoT | PCU - UPT</title>
  <link rel="stylesheet" href="assets/css/sidebar-admin.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/page-layout.css">
  <link rel="stylesheet" href="assets/css/iot.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content iot-page">

  <div class="page-header-flex">
    <div class="page-header-text">
      <h1>Centro de Monitorização IoT</h1>
      <p>Monitorização em tempo real de sensores distribuídos pelo campus</p>
      <small class="last-update" data-last-update>Última atualização: <?= h($dados['atualizado_em']) ?></small>
    </div>
    <?php if ($podeAtualizarLeituras): ?>
    <button class="refresh-readings-button" type="button" data-collect-readings>Atualizar leituras</button>
    <?php endif; ?>
  </div>

  <section class="summary-grid">
    <article class="stat-card"><h2>Total de Sensores</h2><strong data-summary-total><?= h($dados['total_sensores']) ?></strong><p>Instalados no campus</p></article>
    <article class="stat-card"><h2>Sensores Ativos</h2><strong class="green" data-summary-active><?= h($dados['ativos']) ?></strong><p data-summary-active-text><?= h($dados['percentagem_ativos']) ?></p></article>
    <article class="stat-card"><h2>Alertas Críticos</h2><strong class="red" data-summary-critical><?= h($dados['criticos']) ?></strong><p>Requerem atenção imediata</p></article>
    <article class="stat-card"><h2>Avisos</h2><strong class="orange" data-summary-warning><?= h($dados['avisos']) ?></strong><p>Para monitorização</p></article>
  </section>

  <section class="filters-card">
    <h2>Filtros</h2>
    <div class="filters-grid">
      <label><span>Edifício</span><select data-filter-building><option value="">Todos os Edifícios</option><option value="a">Edifício A</option><option value="b">Edifício B</option><option value="c">Edifício C</option></select></label>
      <label><span>Tipo de Sensor</span><select data-filter-type><option value="">Todos os Tipos</option><option value="temperatura">Temperatura</option><option value="co2">Qualidade do Ar (CO₂)</option><option value="humidade">Humidade</option><option value="energia">Energia</option></select></label>
      <label><span>Pesquisar</span><span class="search"><i data-lucide="search"></i><input placeholder="ID ou recurso..." data-filter-search></span></label>
    </div>
  </section>

  <section class="sensor-grid" data-sensor-grid>
    <?php foreach ($dados['sensores'] as $sensor): ?>
      <article class="sensor-card" data-building="<?= h($sensor['edificio_key']) ?>" data-type="<?= h($sensor['tipo']) ?>" data-search="<?= h($sensor['pesquisa']) ?>">
        <header>
          <span class="sensor-icon <?= h($sensor['classe']) ?>"><i data-lucide="<?= h(match ($sensor['icone']) { 'wind' => 'wind', 'zap' => 'zap', 'droplets' => 'droplets', 'gauge' => 'gauge', default => 'thermometer' }) ?>"></i></span>
          <div><h3><?= h($sensor['codigo']) ?></h3><p><?= h($sensor['tipo_label']) ?></p></div>
          <span class="status <?= h($sensor['estado']) ?>"><?= h($sensor['estado_label']) ?></span>
        </header>
        <strong class="value"><?= h($sensor['valor_formatado']) ?> <small><?= h($sensor['unidade']) ?></small></strong>
        <p>Recurso: <b><?= h($sensor['localizacao']) ?></b></p>
        <p>Edifício: <b><?= h($sensor['edificio']) ?></b></p>
        <p>Piso: <b><?= h($sensor['piso']) ?></b></p>
        <small>Atualizado: <?= h($sensor['atualizado']) ?></small>
      </article>
    <?php endforeach; ?>
  </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/iot.js"></script>
</body>
</html>
