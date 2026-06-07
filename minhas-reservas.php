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

function datetimeLocalReserva(?string $valor): string
{
    $timestamp = strtotime((string) $valor);

    return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
}

function labelEstadoReserva(string $estado): string
{
    return match ($estado) {
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'expirada' => 'Concluída',
        default => ucfirst($estado),
    };
}

function classeEstadoReserva(string $estado): string
{
    return match ($estado) {
        'confirmada' => 'confirmada',
        'cancelada' => 'cancelada',
        'expirada' => 'concluida',
        default => 'confirmada',
    };
}

$utilizadorId = (int) $_SESSION['utilizador_id'];

$conexao->exec(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

$stmtReservas = $conexao->prepare(
    "SELECT r.*,
            COALESCE(s.nome, l.nome, CONCAT('Recurso #', r.recurso_id)) AS nome_recurso,
            COALESCE(s.edificio, l.edificio, '') AS edificio,
            COALESCE(s.piso, l.piso, '') AS piso
     FROM reserva r
     LEFT JOIN sala s ON r.tipo_recurso = 'sala' AND s.id = r.recurso_id
     LEFT JOIN laboratorio l ON r.tipo_recurso = 'laboratorio' AND l.id = r.recurso_id
     WHERE r.utilizador_id = ?
       AND r.tipo_recurso IN ('sala', 'laboratorio')
     ORDER BY r.data_inicio DESC"
);
$stmtReservas->execute([$utilizadorId]);
$todasReservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);

