<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/sensores-dados.php';

$mensagemSucesso = '';
$mensagemErro = '';
$valores = [
    'codigo' => '',
    'tipo' => 'temperatura',
    'recurso_tipo' => 'sala',
    'recurso_id' => '',
    'edificio' => 'a',
    'piso' => '1',
    'estado' => 'ativo',
    'firmware' => 'v2.3.1',
    'intervalo_recolha' => '30',
    'data_instalacao' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $valores = array_merge($valores, [
        'codigo' => trim($_POST['codigo'] ?? ''),
        'tipo' => $_POST['tipo'] ?? 'temperatura',
        'recurso_tipo' => $_POST['recurso_tipo'] ?? 'sala',
        'recurso_id' => (string) ($_POST['recurso_id'] ?? ''),
        'edificio' => $_POST['edificio'] ?? 'a',
        'piso' => (string) ($_POST['piso'] ?? '1'),
        'estado' => $_POST['estado'] ?? 'ativo',
        'firmware' => trim($_POST['firmware'] ?? 'v2.3.1'),
        'intervalo_recolha' => (string) ($_POST['intervalo_recolha'] ?? '30'),
        'data_instalacao' => $_POST['data_instalacao'] ?? '',
    ]);

    try {
        criarSensorBaseDados($valores);
        $mensagemSucesso = 'Sensor registado com sucesso na base de dados.';
        $valores['codigo'] = '';
        $valores['recurso_id'] = '';
        $valores['piso'] = '1';
        $valores['estado'] = 'ativo';
        $valores['firmware'] = 'v2.3.1';
        $valores['intervalo_recolha'] = '30';
        $valores['data_instalacao'] = '';
    } catch (Throwable $erro) {
        $mensagemErro = $erro->getMessage();
    }
}

function criarSensorBaseDados(array $dados): void
{
    $codigo = trim($dados['codigo']);
    $tipo = tipoSensorParaBaseDados($dados['tipo']);
    $estado = estadoSensorParaBaseDados($dados['estado']);
    $recursoTipo = recursoTipoSensorParaBaseDados((string) ($dados['recurso_tipo'] ?? 'sala'));
    $recurso = obterRecursoSensor($recursoTipo, $dados['recurso_id'] ?? null);
    $intervalo = filter_var($dados['intervalo_recolha'], FILTER_VALIDATE_INT);
    $firmware = trim($dados['firmware']);

    if ($codigo === '') {
        throw new RuntimeException('Indica o ID do sensor.');
    }

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
        "INSERT INTO sensor_sustentabilidade
            (codigo, tipo, recurso_tipo, recurso_id, ativo, firmware, estado_tecnico, intervalo_recolha)
         VALUES
            (:codigo, :tipo, :recurso_tipo, :recurso_id, :ativo, :firmware, :estado_tecnico, :intervalo_recolha)"
    );

    try {
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
    } catch (PDOException $erro) {
        if ($erro->getCode() === '23000') {
            throw new RuntimeException('Já existe um sensor com esse ID.');
        }

        throw $erro;
    }
}

function tipoSensorParaBaseDados(string $tipo): string
{
    return match ($tipo) {
        'co2' => 'qualidade_ar',
        'energia' => 'consumo_energetico',
        'humidade' => 'humidade',
        default => 'temperatura',
    };
}

function recursoTipoSensorParaBaseDados(string $tipo): string
{
    return $tipo === 'laboratorio' ? 'laboratorio' : 'sala';
}

