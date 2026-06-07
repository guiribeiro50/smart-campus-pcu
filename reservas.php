<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao(['reservar_salas', 'reservar_laboratorios', 'reservar_equipamentos']);

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: login.php');
    exit;
}

date_default_timezone_set('Europe/Lisbon');

function hReserva(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$utilizadorId = (int) $_SESSION['utilizador_id'];
$edificio = trim((string) ($_GET['edificio'] ?? ''));
$piso = trim((string) ($_GET['piso'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));

$conexao->exec(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

$where = ['ativo = 1'];
$params = [];

if ($edificio !== '') {
    $where[] = 'edificio = ?';
    $params[] = $edificio;
}

if ($piso !== '') {
    $where[] = 'piso = ?';
    $params[] = $piso;
}

if ($search !== '') {
    $where[] = 'nome LIKE ?';
    $params[] = '%' . $search . '%';
}

$stmtSalas = $conexao->prepare(
    'SELECT *
     FROM sala
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY edificio, piso, nome'
);
$stmtSalas->execute($params);
$salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

$stmtOcupadasAgora = $conexao->query(
    "SELECT recurso_id
     FROM reserva
     WHERE tipo_recurso = 'sala'
       AND estado = 'confirmada'
       AND data_inicio <= NOW()
       AND data_fim > NOW()"
);
$salasOcupadasAgora = [];
foreach ($stmtOcupadasAgora->fetchAll(PDO::FETCH_ASSOC) as $linha) {
    $salasOcupadasAgora[(int) $linha['recurso_id']] = true;
}

$stmtTodasReservas = $conexao->query(
    "SELECT r.*, s.nome AS nome_sala, s.edificio, s.piso, u.nome AS nome_utilizador
     FROM reserva r
     INNER JOIN sala s ON s.id = r.recurso_id
     INNER JOIN utilizador u ON u.id = r.utilizador_id
     WHERE r.tipo_recurso = 'sala'
       AND r.estado = 'confirmada'
       AND r.data_fim > NOW()
     ORDER BY r.data_inicio"
);
$todasReservas = $stmtTodasReservas->fetchAll(PDO::FETCH_ASSOC);

$edificios = $conexao->query("SELECT DISTINCT edificio FROM sala WHERE edificio IS NOT NULL ORDER BY edificio")->fetchAll(PDO::FETCH_COLUMN);
$pisos = $conexao->query("SELECT DISTINCT piso FROM sala WHERE piso IS NOT NULL ORDER BY piso")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas – PCU UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/reservas.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content">

    <div class="page-header-flex">
        <div class="page-header-text">
            <h1>Sistema de Reservas</h1>
            <p>Marcação de salas e laboratórios do campus</p>
        </div>
        <a href="minhas-reservas.php" class="novo-recurso-btn" style="text-decoration:none;">
            <i data-lucide="calendar-check"></i>
            Minhas Reservas
        </a>
    </div>

    <div class="overview-section filtros-card">
        <h2 class="section-title">Filtrar Salas</h2>
        <p class="section-subtitle">Encontre a sala ideal para a sua reserva</p>
        <form class="filtros-grid" method="GET" action="">
            <div>
                <label for="edificio">Edifício</label>
                <select name="edificio" id="edificio">
                    <option value="">Todos</option>
                    <?php foreach ($edificios as $edificioOpcao): ?>
                        <option value="<?= hReserva((string) $edificioOpcao) ?>" <?= $edificio === (string) $edificioOpcao ? 'selected' : '' ?>>
                            <?= hReserva((string) $edificioOpcao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="piso">Piso</label>
                <select name="piso" id="piso">
                    <option value="">Todos</option>
                    <?php foreach ($pisos as $pisoOpcao): ?>
                        <option value="<?= hReserva((string) $pisoOpcao) ?>" <?= $piso === (string) $pisoOpcao ? 'selected' : '' ?>>
                            Piso <?= hReserva((string) $pisoOpcao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="search">Pesquisar</label>
                <input type="text" name="search" id="search" placeholder="Nome da sala..." value="<?= hReserva($search) ?>">
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="grid-salas">
        <?php if ($salas): ?>
            <?php foreach ($salas as $sala): ?>
                <?php $ocupadaAgora = isset($salasOcupadasAgora[(int) $sala['id']]); ?>
                <div class="card-sala <?= $ocupadaAgora ? 'indisponivel' : '' ?>">
                    <span class="estado-badge <?= $ocupadaAgora ? 'ocupada' : 'disponivel' ?>">
                        <?= $ocupadaAgora ? 'Indisponível agora' : 'Disponível' ?>
                    </span>
                    <h3><?= hReserva($sala['nome']) ?></h3>
                    <p><?= hReserva($sala['edificio']) ?> · Piso <?= hReserva((string) $sala['piso']) ?></p>
                    <span>Capacidade: <?= hReserva((string) $sala['capacidade']) ?> pessoas</span>
                    <?php if (!empty($sala['descricao'])): ?>
                        <span><?= hReserva((string) $sala['descricao']) ?></span>
                    <?php endif; ?>

                    <form class="form-reserva">
                        <?= csrfField() ?>
                        <input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>">
                        <label for="inicio-<?= (int) $sala['id'] ?>">Início</label>
                        <input type="datetime-local" id="inicio-<?= (int) $sala['id'] ?>" name="data_inicio" required>
                        <label for="fim-<?= (int) $sala['id'] ?>">Fim</label>
                        <input type="datetime-local" id="fim-<?= (int) $sala['id'] ?>" name="data_fim" required>
                        <label for="finalidade-<?= (int) $sala['id'] ?>">Finalidade</label>
                        <input type="text" id="finalidade-<?= (int) $sala['id'] ?>" name="finalidade" placeholder="Ex: Aula, reunião">
                        <button type="submit" name="reservar">Reservar</button>
                        <div class="msg-form hidden"></div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Não existem salas na base de dados.</p>
        <?php endif; ?>
    </div>

    <div class="overview-section">
        <h2 class="section-title">Reservas do Campus</h2>
        <p class="section-subtitle">Todas as reservas confirmadas e ativas</p>

        <?php if ($todasReservas): ?>
            <div class="table-container">
                <table class="reservas-campus-table">
                    <thead>
                        <tr>
                            <th>Sala</th>
                            <th>Utilizador</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Finalidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todasReservas as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= hReserva($row['nome_sala']) ?></strong><br>
                                    <small><?= hReserva($row['edificio']) ?> · Piso <?= hReserva((string) $row['piso']) ?></small>
                                </td>
                                <td><?= hReserva($row['nome_utilizador']) ?></td>
                                <td><?= hReserva(date('d/m/Y H:i', strtotime($row['data_inicio']))) ?></td>
                                <td><?= hReserva(date('d/m/Y H:i', strtotime($row['data_fim']))) ?></td>
                                <td><?= hReserva($row['finalidade'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Não existem reservas ativas no campus.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    document.querySelectorAll('.form-reserva').forEach((form) => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const dados = new FormData(this);
            const msgBox = this.querySelector('.msg-form');

            fetch('reserva-criar-ajax.php', {
                method: 'POST',
                body: dados
            })
            .then((response) => response.text())
            .then((data) => {
                msgBox.classList.remove('hidden', 'sucesso', 'erro');

                if (data.trim() === 'ok') {
                    msgBox.textContent = 'Reserva criada com sucesso!';
                    msgBox.classList.add('sucesso');
                    this.reset();
                    setTimeout(() => location.reload(), 600);
                    return;
                }

                msgBox.textContent = data;
                msgBox.classList.add('erro');
            });
        });
    });
</script>
</body>
</html>
