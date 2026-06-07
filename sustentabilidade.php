<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('acesso_iot');

require_once __DIR__ . '/sustentabilidade-dados.php';
require_once __DIR__ . '/alertas-dados.php';

$dados = dadosSustentabilidade();
$podeExportarCsv = utilizadorTemPermissao('exportar_relatorios');
$graficoAtual = $dados['graficos']['energia'];
$pontosConsumo = pontosGraficoSustentabilidade($graficoAtual['evolucao'], 'principal', $graficoAtual['min_principal'], $graficoAtual['max_principal']);
$pontosPoupanca = pontosGraficoSustentabilidade($graficoAtual['evolucao'], 'secundaria', $graficoAtual['min_secundaria'], $graficoAtual['max_secundaria']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sustentabilidade | PCU - UPT</title>
  <link rel="stylesheet" href="assets/css/sidebar-admin.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/page-layout.css">
  <link rel="stylesheet" href="assets/css/sustentabilidade.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content sustentabilidade-page">

  <div class="page-header-flex">
    <div class="page-header-text">
      <h1>Relatórios de Sustentabilidade</h1>
      <p>Análise de eficiência e consumo energético</p>
      <small class="periodo">Período analisado: <?= h($dados['periodo']) ?></small>
    </div>
    <?php if ($podeExportarCsv): ?>
    <a class="export-button" href="sustentabilidade-exportar.php?tipo=csv">
      <i data-lucide="download"></i> Exportar CSV
    </a>
    <?php endif; ?>
  </div>

  <section class="summary-grid">
    <?php foreach ($dados['resumo'] as $indicador): ?>
      <article class="summary-card">
        <header>
          <i data-lucide="<?= h($indicador['icone']) ?>" class="icon-<?= h($indicador['cor']) ?>"></i>
          <h2><?= h($indicador['titulo']) ?></h2>
        </header>
        <strong class="<?= h($indicador['valor_cor']) ?>"><?= h($indicador['valor']) ?></strong>
        <p class="<?= h($indicador['descricao_cor']) ?>"><?= h($indicador['descricao']) ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="goals overview-section">
    <h2><i data-lucide="award"></i> Metas de Sustentabilidade 2026</h2>
    <div class="goals-grid">
      <?php foreach ($dados['metas'] as $meta): ?>
        <article class="goal <?= h($meta['classe']) ?>">
          <div>
            <h3><?= h($meta['titulo']) ?></h3>
            <p><?= h($meta['descricao']) ?></p>
            <span><?= h($meta['estado']) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="chart-card overview-section">
    <h2><?= h($graficoAtual['titulo']) ?></h2>
    <p><?= h($graficoAtual['descricao']) ?></p>
    <svg class="line-chart" viewBox="0 0 1000 410" aria-label="Gráfico de consumo energético">
      <g class="grid-lines">
        <line x1="75" y1="30" x2="965" y2="30"/>
        <line x1="75" y1="125" x2="965" y2="125"/>
        <line x1="75" y1="220" x2="965" y2="220"/>
        <line x1="75" y1="315" x2="965" y2="315"/>
      </g>
      <polyline class="consumption-line" points="<?= h($pontosConsumo) ?>"/>
      <polyline class="saving-line" points="<?= h($pontosPoupanca) ?>"/>
    </svg>
    <div class="legend">
      <span class="navy-line"></span><b><?= h($graficoAtual['legenda_principal']) ?></b>
      <span class="green-line"></span><b><?= h($graficoAtual['legenda_secundaria']) ?></b>
    </div>
  </section>

  <section class="analysis-grid">
    <article class="period-card overview-section">
      <h2><?= h($graficoAtual['titulo_periodo']) ?></h2>
      <div class="bar-chart">
        <div class="bars">
          <?php foreach ($graficoAtual['evolucao'] as $linha): ?>
            <div><span style="height:<?= (int) $linha['barra'] ?>%"></span><small><?= h($linha['mes']) ?></small></div>
          <?php endforeach; ?>
        </div>
      </div>
    </article>
    <article class="trend-card overview-section">
      <h2>Análise de Tendência</h2>
      <p><strong><?= h($graficoAtual['tendencia']['estado']) ?></strong></p>
      <p><?= h($graficoAtual['tendencia']['descricao']) ?></p>
      <h3>Principais Fatores</h3>
      <ul>
        <?php foreach ($graficoAtual['tendencia']['fatores'] as $fator): ?>
          <li><?= h($fator) ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="projection">
        <strong>Projeção Anual</strong>
        <p><?= h($graficoAtual['tendencia']['projecao']) ?></p>
      </div>
    </article>
  </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