$reservasAtivas = array_filter(
    $todasReservas,
    fn (array $row): bool => $row['estado'] === 'confirmada' && strtotime((string) $row['data_fim']) > time()
);
$historicoReservas = array_filter(
    $todasReservas,
    fn (array $row): bool => in_array($row['estado'], ['expirada', 'cancelada'], true)
        || ($row['estado'] === 'confirmada' && strtotime((string) $row['data_fim']) <= time())
);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas – PCU UPT</title>
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
            <h1>Minhas Reservas</h1>
            <p>Salas e laboratórios que reservou</p>
        </div>
        <a href="reservas.php" class="novo-recurso-btn" style="text-decoration:none;">
            <i data-lucide="calendar-plus"></i>
            Nova Reserva
        </a>
    </div>

    <div class="overview-section">
        <h2 class="section-title">Reservas Ativas</h2>
        <p class="section-subtitle">Reservas confirmadas com horário ainda por decorrer</p>

        <?php if ($reservasAtivas): ?>
            <div class="reservas-lista">
                <?php foreach ($reservasAtivas as $row): ?>
                    <article class="reserva-item">
                        <div class="reserva-item-header">
                            <div>
                                <h3><?= hReserva($row['nome_recurso']) ?></h3>
                                <p><?= hReserva($row['edificio']) ?> · Piso <?= hReserva((string) $row['piso']) ?></p>
                            </div>
                            <span class="estado-badge confirmada">Confirmada</span>
                        </div>
                        <div class="reserva-item-detalhes">
                            <div>
                                <strong>Início</strong>
                                <span><?= hReserva(date('d/m/Y H:i', strtotime($row['data_inicio']))) ?></span>
                            </div>
                            <div>
                                <strong>Fim</strong>
                                <span><?= hReserva(date('d/m/Y H:i', strtotime($row['data_fim']))) ?></span>
                            </div>
                            <?php if (!empty($row['finalidade'])): ?>
                                <div>
                                    <strong>Finalidade</strong>
                                    <span><?= hReserva($row['finalidade']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-cancelar btn-alterar-horario" data-target="alterar-<?= (int) $row['id'] ?>">Alterar Horário</button>
                        <button type="button" class="btn-cancelar" data-id="<?= (int) $row['id'] ?>">Cancelar Reserva</button>
                        <form id="alterar-<?= (int) $row['id'] ?>" class="form-reserva form-alterar-horario hidden">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <label for="novo-inicio-<?= (int) $row['id'] ?>">Início</label>
                            <input
                                type="datetime-local"
                                id="novo-inicio-<?= (int) $row['id'] ?>"
                                name="data_inicio"
                                value="<?= hReserva(datetimeLocalReserva($row['data_inicio'])) ?>"
                                required
                            >
                            <label for="novo-fim-<?= (int) $row['id'] ?>">Fim</label>
                            <input
                                type="datetime-local"
                                id="novo-fim-<?= (int) $row['id'] ?>"
                                name="data_fim"
                                value="<?= hReserva(datetimeLocalReserva($row['data_fim'])) ?>"
                                required
                            >
                            <button type="submit">Guardar Alteração</button>
                            <div class="msg-form hidden"></div>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i data-lucide="calendar-x"></i>
                <p>Ainda não tem reservas ativas.</p>
                <a href="reservas.php" class="primary-link">Fazer uma reserva</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="overview-section" style="margin-top: 32px;">
        <h2 class="section-title">Histórico</h2>
        <p class="section-subtitle">Reservas concluídas ou canceladas</p>

        <?php if ($historicoReservas): ?>
            <div class="reservas-lista">
                <?php foreach ($historicoReservas as $row):
                    $estado = $row['estado'] === 'confirmada' ? 'expirada' : $row['estado'];
                    ?>
                    <article class="reserva-item reserva-historico">
                        <div class="reserva-item-header">
                            <div>
                                <h3><?= hReserva($row['nome_recurso']) ?></h3>
                                <p><?= hReserva($row['edificio']) ?> · Piso <?= hReserva((string) $row['piso']) ?></p>
                            </div>
                            <span class="estado-badge <?= hReserva(classeEstadoReserva($estado)) ?>">
                                <?= hReserva(labelEstadoReserva($estado)) ?>
                            </span>
                        </div>
                        <div class="reserva-item-detalhes">
                            <div>
                                <strong>Início</strong>
                                <span><?= hReserva(date('d/m/Y H:i', strtotime($row['data_inicio']))) ?></span>
                            </div>
                            <div>
                                <strong>Fim</strong>
                                <span><?= hReserva(date('d/m/Y H:i', strtotime($row['data_fim']))) ?></span>
                            </div>
                            <?php if (!empty($row['finalidade'])): ?>
                                <div>
                                    <strong>Finalidade</strong>
                                    <span><?= hReserva($row['finalidade']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i data-lucide="history"></i>
                <p>Sem histórico de reservas.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    document.querySelectorAll('.btn-cancelar[data-id]').forEach((btn) => {
        btn.addEventListener('click', function () {
            if (!confirm('Cancelar esta reserva?')) return;

            const body = new URLSearchParams({ id: this.dataset.id });
            appendCsrf(body);

            fetch('reserva-cancelar.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body
            })
            .then((response) => response.text())
            .then((data) => {
                if (data.trim() === 'ok') {
                    location.reload();
                    return;
                }
                alert(data || 'Erro ao cancelar reserva.');
            });
        });
    });

    document.querySelectorAll('.btn-alterar-horario').forEach((btn) => {
        btn.addEventListener('click', function () {
            const form = document.getElementById(this.dataset.target);
            if (form) {
                form.classList.toggle('hidden');
            }
        });
    });

    document.querySelectorAll('.form-alterar-horario').forEach((form) => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const dados = new FormData(this);
            const msgBox = this.querySelector('.msg-form');

            fetch('reserva-alterar-horario.php', {
                method: 'POST',
                body: dados
            })
            .then((response) => response.text())
            .then((data) => {
                msgBox.classList.remove('hidden', 'sucesso', 'erro');

                if (data.trim() === 'ok') {
                    msgBox.textContent = 'Horário alterado com sucesso!';
                    msgBox.classList.add('sucesso');
                    setTimeout(() => location.reload(), 600);
                    return;
                }

                msgBox.textContent = data || 'Erro ao alterar horário.';
                msgBox.classList.add('erro');
            });
        });
    });
</script>
</body>
</html>