function estadoSensorParaBaseDados(string $estado): string
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
  <title>Adicionar Sensor | PCU - UPT</title>
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
          <h1>Adicionar Sensor IoT</h1>
          <p>Registo de um novo sensor no sistema de gestão técnica</p>
        </div>
        <a class="secondary-button" href="sensores-listar.php">Voltar à Gestão de Sensores</a>
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
          <p>Preenche os dados de identificação, instalação e comunicação do equipamento.</p>

          <div class="form-grid">
            <label class="field">
              <span>ID do Sensor</span>
              <input name="codigo" type="text" placeholder="Ex.: S-A-101-T" value="<?= h($valores['codigo']) ?>" required>
              <small>Usa o padrão definido para o campus.</small>
            </label>

            <label class="field">
              <span>Tipo</span>
              <select name="tipo" required>
                <option value="temperatura" <?= $valores['tipo'] === 'temperatura' ? 'selected' : '' ?>>Temperatura</option>
                <option value="co2" <?= $valores['tipo'] === 'co2' ? 'selected' : '' ?>>Qualidade do Ar (CO₂)</option>
                <option value="humidade" <?= $valores['tipo'] === 'humidade' ? 'selected' : '' ?>>Humidade</option>
                <option value="energia" <?= $valores['tipo'] === 'energia' ? 'selected' : '' ?>>Energia</option>
              </select>
            </label>

            <label class="field">
              <span>Tipo de Recurso</span>
              <select name="recurso_tipo" required>
                <option value="sala" <?= $valores['recurso_tipo'] === 'sala' ? 'selected' : '' ?>>Sala</option>
                <option value="laboratorio" <?= $valores['recurso_tipo'] === 'laboratorio' ? 'selected' : '' ?>>Laboratório</option>
              </select>
            </label>

            <label class="field">
              <span>Recurso</span>
              <select name="recurso_id" <?= $temRecursos ? 'required' : 'disabled' ?>>
                <?php if ($temRecursos): ?>
                  <option value="">Seleciona um recurso</option>
                  <?php foreach ($recursos as $recurso): ?>
                    <option value="<?= h($recurso['valor']) ?>"
                            data-resource-type="<?= h($recurso['tipo']) ?>"
                            data-building="<?= h($recurso['edificio_key']) ?>"
                            data-building-label="<?= h($recurso['edificio']) ?>"
                            data-floor="<?= h($recurso['piso']) ?>"
                            <?= $valores['recurso_id'] === $recurso['valor'] || ($valores['recurso_tipo'] === $recurso['tipo'] && $valores['recurso_id'] === $recurso['id']) ? 'selected' : '' ?>>
                      <?= h($recurso['tipo_label']) ?> - <?= h($recurso['nome']) ?> - <?= h($recurso['edificio']) ?><?= $recurso['piso'] !== '' ? ', Piso ' . h($recurso['piso']) : '' ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="">Não existem salas ou laboratórios registados</option>
                <?php endif; ?>
              </select>
              <small>Os recursos são carregados das tabelas sala e laboratorio.</small>
            </label>

            <label class="field">
              <span>Edifício</span>
              <select name="edificio" required>
                <option value="a" <?= $valores['edificio'] === 'a' ? 'selected' : '' ?>>Edifício A</option>
                <option value="b" <?= $valores['edificio'] === 'b' ? 'selected' : '' ?>>Edifício B</option>
                <option value="c" <?= $valores['edificio'] === 'c' ? 'selected' : '' ?>>Edifício C</option>
              </select>
            </label>

            <label class="field">
              <span>Piso</span>
              <input name="piso" type="number" min="0" max="20" value="<?= h($valores['piso']) ?>" readonly required>
            </label>

            <label class="field">
              <span>Estado Inicial</span>
              <select name="estado" required>
                <option value="ativo" <?= $valores['estado'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $valores['estado'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                <option value="manutencao" <?= $valores['estado'] === 'manutencao' ? 'selected' : '' ?>>Manutenção</option>
              </select>
            </label>

            <label class="field">
              <span>Firmware</span>
              <input name="firmware" type="text" value="<?= h($valores['firmware']) ?>" required>
            </label>

            <label class="field">
              <span>Intervalo de Recolha</span>
              <input name="intervalo_recolha" type="number" min="1" value="<?= h($valores['intervalo_recolha']) ?>" required>
              <small>Valor indicado em segundos.</small>
            </label>

            <label class="field">
              <span>Data de Instalação</span>
              <input name="data_instalacao" type="date" value="<?= h($valores['data_instalacao']) ?>">
            </label>
          </div>

          <p class="form-note">Depois de registar o sensor, configura os limites de aviso e críticos através da roda dentada disponível na lista de sensores.</p>

          <div class="form-actions">
            <button class="primary-button" type="submit" <?= $temRecursos ? '' : 'disabled' ?>><svg><use href="#plus"></use></svg>Registar Sensor</button>
            <a class="secondary-button" href="sensores-listar.php">Cancelar</a>
          </div>
        </form>

        <aside class="preview-card">
          <h2>Pré-visualização</h2>
          <p>Resumo do sensor que será adicionado ao sistema.</p>
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
