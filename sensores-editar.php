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
$valores = valoresFormularioEdicaoSensor($sensor);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $valores = array_merge($valores, [
        'tipo' => $_POST['tipo'] ?? $valores['tipo'],
        'recurso_tipo' => $_POST['recurso_tipo'] ?? $valores['recurso_tipo'],
        'recurso_id' => (string) ($_POST['recurso_id'] ?? $valores['recurso_id']),
        'edificio' => $_POST['edificio'] ?? $valores['edificio'],
        'piso' => (string) ($_POST['piso'] ?? $valores['piso']),
        'estado' => $_POST['estado'] ?? $valores['estado'],
        'firmware' => trim($_POST['firmware'] ?? $valores['firmware']),
        'intervalo_recolha' => (string) ($_POST['intervalo_recolha'] ?? $valores['intervalo_recolha']),
        'data_instalacao' => $_POST['data_instalacao'] ?? $valores['data_instalacao'],
    ]);

    try {
        atualizarSensorBaseDados($sensor['codigo'], $valores);
        $sensor = obterSensorGestao($sensor['codigo']) ?? $sensor;
        $valores = valoresFormularioEdicaoSensor($sensor);
        $mensagemSucesso = 'Alterações guardadas com sucesso na base de dados.';
    } catch (Throwable $erro) {
        $mensagemErro = $erro->getMessage();
    }
}

function valoresFormularioEdicaoSensor(array $sensor): array
{
    return [
        'codigo' => $sensor['codigo'],
        'tipo' => $sensor['tipo'],
        'recurso_tipo' => $sensor['recurso_tipo'] ?: 'sala',
        'recurso_id' => $sensor['recurso_id'],
        'edificio' => $sensor['edificio'],
        'piso' => $sensor['piso'],
        'estado' => $sensor['estado'],
        'firmware' => $sensor['firmware'],
        'intervalo_recolha' => $sensor['intervalo_recolha'],
        'data_instalacao' => $sensor['data_instalacao'],
    ];
}

function atualizarSensorBaseDados(string $codigo, array $dados): void
{
    $tipo = tipoSensorEdicaoParaBaseDados($dados['tipo']);
    $recursoTipo = recursoTipoSensorEdicaoParaBaseDados((string) ($dados['recurso_tipo'] ?? 'sala'));
    $recurso = obterRecursoSensor($recursoTipo, $dados['recurso_id'] ?? null);
    $estado = estadoSensorEdicaoParaBaseDados($dados['estado']);
    $intervalo = filter_var($dados['intervalo_recolha'], FILTER_VALIDATE_INT);
    $firmware = trim($dados['firmware']);

    if ($recurso === null) {
        throw new RuntimeException('Seleciona uma sala ou laboratório existente para associar ao sensor.');
    }

    if ($intervalo === false || $intervalo < 1) {
        throw new RuntimeException('O intervalo de recolha deve ser superior a 0.');
    }

    if ($firmware === '') {
        throw new RuntimeException('Indica a versão de firmware.');
    }

    $stmt = obterLigacaoBD()->prepare(
        "UPDATE sensor_sustentabilidade
         SET tipo = :tipo,
             recurso_tipo = :recurso_tipo,
             recurso_id = :recurso_id,
             ativo = :ativo,
             firmware = :firmware,
             estado_tecnico = :estado_tecnico,
             intervalo_recolha = :intervalo_recolha
         WHERE codigo = :codigo"
    );

    $stmt->execute([
        'codigo' => $codigo,
        'tipo' => $tipo,
        'recurso_tipo' => $recurso['tipo'],
        'recurso_id' => $recurso['id'],
        'ativo' => $estado === 'inativo' ? 0 : 1,
        'firmware' => $firmware,
        'estado_tecnico' => $estado,
        'intervalo_recolha' => $intervalo,
    ]);
}

function tipoSensorEdicaoParaBaseDados(string $tipo): string
{
    return match ($tipo) {
        'co2' => 'qualidade_ar',
        'energia' => 'consumo_energetico',
        'humidade' => 'humidade',
        default => 'temperatura',
    };
}

function recursoTipoSensorEdicaoParaBaseDados(string $tipo): string
{
    return $tipo === 'laboratorio' ? 'laboratorio' : 'sala';
}

function estadoSensorEdicaoParaBaseDados(string $estado): string
{
    return match ($estado) {
        'inativo' => 'inativo',
        'manutencao' => 'manutencao',
        default => 'ativo',
    };
}

