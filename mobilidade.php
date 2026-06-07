<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('acesso_mobilidade');

$utilizador_id = (int)($_SESSION['utilizador_id'] ?? 1);

$conexao->exec("
    UPDATE reserva_bicicleta
    SET estado = 'concluida'
    WHERE estado = 'ativa' AND fim IS NOT NULL AND fim <= NOW()
");

$conexao->exec("
    UPDATE reserva_trotinete
    SET estado = 'concluida'
    WHERE estado = 'ativa' AND fim IS NOT NULL AND fim <= NOW()
");

$conexao->exec("
    UPDATE bicicleta b
    INNER JOIN reserva_bicicleta rb ON rb.bicicleta_id = b.id AND rb.estado = 'ativa'
    SET b.estado = 'em_uso'
    WHERE b.estado = 'disponivel' AND b.ativo = 1
");

$conexao->exec("
    UPDATE trotinete t
    INNER JOIN reserva_trotinete rt ON rt.trotinete_id = t.id AND rt.estado = 'ativa'
    SET t.estado = 'em_uso'
    WHERE t.estado = 'disponivel' AND t.ativo = 1
");

$conexao->exec("
    UPDATE bicicleta
    SET estado = 'disponivel'
    WHERE estado = 'em_uso'
    AND id NOT IN (
        SELECT bicicleta_id FROM reserva_bicicleta WHERE estado = 'ativa'
    )
");

$conexao->exec("
    UPDATE trotinete
    SET estado = 'disponivel'
    WHERE estado = 'em_uso'
    AND id NOT IN (
        SELECT trotinete_id FROM reserva_trotinete WHERE estado = 'ativa'
    )
");

$query_bicicletas_total = "SELECT COUNT(*) AS total FROM bicicleta WHERE estado = 'disponivel' AND ativo = 1";
$result_bic_total = $conexao->query($query_bicicletas_total);
$total_bicicletas_disp = (int)($result_bic_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$query_trotinetes_total = "SELECT COUNT(*) AS total FROM trotinete WHERE estado = 'disponivel' AND ativo = 1";
$result_trot_total = $conexao->query($query_trotinetes_total);
$total_trotinetes_disp = (int)($result_trot_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$query_lugares = "
    SELECT l.id, l.nome,
           COUNT(le.id) AS total_lugares
    FROM localidade l
    LEFT JOIN lugar_estacionamento le ON le.localidade_id = l.id
    GROUP BY l.id, l.nome
";
$result_lugares = $conexao->query($query_lugares);
$lugares_por_zona = $result_lugares->fetchAll(PDO::FETCH_ASSOC);

$total_lugares_global = 0;
foreach ($lugares_por_zona as $z) {
    $total_lugares_global += (int)$z['total_lugares'];
}

$query_lugares_detalhe = "
    SELECT le.id, le.codigo, l.id AS localidade_id, l.nome AS localidade_nome
    FROM lugar_estacionamento le
    JOIN localidade l ON l.id = le.localidade_id
    ORDER BY l.nome, le.codigo
";
$result_lugares_detalhe = $conexao->query($query_lugares_detalhe);
$lugares_detalhe = $result_lugares_detalhe->fetchAll(PDO::FETCH_ASSOC);

$lugares_por_zona_map = [];
foreach ($lugares_detalhe as $lugar) {
    $lugares_por_zona_map[$lugar['localidade_id']][] = [
        'codigo' => $lugar['codigo'],
    ];
}

foreach ($lugares_por_zona as &$zona) {
    $zona['lugares'] = $lugares_por_zona_map[$zona['id']] ?? [];
}
unset($zona);

$query_bicicletas_zona = "
    SELECT l.id AS localidade_id, l.nome AS localidade_nome,
           COUNT(b.id) AS total,
           SUM(CASE WHEN b.estado = 'disponivel' THEN 1 ELSE 0 END) AS disponiveis,
           SUM(CASE WHEN b.estado = 'em_uso' THEN 1 ELSE 0 END) AS em_uso
    FROM localidade l
    LEFT JOIN bicicleta b ON b.localidade_id = l.id AND b.ativo = 1
    GROUP BY l.id, l.nome
    ORDER BY l.nome
";
$result_bic_zona = $conexao->query($query_bicicletas_zona);
$bicicletas_por_zona = $result_bic_zona->fetchAll(PDO::FETCH_ASSOC);

$query_trotinetes_zona = "
    SELECT l.id AS localidade_id, l.nome AS localidade_nome,
           COUNT(t.id) AS total,
           SUM(CASE WHEN t.estado = 'disponivel' THEN 1 ELSE 0 END) AS disponiveis,
           SUM(CASE WHEN t.estado = 'em_uso' THEN 1 ELSE 0 END) AS em_uso
    FROM localidade l
    LEFT JOIN trotinete t ON t.localidade_id = l.id AND t.ativo = 1
    GROUP BY l.id, l.nome
    ORDER BY l.nome
";
$result_trot_zona = $conexao->query($query_trotinetes_zona);
$trotinetes_por_zona = $result_trot_zona->fetchAll(PDO::FETCH_ASSOC);

$stmt_ativa = $conexao->prepare("
    SELECT
        (SELECT COUNT(*) FROM reserva_bicicleta WHERE utilizador_id = ? AND estado = 'ativa')
      + (SELECT COUNT(*) FROM reserva_trotinete WHERE utilizador_id = ? AND estado = 'ativa')
");
$stmt_ativa->execute([$utilizador_id, $utilizador_id]);
$tem_reserva_ativa = (int)$stmt_ativa->fetchColumn() > 0 ? 'true' : 'false';

$query_localidades = "SELECT id, nome FROM localidade ORDER BY nome";
$result_localidades = $conexao->query($query_localidades);
$localidades = $result_localidades->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobilidade – PCU UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/mobilidade.css">
    <link rel="stylesheet" href="assets/css/reserva-form.css">
    <link rel="stylesheet" href="assets/css/reserva-indisponivel.css">
    <link rel="stylesheet" href="assets/css/reserva-sucesso.css">
    <link rel="stylesheet" href="assets/css/estacionamento-disponivel.css">
</head>

<body>

    <?php include 'sidebar-admin.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <h1>Mapa de Mobilidade</h1>
            <p>Estacionamento e pontos de partilha em tempo real</p>
        </div>

        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-card-header">
                    <i data-lucide="car"></i>
                    <span>Lugares de Estacionamento</span>
                </div>
                <div class="stat-card-value"><?= $total_lugares_global ?></div>
                <div class="stat-card-sub">Total de lugares registados</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <i data-lucide="bike" class="stat-card-icon blue"></i>
                    <span>Bicicletas</span>
                </div>
                <div class="stat-card-value blue"><?= $total_bicicletas_disp ?></div>
                <div class="stat-card-sub">Disponíveis para partilha</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <i data-lucide="navigation" class="stat-card-icon purple"></i>
                    <span>Trotinetes</span>
                </div>
                <div class="stat-card-value purple"><?= $total_trotinetes_disp ?></div>
                <div class="stat-card-sub">Disponíveis para partilha</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <i data-lucide="map-pin" class="stat-card-icon green"></i>
                    <span>Zonas</span>
                </div>
                <div class="stat-card-value green"><?= count($localidades) ?></div>
                <div class="stat-card-sub">Localidades no campus</div>
            </div>

        </div>

        <div class="overview-section">

            <div class="overview-header">
                <i data-lucide="map"></i>
                <h2>Vista Geral do Campus UPT</h2>
            </div>

            <div class="overview-grid">

                <div class="overview-card">
                    <div class="overview-card-title">
                        <i data-lucide="car"></i>
                        <span>Estacionamento por Zona</span>
                    </div>

                    <?php foreach ($lugares_por_zona as $zona): ?>
                        <?php
                        $total = (int)$zona['total_lugares'];
                        ?>
                        <div class="parking-item">
                            <div class="parking-item-header">
                                <span><?= htmlspecialchars($zona['nome']) ?></span>
                                <span><?= $total ?> lugar<?= $total !== 1 ? 'es' : '' ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill green" style="width: <?= $total > 0 ? 100 : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="overview-card">
                    <div class="overview-card-title">
                        <i data-lucide="bike" class="card-icon-bike"></i>
                        <span>Bicicletas por Zona</span>
                    </div>

                    <?php foreach ($bicicletas_por_zona as $zona): ?>
                        <?php
                        $total = (int)$zona['total'];
                        $em_uso = (int)$zona['em_uso'];
                        $percentagem = $total > 0 ? ($em_uso / $total) * 100 : 0;
                        if ($percentagem <= 40) $cor = "green";
                        elseif ($percentagem <= 60) $cor = "yellow";
                        elseif ($percentagem <= 80) $cor = "orange";
                        else $cor = "red";
                        ?>
                        <div class="parking-item">
                            <div class="parking-item-header">
                                <span>
                                    <i data-lucide="bike" class="card-icon-bike"></i>
                                    <?= htmlspecialchars($zona['localidade_nome']) ?>
                                </span>
                                <span><?= $zona['disponiveis'] ?>/<?= $total ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $cor ?>" style="width: <?= $percentagem ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="overview-card">
                    <div class="overview-card-title">
                        <i data-lucide="navigation" class="card-icon-scooter"></i>
                        <span>Trotinetes por Zona</span>
                    </div>

                    <?php foreach ($trotinetes_por_zona as $zona): ?>
                        <?php
                        $total = (int)$zona['total'];
                        $em_uso = (int)$zona['em_uso'];
                        $percentagem = $total > 0 ? ($em_uso / $total) * 100 : 0;
                        if ($percentagem <= 40) $cor = "green";
                        elseif ($percentagem <= 60) $cor = "yellow";
                        elseif ($percentagem <= 80) $cor = "orange";
                        else $cor = "red";
                        ?>
                        <div class="parking-item">
                            <div class="parking-item-header">
                                <span>
                                    <i data-lucide="navigation" class="card-icon-scooter"></i>
                                    <?= htmlspecialchars($zona['localidade_nome']) ?>
                                </span>
                                <span><?= $zona['disponiveis'] ?>/<?= $total ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $cor ?>" style="width: <?= $percentagem ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

            </div>

            <div class="reserve-grid">

                <div class="reserve-card" onclick="abrirModalEstacionamento(<?= htmlspecialchars(json_encode($lugares_por_zona)) ?>)">
                    <i data-lucide="car"></i>
                    <span class="reserve-title">Encontrar Estacionamento</span>
                    <span class="reserve-sub"><?= $total_lugares_global ?> lugares registados</span>
                </div>

                <div class="reserve-card" onclick="abrirModal('bicicleta', <?= $total_bicicletas_disp ?>, <?= htmlspecialchars(json_encode($bicicletas_por_zona)) ?>, <?= $tem_reserva_ativa ?>)">
                    <i data-lucide="bike" class="card-icon-bike"></i>
                    <span class="reserve-title">Reservar Bicicleta</span>
                    <span class="reserve-sub"><?= $total_bicicletas_disp ?> bicicletas disponíveis</span>
                </div>

                <div class="reserve-card" onclick="abrirModal('trotinete', <?= $total_trotinetes_disp ?>, <?= htmlspecialchars(json_encode($trotinetes_por_zona)) ?>, <?= $tem_reserva_ativa ?>)">
                    <i data-lucide="navigation" class="card-icon-scooter"></i>
                    <span class="reserve-title">Reservar Trotinete</span>
                    <span class="reserve-sub"><?= $total_trotinetes_disp ?> trotinetes disponíveis</span>
                </div>

            </div>

        </div>

        <div class="modal-overlay" id="modal" onclick="fecharModal(event)">
            <div class="modal">
            </div>
        </div>

    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/mobilidade.js"></script>
</body>

</html>
