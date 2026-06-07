<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPerfil(['docente', 'estudante']);

require_once __DIR__ . '/utilizador-dados.php';

$utilizadorSessao = obterUtilizador();
$dados = dadosDashboardUtilizador($utilizadorSessao);
$utilizador = $dados['utilizador'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= h($utilizador['role']) ?> | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content dashboard-page">

    <div class="page-header-text">
        <h1><?= h($utilizador['boas_vindas']) ?></h1>
        <p>Visão geral do campus em tempo real</p>
    </div>

    <section class="summary-grid" aria-label="Resumo pessoal">
        <?php foreach ($dados['resumo'] as $card): ?>
            <article class="summary-card <?= h($card['classe']) ?>">
                <div class="summary-title">
                    <i data-lucide="<?= h(str_replace('icon-', '', $card['icone'])) ?>"></i>
                    <h2><?= h($card['titulo']) ?></h2>
                </div>
                <strong class="summary-value"><?= h($card['valor']) ?></strong>
                <p class="summary-description"><?= h($card['descricao']) ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-grid">
        <article class="panel">
            <header class="panel-heading">
                <h2>Minhas Próximas Reservas</h2>
                <p>Salas e laboratórios agendados</p>
            </header>
            <div class="alerts-list">
                <?php if ($dados['reservas']): ?>
                    <?php foreach ($dados['reservas'] as $reserva): ?>
                        <section class="alert-item warning">
                            <i data-lucide="calendar"></i>
                            <div>
                                <h3><?= h($reserva['espaco']) ?></h3>
                                <p><?= h($reserva['periodo']) ?></p>
                                <span><?= h($reserva['estado']) ?></span>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-panel-message">Sem reservas futuras confirmadas. <a href="minhas-reservas.php">Ver reservas</a></p>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <header class="panel-heading">
                <h2>Ocupação do Campus</h2>
                <p>Distribuição por edifício</p>
            </header>
            <div class="buildings-list">
                <?php foreach ($dados['ocupacao'] as $item): ?>
                    <section class="building-item">
                        <div class="building-heading">
                            <div><i data-lucide="building-2"></i><h3><?= h($item['nome']) ?></h3></div>
                            <strong><?= h((string) $item['percentagem']) ?>%</strong>
                        </div>
                        <span class="progress-track"><span class="progress-value" style="width: <?= h((string) $item['percentagem']) ?>%"></span></span>
                    </section>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
