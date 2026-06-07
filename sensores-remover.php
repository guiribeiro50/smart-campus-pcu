<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/sensores-dados.php';
$codigoSensor = $_GET['id'] ?? $_POST['codigo'] ?? null;
$sensor = obterSensorGestao($codigoSensor);

if ($sensor === null) {
    header('Location: sensores-listar.php');
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';
$remocaoConfirmada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $confirmacao = trim($_POST['confirmacao'] ?? '');

    try {
        if ($confirmacao !== $sensor['codigo']) {
            throw new RuntimeException('Escreve exatamente o ID do sensor para confirmar a remoção.');
        }

        removerSensorBaseDados($sensor['codigo']);
        $sensor = obterSensorGestao($sensor['codigo']) ?? $sensor;
        $mensagemSucesso = 'Sensor retirado da operação com sucesso. O histórico técnico foi mantido.';
        $remocaoConfirmada = true;
    } catch (Throwable $erro) {
        $mensagemErro = $erro->getMessage();
    }
}

function removerSensorBaseDados(string $codigo): void
{
    $stmt = obterLigacaoBD()->prepare(
        "UPDATE sensor_sustentabilidade
         SET ativo = 0,
             estado_tecnico = 'inativo'
         WHERE codigo = :codigo"
    );
    $stmt->execute(['codigo' => $codigo]);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Remover Sensor | PCU - UPT</title>
  <link rel="stylesheet" href="assets/css/sidebar-admin.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/page-layout.css">
  <link rel="stylesheet" href="assets/css/sensores.css">
</head>

<body>

  <?php include 'sidebar-admin.php'; ?>

  <main class="main-content sensores-page">
      <div class="section-heading-row">
        <div class="page-header-text">
          <h1>Remover Sensor IoT</h1>
          <p>Confirmação de remoção do equipamento <?= h($sensor['codigo']) ?></p>
        </div>
        <a class="secondary-button" href="sensores-gerir.php?id=<?= urlencode($sensor['codigo']) ?>">Voltar ao Sensor</a>
      </div>

      <?php if ($mensagemSucesso !== ''): ?>
        <div class="success-message visible" data-success-message><?= h($mensagemSucesso) ?></div>
      <?php endif; ?>

      <?php if ($mensagemErro !== ''): ?>
        <div class="success-message error-message visible" data-success-message><?= h($mensagemErro) ?></div>
      <?php endif; ?>

      <section class="warning-panel">
        <i data-lucide="alert-triangle"></i>
        <div>
          <h2>Atenção</h2>
          <p>Esta ação retira o sensor da operação. O histórico de leituras e alertas deve ser mantido para relatórios, auditoria e sustentabilidade.</p>
        </div>
      </section>

      <section class="removal-grid">
        <article class="details-card">
          <h2>Sensor Selecionado</h2>
          <div class="details-grid">
            <div><span>ID</span><strong><?= h($sensor['codigo']) ?></strong></div>
            <div><span>Tipo</span><strong><?= h($sensor['tipo_label']) ?></strong></div>
            <div><span>Recurso</span><strong><?= h($sensor['localizacao']) ?></strong></div>
            <div><span>Edifício</span><strong><?= h($sensor['edificio_label']) ?></strong></div>
            <div><span>Piso</span><strong><?= h($sensor['piso']) ?></strong></div>
            <div><span>Estado</span><strong><?= h($sensor['estado']) ?></strong></div>
          </div>
        </article>

        <article class="details-card">
          <h2>Consequências</h2>
          <ul class="consequence-list">
            <li>O sensor deixa de aparecer como operacional.</li>
            <li>Não serão recolhidas novas leituras.</li>
            <li>Alertas pendentes devem ser revistos pela equipa técnica.</li>
            <li>O histórico permanece disponível para relatórios.</li>
          </ul>
        </article>
      </section>

      <form class="confirmation-card" method="post" data-removal-form data-sensor-code="<?= h($sensor['codigo']) ?>">
        <?= csrfField() ?>
        <input type="hidden" name="codigo" value="<?= h($sensor['codigo']) ?>">
        <h2>Confirmar Remoção</h2>
        <?php if ($remocaoConfirmada): ?>
          <p>O sensor já foi retirado da operação. Podes voltar à gestão de sensores para confirmar o novo estado.</p>
        <?php else: ?>
          <p>Para evitar erros, escreve o ID do sensor antes de confirmar.</p>
          <label class="field">
            <span>ID do sensor</span>
            <input name="confirmacao" type="text" placeholder="<?= h($sensor['codigo']) ?>" autocomplete="off">
          </label>
        <?php endif; ?>
        <div class="form-actions">
          <a class="secondary-button" href="<?= $remocaoConfirmada ? 'sensores-listar.php' : 'sensores-gerir.php?id=' . urlencode($sensor['codigo']) ?>"><?= $remocaoConfirmada ? 'Voltar à Gestão de Sensores' : 'Cancelar' ?></a>
          <?php if (!$remocaoConfirmada): ?>
            <button class="danger-button" type="submit" data-remove-button>Confirmar Remoção</button>
          <?php endif; ?>
        </div>
      </form>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="assets/js/sensores-remover.js"></script>
</body>
</html>
