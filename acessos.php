<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

$hoje = $conn->query("
    SELECT COUNT(*) as total
    FROM acesso
    WHERE DATE(registado_em) = CURDATE()
")->fetch_assoc()['total'];

$negados = $conn->query("
    SELECT COUNT(*) as total
    FROM acesso
    WHERE resultado = 'negado'
")->fetch_assoc()['total'];

$autorizados = $conn->query("
    SELECT COUNT(*) as total
    FROM acesso
    WHERE resultado = 'autorizado'
")->fetch_assoc()['total'];

$acessos = $conn->query("
    SELECT
        a.*,
        u.nome
    FROM acesso a
    INNER JOIN utilizador u
        ON a.utilizador_id = u.id
    ORDER BY a.registado_em DESC
    LIMIT 50
");

$espacos = [];
$resSalas = $conn->query("SELECT id, nome, edificio, piso FROM sala WHERE ativo = 1 ORDER BY nome");
while ($sala = $resSalas->fetch_assoc()) {
    $espacos[] = [
        'valor' => 'sala:' . (int) $sala['id'],
        'label' => 'Sala - ' . $sala['nome'] . ' (' . $sala['edificio'] . ', Piso ' . $sala['piso'] . ')',
    ];
}

$resLabs = $conn->query("SELECT id, nome, edificio, piso FROM laboratorio WHERE ativo = 1 ORDER BY nome");
while ($lab = $resLabs->fetch_assoc()) {
    $espacos[] = [
        'valor' => 'laboratorio:' . (int) $lab['id'],
        'label' => 'Laboratorio - ' . $lab['nome'] . ' (' . $lab['edificio'] . ', Piso ' . $lab['piso'] . ')',
    ];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controlo de Acessos | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/acessos.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content acessos-page">

    <div class="page-header">
        <h1>Controlo de Acessos</h1>
        <p>Validação e registo de acessos físicos a espaços</p>
    </div>

    <section class="summary-grid" style="grid-template-columns: repeat(3, 1fr);">
        <article class="stat-card">
            <h2>Acessos Hoje</h2>
            <strong><?= (int) $hoje ?></strong>
            <p>Registados hoje</p>
        </article>
        <article class="stat-card">
            <h2>Acessos Negados</h2>
            <strong class="red"><?= (int) $negados ?></strong>
            <p>Total histórico</p>
        </article>
        <article class="stat-card">
            <h2>Acessos Autorizados</h2>
            <strong class="green"><?= (int) $autorizados ?></strong>
            <p>Total histórico</p>
        </article>
    </section>

    <div class="acessos-grid">

        <section class="acessos-card">
            <h2>Log de Acessos</h2>
            <div id="log-acessos">
            <?php while ($acesso = $acessos->fetch_assoc()) { ?>
                <div class="acesso-item">
                    <div class="acesso-topo">
                        <div class="acesso-utilizador">
                            <span class="indicador <?= $acesso['resultado'] === 'autorizado' ? 'verde' : 'vermelho' ?>"></span>
                            <strong><?= htmlspecialchars($acesso['nome']) ?></strong>
                        </div>
                        <span class="estado-acesso <?= $acesso['resultado'] === 'autorizado' ? 'sucesso' : 'erro' ?>">
                            <?= ucfirst($acesso['resultado']) ?>
                        </span>
                    </div>
                    <div class="acesso-info">
                        <?= ucfirst($acesso['tipo_recurso']) ?> #<?= (int) $acesso['recurso_id'] ?> · <?= ucfirst($acesso['metodo']) ?>
                    </div>
                    <div class="hora-acesso">
                        <?= date('d/m/Y H:i:s', strtotime($acesso['registado_em'])) ?>
                    </div>
                </div>
            <?php } ?>
            </div>
        </section>

        <section class="acessos-card">
            <h2>Validar Acesso</h2>
            <form id="acessoForm" class="acesso-form">
                <?= csrfField() ?>
                <label for="utilizador_id">ID do Utilizador</label>
                <input type="number" id="utilizador_id" name="utilizador_id" min="1" placeholder="Ex.: 4" required>

                <label for="espaco">Espaço</label>
                <select id="espaco" name="espaco" required>
                    <option value="">Selecionar espaço...</option>
                    <?php foreach ($espacos as $espaco): ?>
                        <option value="<?= htmlspecialchars($espaco['valor'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($espaco['label'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="acesso-actions">
                    <button type="submit" class="btn-validar">Validar Acesso</button>
                </div>

                <div id="msg" class="msg-form hidden"></div>
            </form>
        </section>

    </div>

</main>

<script>
function adicionarLog(data) {
    const log = document.getElementById('log-acessos');
    const novo = document.createElement('div');
    novo.className = 'acesso-item';

    const topo = document.createElement('div');
    topo.className = 'acesso-topo';

    const utilizador = document.createElement('div');
    utilizador.className = 'acesso-utilizador';

    const indicador = document.createElement('span');
    indicador.className = 'indicador ' + (data.resultado === 'autorizado' ? 'verde' : 'vermelho');

    const nome = document.createElement('strong');
    nome.textContent = data.nome || '';

    utilizador.append(indicador, nome);

    const estado = document.createElement('span');
    estado.className = 'estado-acesso ' + (data.resultado === 'autorizado' ? 'sucesso' : 'erro');
    const resultado = data.resultado || '';
    estado.textContent = resultado.charAt(0).toUpperCase() + resultado.slice(1);

    topo.append(utilizador, estado);

    const info = document.createElement('div');
    info.className = 'acesso-info';
    info.textContent = (data.espaco_nome || ((data.tipo || '') + ' #' + (data.espaco_id || ''))) + ' · manual';

    const hora = document.createElement('div');
    hora.className = 'hora-acesso';
    hora.textContent = data.hora || '';

    novo.append(topo, info, hora);
    log.prepend(novo);
}

function mostrarMensagem(data) {
    const msg = document.getElementById('msg');
    msg.classList.remove('hidden');
    msg.innerText = data.msg;
    msg.className = 'msg-form ' + (data.resultado === 'autorizado' ? 'sucesso' : 'erro');
}

document.getElementById('acessoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('acessos-validar.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            mostrarMensagem(data);
            adicionarLog(data);
        });
});
</script>

</body>
</html>