$recursos = recursosSensorDisponiveis();
$temRecursos = count($recursos) > 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Sensor | PCU - UPT</title>
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
          <h1>Editar Sensor IoT</h1>
          <p>Atualização dos dados do equipamento <?= h($sensor['codigo']) ?></p>
        </div>
      </div>
      <?php if ($mensagemSucesso !== ''): ?>
        <div class="success-message visible" data-success-message><?= h($mensagemSucesso) ?></div>
      <?php endif; ?>

      <?php if ($mensagemErro !== ''): ?>
        <div class="success-message error-message visible" data-success-message><?= h($mensagemErro) ?></div>
      <?php endif; ?>

      <section class="form-layout">
        <form class="form-card" method="post" data-sensor-form>
          <?= csrfField() ?>
          <h2>Dados do Sensor</h2>
          <p>Atualiza os dados de instalação e comunicação do equipamento.</p>
          <div class="form-grid">
            <label class="field"><span>ID do Sensor</span><input class="readonly-field" name="codigo" type="text" value="<?= h($valores['codigo']) ?>" readonly><small>O identificador não pode ser alterado.</small></label>
            <label class="field"><span>Tipo</span><select name="tipo" required><option value="temperatura" <?= $valores['tipo'] === 'temperatura' ? 'selected' : '' ?>>Temperatura</option><option value="co2" <?= $valores['tipo'] === 'co2' ? 'selected' : '' ?>>Qualidade do Ar (CO₂)</option><option value="humidade" <?= $valores['tipo'] === 'humidade' ? 'selected' : '' ?>>Humidade</option><option value="energia" <?= $valores['tipo'] === 'energia' ? 'selected' : '' ?>>Energia</option></select></label>
            <label class="field"><span>Tipo de Recurso</span><select name="recurso_tipo" required><option value="sala" <?= $valores['recurso_tipo'] === 'sala' ? 'selected' : '' ?>>Sala</option><option value="laboratorio" <?= $valores['recurso_tipo'] === 'laboratorio' ? 'selected' : '' ?>>Laboratório</option></select></label>
            <label class="field"><span>Recurso</span><select name="recurso_id" <?= $temRecursos ? 'required' : 'disabled' ?>><?php if ($temRecursos): ?><option value="">Seleciona um recurso</option><?php foreach ($recursos as $recurso): ?><option value="<?= h($recurso['valor']) ?>" data-resource-type="<?= h($recurso['tipo']) ?>" data-building="<?= h($recurso['edificio_key']) ?>" data-building-label="<?= h($recurso['edificio']) ?>" data-floor="<?= h($recurso['piso']) ?>" <?= $valores['recurso_id'] === $recurso['valor'] || ($valores['recurso_tipo'] === $recurso['tipo'] && $valores['recurso_id'] === $recurso['id']) ? 'selected' : '' ?>><?= h($recurso['tipo_label']) ?> - <?= h($recurso['nome']) ?> - <?= h($recurso['edificio']) ?><?= $recurso['piso'] !== '' ? ', Piso ' . h($recurso['piso']) : '' ?></option><?php endforeach; ?><?php else: ?><option value="">Não existem salas ou laboratórios registados</option><?php endif; ?></select></label>
            <label class="field"><span>Edifício</span><select name="edificio" required><option value="a" <?= $valores['edificio'] === 'a' ? 'selected' : '' ?>>Edifício A</option><option value="b" <?= $valores['edificio'] === 'b' ? 'selected' : '' ?>>Edifício B</option><option value="c" <?= $valores['edificio'] === 'c' ? 'selected' : '' ?>>Edifício C</option></select></label>
            <label class="field"><span>Piso</span><input name="piso" type="number" min="0" max="20" value="<?= h($valores['piso']) ?>" readonly required></label>
            <label class="field"><span>Estado</span><select name="estado" required><option value="ativo" <?= $valores['estado'] === 'ativo' ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= $valores['estado'] === 'inativo' ? 'selected' : '' ?>>Inativo</option><option value="manutencao" <?= $valores['estado'] === 'manutencao' ? 'selected' : '' ?>>Manutenção</option></select></label>
            <label class="field"><span>Firmware</span><input name="firmware" type="text" value="<?= h($valores['firmware']) ?>" required></label>
            <label class="field"><span>Intervalo de Recolha</span><input name="intervalo_recolha" type="number" min="1" value="<?= h($valores['intervalo_recolha']) ?>" required><small>Valor indicado em segundos.</small></label>
            <label class="field"><span>Data de Instalação</span><input name="data_instalacao" type="date" value="<?= h($valores['data_instalacao']) ?>"><small>Data informativa; o registo real vem da base de dados.</small></label>
          </div>
          <p class="form-note">Os limites de alerta são configurados separadamente para preservar o histórico técnico do sensor.</p>
          <div class="form-actions"><button class="primary-button" type="submit" <?= $temRecursos ? '' : 'disabled' ?>>Guardar Alterações</button><a class="secondary-button" href="sensores-gerir.php?id=<?= urlencode($sensor['codigo']) ?>">Cancelar</a></div>
        </form>

        <aside class="preview-card">
          <h2>Pré-visualização</h2><p>Resumo atualizado do sensor.</p>
          <dl>
            <div><dt>ID</dt><dd data-preview-code></dd></div>
            <div><dt>Tipo</dt><dd data-preview-type></dd></div>
            <div><dt>Recurso</dt><dd data-preview-location></dd></div>
            <div><dt>Edifício</dt><dd data-preview-building></dd></div>
            <div><dt>Unidade</dt><dd data-preview-unit></dd></div>
          </dl>
        </aside>
      </section>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="assets/js/sensores-criar.js"></script>
</body>
</html>
