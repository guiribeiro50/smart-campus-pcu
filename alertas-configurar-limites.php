<?php
session_start();
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/sensores-dados.php';
require_once __DIR__ . '/config/database.php';

$sensores = sensoresGestao()['sensores'];
$codigoSelecionado = trim($_GET['sensor'] ?? $_POST['sensor'] ?? '');
$sensorSelecionado = obterSensorGestao($codigoSelecionado !== '' ? $codigoSelecionado : null);
$mensagemSucesso = '';
$mensagemErro = '';
$limiteAviso = '';
$limiteCritico = '';

if ($sensorSelecionado) {
  try {
    $stmt = obterLigacaoBD()->prepare(
      "SELECT limite_aviso, limite_critico
       FROM limites_alerta
       WHERE sensor_sustentabilidade_id = (
         SELECT id FROM sensor_sustentabilidade WHERE codigo = :codigo LIMIT 1
       ) AND ativo = 1
       LIMIT 1"
    );
    $stmt->execute(['codigo' => $sensorSelecionado['codigo']]);
    $limites = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($limites) {
      $limiteAviso = (string) $limites['limite_aviso'];
      $limiteCritico = (string) $limites['limite_critico'];
    }
  } catch (Throwable $erro) {
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sensorSelecionado) {
  validarCsrf();

  $limiteAviso = trim($_POST['limite_aviso'] ?? '');
  $limiteCritico = trim($_POST['limite_critico'] ?? '');

  try {
    $aviso = filter_var($limiteAviso, FILTER_VALIDATE_FLOAT);
    $critico = filter_var($limiteCritico, FILTER_VALIDATE_FLOAT);

    if ($aviso === false || $critico === false || $critico <= $aviso) {
      throw new RuntimeException('Os limites devem ser numéricos e o limite crítico deve ser superior ao de aviso.');
    }

    $pdo = obterLigacaoBD();
    $stmtSensor = $pdo->prepare('SELECT id FROM sensor_sustentabilidade WHERE codigo = :codigo LIMIT 1');
    $stmtSensor->execute(['codigo' => $sensorSelecionado['codigo']]);
    $sensorId = (int) $stmtSensor->fetchColumn();

    if ($sensorId <= 0) {
      throw new RuntimeException('Sensor não encontrado.');
    }

    $stmtExiste = $pdo->prepare(
      'SELECT id FROM limites_alerta WHERE sensor_sustentabilidade_id = :sensor_id AND ativo = 1 LIMIT 1'
    );
    $stmtExiste->execute(['sensor_id' => $sensorId]);
    $limiteId = $stmtExiste->fetchColumn();

    if ($limiteId) {
      $stmt = $pdo->prepare(
        'UPDATE limites_alerta SET limite_aviso = :aviso, limite_critico = :critico WHERE id = :id'
      );
      $stmt->execute(['aviso' => $aviso, 'critico' => $critico, 'id' => $limiteId]);
    } else {
      $stmt = $pdo->prepare(
        'INSERT INTO limites_alerta (sensor_sustentabilidade_id, limite_aviso, limite_critico, ativo)
         VALUES (:sensor_id, :aviso, :critico, 1)'
      );
      $stmt->execute(['sensor_id' => $sensorId, 'aviso' => $aviso, 'critico' => $critico]);
    }

    $mensagemSucesso = 'Limites guardados com sucesso.';
  } catch (Throwable $erro) {
    $mensagemErro = $erro->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Limites | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/sensores.css">
    <link rel="stylesheet" href="assets/css/alertas.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content sensores-page">

    <div class="section-heading-row">
        <div class="page-header-text">
            <h1>Configurar Limites de Alertas</h1>
            <p>Definição dos valores que originam avisos e alertas críticos</p>
        </div>
        <a class="secondary-button" href="sensores-listar.php">Voltar à Gestão de Sensores</a>
    </div>

    <?php if ($mensagemSucesso !== ''): ?>
        <div class="success-message visible"><?= h($mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro !== ''): ?>
        <div class="success-message error-message visible"><?= h($mensagemErro) ?></div>
    <?php endif; ?>

    <section class="limites-layout">
        <form class="form-card" method="post">
          <?= csrfField() ?>
            <h2>Limites do Sensor</h2>
            <p>Seleciona o sensor e define os valores de aviso e crítico.</p>

            <div class="form-grid">
                <label class="field full-width">
                    <span>Sensor</span>
                    <select name="sensor" onchange="if(this.value) window.location='alertas-configurar-limites.php?sensor='+encodeURIComponent(this.value)">
                        <option value="">Seleciona um sensor</option>
                        <?php foreach ($sensores as $sensor): ?>
                            <option value="<?= h($sensor['codigo']) ?>" <?= $sensorSelecionado && $sensor['codigo'] === $sensorSelecionado['codigo'] ? 'selected' : '' ?>>
                                <?= h($sensor['codigo']) ?> — <?= h($sensor['tipo_label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Limite de aviso</span>
                    <input type="number" step="0.01" name="limite_aviso" value="<?= h($limiteAviso) ?>" placeholder="Ex.: 27" <?= $sensorSelecionado ? '' : 'disabled' ?>>
                </label>
                <label class="field">
                    <span>Limite crítico</span>
                    <input type="number" step="0.01" name="limite_critico" value="<?= h($limiteCritico) ?>" placeholder="Ex.: 30" <?= $sensorSelecionado ? '' : 'disabled' ?>>
                </label>
            </div>

            <div class="info-box">
                O limite de aviso gera monitorização reforçada. O limite crítico origina notificação imediata por email ao staff.
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit" <?= $sensorSelecionado ? '' : 'disabled' ?>>Guardar limites</button>
                <a class="secondary-button" href="sensores-listar.php">Cancelar</a>
            </div>
        </form>

        <aside class="preview-card">
            <h2>Informação do Sensor</h2>
            <p>Resumo do equipamento selecionado.</p>
            <?php if ($sensorSelecionado): ?>
                <dl>
                    <div><dt>ID</dt><dd><?= h($sensorSelecionado['codigo']) ?></dd></div>
                    <div><dt>Tipo</dt><dd><?= h($sensorSelecionado['tipo_label']) ?></dd></div>
                    <div><dt>Recurso</dt><dd><?= h($sensorSelecionado['localizacao']) ?></dd></div>
                    <div><dt>Unidade</dt><dd><?= h($sensorSelecionado['unidade']) ?></dd></div>
                </dl>
            <?php else: ?>
                <p>Seleciona um sensor para configurar os limites.</p>
            <?php endif; ?>
        </aside>
    </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) window.lucide.createIcons();</script>
</body>
</html>
