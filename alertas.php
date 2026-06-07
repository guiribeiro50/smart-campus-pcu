<?php
session_start();
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/alertas-dados.php';

if (!function_exists('h')) {
    function h(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

$dados = dadosAlertas();
$resumo = $dados['resumo'];
$alertas = $dados['alertas'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas dos Sensores | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/alertas.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content alertas-page">

    <div class="page-header-flex">
        <div class="page-header-text">
            <h1>Alertas dos Sensores</h1>
            <p>Consulta e resolução de situações que requerem atenção</p>
        </div>
        <a class="primary-button" href="alertas-configurar-limites.php">Configurar limites</a>
    </div>

    <section class="summary-grid">
        <article class="stat-card">
            <h2>Alertas Pendentes</h2>
            <strong class="red"><?= (int) $resumo['pendentes'] ?></strong>
            <p>Requerem atenção</p>
        </article>
        <article class="stat-card">
            <h2>Críticos</h2>
            <strong class="red"><?= (int) $resumo['criticos'] ?></strong>
            <p>Ação imediata necessária</p>
        </article>
        <article class="stat-card">
            <h2>Avisos</h2>
            <strong class="orange"><?= (int) $resumo['avisos'] ?></strong>
            <p>Para monitorização</p>
        </article>
        <article class="stat-card">
            <h2>Resolvidos Hoje</h2>
            <strong class="green"><?= (int) ($resumo['resolvidos_hoje'] ?? 0) ?></strong>
            <p>Situações tratadas</p>
        </article>
    </section>

    <section class="overview-section">
        <h2>Filtros</h2>
        <div class="filters-grid" style="margin-top:16px">
            <label class="field">
                <span>Pesquisar</span>
                <span class="search"><i data-lucide="search"></i><input type="search" placeholder="Sensor ou recurso..." data-filter-search></span>
            </label>
            <label class="field">
                <span>Nível</span>
                <select data-filter-level>
                    <option value="">Todos</option>
                    <option value="critico">Crítico</option>
                    <option value="aviso">Aviso</option>
                </select>
            </label>
            <label class="field">
                <span>Estado</span>
                <select data-filter-status>
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="resolvido">Resolvido</option>
                </select>
            </label>
            <label class="field">
                <span>Edifício</span>
                <select data-filter-building>
                    <option value="">Todos</option>
                    <option value="edifício a">Edifício A</option>
                    <option value="edifício b">Edifício B</option>
                    <option value="edifício c">Edifício C</option>
                </select>
            </label>
        </div>
    </section>

    <section class="overview-section">
        <div class="section-heading-row">
            <div>
                <h2>Lista de Alertas</h2>
                <p>Os alertas críticos originam uma notificação por email para o staff</p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="alertas-table">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Sensor</th>
                        <th>Recurso</th>
                        <th>Valor</th>
                        <th>Nível</th>
                        <th>Data</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alertas): ?>
                        <?php foreach ($alertas as $alerta): ?>
                            <tr data-alert-row
                                data-search="<?= h(strtolower($alerta['titulo'] . ' ' . $alerta['codigo'] . ' ' . $alerta['localizacao'])) ?>"
                                data-level="<?= h($alerta['nivel']) ?>"
                                data-status="<?= h($alerta['estado']) ?>"
                                data-building="<?= h(strtolower($alerta['localizacao'])) ?>">
                                <td><strong><?= h($alerta['titulo']) ?></strong></td>
                                <td><?= h($alerta['codigo']) ?></td>
                                <td><?= h($alerta['localizacao']) ?></td>
                                <td><?= h($alerta['valor']) ?></td>
                                <td><span class="level-badge <?= h($alerta['nivel']) ?>"><?= h($alerta['nivel_label']) ?></span></td>
                                <td><?= h($alerta['data']) ?></td>
                                <td><span class="status-badge <?= h($alerta['estado']) ?>"><?= h($alerta['estado_label']) ?></span></td>
                                <td>
                                    <?php if ($alerta['estado'] === 'pendente'): ?>
                                        <button type="button" class="btn-resolve" data-resolve-alert="<?= (int) $alerta['id'] ?>">Resolver</button>
                                    <?php else: ?>
                                        <button type="button" class="btn-resolve" disabled>Resolvido</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">Sem alertas registados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/alertas.js"></script>
</body>
</html>
