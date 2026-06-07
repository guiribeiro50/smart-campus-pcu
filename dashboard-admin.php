<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPerfil(['administrador', 'funcionario_administrativo', 'staff']);

require_once __DIR__ . '/dashboard-dados.php';

if (!function_exists('h')) {
    function h(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

$dados = dadosDashboard();
$resumoAlertas = $dados['alertas_resumo'];
$temAlertasPendentes = (int) ($resumoAlertas['pendentes'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content dashboard-page">

    <div class="page-header-flex">
        <div class="page-header-text">
            <h1>Painel de Controlo</h1>
            <p>Monitorização e gestão do campus</p>
        </div>
        <a href="alertas.php" class="dashboard-alert-link" aria-label="Abrir alertas">
            <i data-lucide="bell"></i>
            <?php if ($temAlertasPendentes): ?><span class="alert-dot"></span><?php endif; ?>
        </a>
    </div>

    <section class="summary-grid" aria-label="Resumo do campus">
        <?php foreach ($dados['resumo'] as $card): ?>
            <article class="summary-card <?= h($card['classe']) ?>" data-summary-card="<?= h($card['chave']) ?>">
                <div class="summary-title">
                    <i data-lucide="<?= str_replace('icon-', '', h($card['icone'])) ?>"></i>
                    <h2><?= h($card['titulo']) ?></h2>
                </div>
                <strong class="summary-value" data-summary-value><?= h($card['valor']) ?></strong>
                <p class="summary-description <?= h($card['descricao_classe']) ?>" data-summary-description>
                    <?= h($card['descricao']) ?>
                </p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-grid">
        <article class="panel alerts-panel">
            <header class="panel-heading">
                <h2>Alertas de Sensores</h2>
                <p>Situações que requerem atenção</p>
            </header>
            <div class="alerts-list" data-alerts-list>
                <?php if ($dados['alertas']): ?>
                    <?php foreach ($dados['alertas'] as $alerta): ?>
                        <section class="alert-item <?= h($alerta['classe']) ?>">
                            <i data-lucide="alert-triangle"></i>
                            <div>
                                <h3><?= h($alerta['titulo']) ?></h3>
                                <p><?= h($alerta['detalhe']) ?></p>
                                <span><?= h($alerta['nivel']) ?></span>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-panel-message">Sem alertas pendentes neste momento.</p>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel consumption-panel">
            <header class="panel-heading">
                <h2>Consumo por Edifício</h2>
                <p>Consumo energético atual (kW)</p>
            </header>
            <div class="buildings-list" data-buildings-list>
                <?php foreach ($dados['consumos'] as $consumo): ?>
                    <section class="building-item">
                        <div class="building-heading">
                            <div>
                                <i data-lucide="zap"></i>
                                <h3><?= h($consumo['nome']) ?></h3>
                            </div>
                            <strong><?= h($consumo['valor']) ?></strong>
                        </div>
                        <span class="progress-track">
                            <span class="progress-value" style="width: <?= h((string) $consumo['percentagem']) ?>%"></span>
                        </span>
                    </section>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/dashboard.js?v=3"></script>
</body>
</html>
