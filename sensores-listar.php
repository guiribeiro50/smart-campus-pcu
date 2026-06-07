<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/sensores-dados.php';

$resultadoSensores = sensoresGestao();
$sensores = $resultadoSensores['sensores'];
$resumo = resumoSensoresGestao($sensores);
$distribuicao = distribuicaoSensoresGestao($sensores);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestão de Sensores IoT | PCU - UPT</title>
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
      <h1>Gestão de Sensores IoT</h1>
      <p>Configuração e monitorização de sensores distribuídos pelo campus</p>
    </div>
    <a class="primary-button" href="sensores-criar.php">
      <i data-lucide="plus"></i> Adicionar Sensor
    </a>
  </div>

  <section class="summary-grid">
    <article class="stat-card">
      <h2>Total de Sensores</h2>
      <strong><?= h($resumo['total_formatado']) ?></strong>
      <p>Registados na base de dados</p>
    </article>
    <article class="stat-card">
      <h2>Ativos</h2>
      <strong class="green"><?= h($resumo['ativos_formatado']) ?></strong>
      <p>Operacionais</p>
    </article>
    <article class="stat-card">
      <h2>Inativos</h2>
      <strong class="red"><?= h($resumo['inativos_formatado']) ?></strong>
      <p>Offline</p>
    </article>
    <article class="stat-card">
      <h2>Manutenção</h2>
      <strong class="orange"><?= h($resumo['manutencao_formatado']) ?></strong>
      <p>Em manutenção</p>
    </article>
  </section>

  <section class="overview-section sensors-card">
    <div class="section-heading-row">
      <div>
        <h2>Lista de Sensores</h2>
        <p>Gerir e configurar sensores distribuídos pelo campus</p>
      </div>
      <a class="secondary-button" href="alertas-configurar-limites.php">
        <i data-lucide="settings"></i> Configurar Limites
      </a>
    </div>

    <div class="filters-grid">
      <label class="field">
        <span>Pesquisar</span>
        <span class="search"><i data-lucide="search"></i><input type="search" placeholder="ID ou recurso..." data-filter-query></span>
      </label>
      <label class="field">
        <span>Edifício</span>
        <select data-filter-building>
          <option value="">Todos</option>
          <option value="a">Edifício A</option>
          <option value="b">Edifício B</option>
          <option value="c">Edifício C</option>
        </select>
      </label>
      <label class="field">
        <span>Tipo</span>
        <select data-filter-type>
          <option value="">Todos</option>
          <option value="temperatura">Temperatura</option>
          <option value="co2">Qualidade do Ar (CO₂)</option>
          <option value="humidade">Humidade</option>
          <option value="energia">Energia</option>
        </select>
      </label>
      <label class="field">
        <span>Estado</span>
        <select data-filter-status>
          <option value="">Todos</option>
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
          <option value="manutencao">Manutenção</option>
        </select>
      </label>
    </div>

    <div class="table-scroll">
      <table class="sensors-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Recurso</th>
            <th>Edifício</th>
            <th>Piso</th>
            <th>Estado</th>
            <th>Firmware</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody id="sensors-tbody">
          <?php if ($sensores): ?>
            <?php foreach ($sensores as $sensor): ?>
              <tr data-sensor-row
                  data-search="<?= h(textoPesquisaSensor($sensor)) ?>"
                  data-building="<?= h($sensor['edificio']) ?>"
                  data-type="<?= h($sensor['tipo']) ?>"
                  data-status="<?= h($sensor['estado']) ?>"
                  data-codigo="<?= h($sensor['codigo']) ?>">
                <td><?= h($sensor['codigo']) ?></td>
                <td>
                  <span class="tipo-cell">
                    <i data-lucide="<?= h(iconeTipoSensor($sensor['tipo'])) ?>"></i>
                    <?= h($sensor['tipo_label']) ?>
                  </span>
                </td>
                <td><?= h($sensor['localizacao']) ?></td>
                <td><?= h($sensor['edificio_label']) ?></td>
                <td><?= h($sensor['piso']) ?></td>
                <td><span class="badge <?= h(classeEstadoSensor($sensor['estado'])) ?>"><?= h(labelEstadoSensor($sensor['estado'])) ?></span></td>
                <td><?= h($sensor['firmware']) ?></td>
                <td>
                  <div class="actions-cell">
                    <button type="button" class="btn-icon-link" data-toggle-sensor title="Alternar estado" aria-label="Alternar estado">
                      <i data-lucide="power"></i>
                    </button>
                    <button type="button" class="btn-icon-link" data-refresh-sensor title="Atualizar leitura" aria-label="Atualizar leitura">
                      <i data-lucide="refresh-cw"></i>
                    </button>
                    <a class="btn-icon-link" href="sensores-gerir.php?id=<?= urlencode($sensor['codigo']) ?>" title="Gerir sensor">
                      <i data-lucide="settings"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr class="empty-row visible"><td colspan="8">Ainda não existem sensores registados na base de dados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="technical-strip">
      <span class="technical-strip-title">Sistema de Gestão Técnica</span>
      <span>A exibir <b data-visible-count><?= h((string) count($sensores)) ?></b> de <?= h($resumo['total_formatado']) ?> sensores</span>
      <button type="button" class="secondary-button" data-refresh-all>
        <i data-lucide="refresh-cw"></i> Atualizar Todos
      </button>
    </div>
  </section>

  <section class="bottom-grid">
    <article class="overview-section">
      <h2>Distribuição por Tipo</h2>
      <div class="distribution-list">
        <?php foreach ($distribuicao as $item): ?>
          <div class="distribution-item">
            <span class="distribution-item-left">
              <i data-lucide="<?= h($item['icone']) ?>"></i>
              <?= h($item['label']) ?>
            </span>
            <strong><?= h($item['total']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </article>
    <article class="overview-section">
      <h2>Informações Técnicas</h2>
      <div class="tech-info-list">
        <div class="tech-info-item">
          <strong>Protocolo de Comunicação</strong>
          <p>LoRaWAN, Zigbee, MQTT</p>
        </div>
        <div class="tech-info-item">
          <strong>Intervalo de Leitura</strong>
          <p>30 segundos (ajustável)</p>
        </div>
        <div class="tech-info-item">
          <strong>Retenção de Dados</strong>
          <p>24 meses (histórico completo)</p>
        </div>
        <div class="tech-info-item">
          <strong>Firmware Atual</strong>
          <p>v2.3.1 (última versão estável)</p>
        </div>
      </div>
    </article>
  </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/sensores.js"></script>
</body>
</html>
