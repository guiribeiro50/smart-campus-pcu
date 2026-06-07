<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

$localidades = $conexao->query("SELECT id, nome FROM localidade ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$totalEstacionamento = (int) $conexao->query("SELECT COUNT(*) FROM lugar_estacionamento")->fetchColumn();
$totalBicicletas = (int) $conexao->query("SELECT COUNT(*) FROM bicicleta WHERE ativo = 1")->fetchColumn();
$totalTrotinetes = (int) $conexao->query("SELECT COUNT(*) FROM trotinete WHERE ativo = 1")->fetchColumn();
$totalZonas = count($localidades);

$estacionamentos = $conexao->query("
    SELECT le.id, le.codigo, le.localidade_id, l.nome AS localidade_nome
    FROM lugar_estacionamento le
    LEFT JOIN localidade l ON l.id = le.localidade_id
    ORDER BY le.codigo
")->fetchAll(PDO::FETCH_ASSOC);

$bicicletas = $conexao->query("
    SELECT b.id, b.codigo, b.localidade_id, b.estado, l.nome AS localidade_nome
    FROM bicicleta b
    LEFT JOIN localidade l ON l.id = b.localidade_id
    WHERE b.ativo = 1
    ORDER BY b.codigo
")->fetchAll(PDO::FETCH_ASSOC);

$trotinetes = $conexao->query("
    SELECT t.id, t.codigo, t.localidade_id, t.estado, l.nome AS localidade_nome
    FROM trotinete t
    LEFT JOIN localidade l ON l.id = t.localidade_id
    WHERE t.ativo = 1
    ORDER BY t.codigo
")->fetchAll(PDO::FETCH_ASSOC);

function badgeEstadoMobilidade(string $estado): array
{
    $mapa = [
        'disponivel' => ['badge-disponivel', 'Disponível'],
        'em_uso' => ['badge-em-uso', 'Em uso'],
        'manutencao' => ['badge-manutencao', 'Manutenção'],
    ];
    return $mapa[$estado] ?? ['badge-disponivel', ucfirst($estado)];
}

$mensagem = '';
if (isset($_GET['sucesso'])) {
    $msgs = [
        'criar' => 'Recurso criado com sucesso.',
        'editar' => 'Recurso atualizado com sucesso.',
        'eliminar' => 'Recurso eliminado com sucesso.',
    ];
    $mensagem = $msgs[$_GET['sucesso']] ?? 'Operação concluída com sucesso.';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Mobilidade – PCU UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/gestao-mobilidade.css">
    <link rel="stylesheet" href="assets/css/utilizador-form.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content">

    <div class="page-header-flex">
        <div class="page-header-text">
            <h1>Gestão de Mobilidade</h1>
            <p>Administração de estacionamento, bicicletas e trotinetes</p>
        </div>
        <button type="button" class="novo-recurso-btn" onclick="abrirModalNovo()">
            <i data-lucide="plus"></i>
            Novo Recurso
        </button>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="alerta-pagina alerta-sucesso" id="alerta-pagina"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-title-top">Estacionamento</div>
            <div class="stat-card-value-large blue-text"><?= $totalEstacionamento ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-title-top">Bicicletas</div>
            <div class="stat-card-value-large green-text"><?= $totalBicicletas ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-title-top">Trotinetes</div>
            <div class="stat-card-value-large purple-text"><?= $totalTrotinetes ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-title-top">Zonas</div>
            <div class="stat-card-value-large light-blue-text"><?= $totalZonas ?></div>
        </div>
    </div>

    <div class="tabs-container">
        <button type="button" class="tab-btn active" onclick="mudarTab('estacionamento', this)">
            <i data-lucide="car"></i> Estacionamento
        </button>
        <button type="button" class="tab-btn" onclick="mudarTab('bicicleta', this)">
            <i data-lucide="bike"></i> Bicicletas
        </button>
        <button type="button" class="tab-btn" onclick="mudarTab('trotinete', this)">
            <i data-lucide="navigation"></i> Trotinetes
        </button>
    </div>

    <div class="overview-section tab-painel active" id="painel-estacionamento">
        <div class="table-section-header">
            <h2>Lugares de Estacionamento</h2>
            <p>Gerir lugares registados no campus</p>
        </div>
        <div class="filters-row">
            <div class="search-box">
                <label for="search-estacionamento">Pesquisar</label>
                <div class="input-wrapper">
                    <i data-lucide="search"></i>
                    <input type="text" id="search-estacionamento" placeholder="Código ou zona..." oninput="filtrarTabela('estacionamento')">
                </div>
            </div>
            <div class="filter-box">
                <label for="filter-estacionamento">Zona</label>
                <select id="filter-estacionamento" onchange="filtrarTabela('estacionamento')">
                    <option value="">Todas</option>
                    <?php foreach ($localidades as $loc): ?>
                        <option value="<?= (int) $loc['id'] ?>"><?= htmlspecialchars($loc['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-container">
            <table class="mobilidade-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Localidade</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-estacionamento">
                    <?php foreach ($estacionamentos as $item): ?>
                        <tr data-codigo="<?= htmlspecialchars(strtolower($item['codigo'])) ?>"
                            data-localidade="<?= htmlspecialchars(strtolower($item['localidade_nome'] ?? '')) ?>"
                            data-zona="<?= (int) $item['localidade_id'] ?>">
                            <td class="font-bold"><?= htmlspecialchars($item['codigo']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($item['localidade_nome'] ?? '—') ?></td>
                            <td>
                                <div class="actions-cell">
                                    <button type="button" class="btn-icon" onclick="abrirEditar('estacionamento', <?= (int) $item['id'] ?>)" title="Editar">
                                        <i data-lucide="edit-3"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-danger" onclick="eliminarRecurso('estacionamento', <?= (int) $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['codigo'])) ?>')" title="Eliminar">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overview-section tab-painel" id="painel-bicicleta">
        <div class="table-section-header">
            <h2>Bicicletas</h2>
            <p>Frota de bicicletas partilhadas</p>
        </div>
        <div class="filters-row">
            <div class="search-box">
                <label for="search-bicicleta">Pesquisar</label>
                <div class="input-wrapper">
                    <i data-lucide="search"></i>
                    <input type="text" id="search-bicicleta" placeholder="Código ou zona..." oninput="filtrarTabela('bicicleta')">
                </div>
            </div>
            <div class="filter-box">
                <label for="filter-bicicleta">Zona</label>
                <select id="filter-bicicleta" onchange="filtrarTabela('bicicleta')">
                    <option value="">Todas</option>
                    <?php foreach ($localidades as $loc): ?>
                        <option value="<?= (int) $loc['id'] ?>"><?= htmlspecialchars($loc['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-container">
            <table class="mobilidade-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Localidade</th>
                        <th>Estado</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-bicicleta">
                    <?php foreach ($bicicletas as $item): ?>
                        <?php [$badgeClass, $badgeLabel] = badgeEstadoMobilidade($item['estado']); ?>
                        <tr data-codigo="<?= htmlspecialchars(strtolower($item['codigo'])) ?>"
                            data-localidade="<?= htmlspecialchars(strtolower($item['localidade_nome'] ?? '')) ?>"
                            data-zona="<?= (int) $item['localidade_id'] ?>">
                            <td class="font-bold"><?= htmlspecialchars($item['codigo']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($item['localidade_nome'] ?? '—') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
                            <td>
                                <div class="actions-cell">
                                    <button type="button" class="btn-icon" onclick="abrirEditar('bicicleta', <?= (int) $item['id'] ?>)" title="Editar">
                                        <i data-lucide="edit-3"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-danger" onclick="eliminarRecurso('bicicleta', <?= (int) $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['codigo'])) ?>')" title="Eliminar">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overview-section tab-painel" id="painel-trotinete">
        <div class="table-section-header">
            <h2>Trotinetes</h2>
            <p>Frota de trotinetes partilhadas</p>
        </div>
        <div class="filters-row">
            <div class="search-box">
                <label for="search-trotinete">Pesquisar</label>
                <div class="input-wrapper">
                    <i data-lucide="search"></i>
                    <input type="text" id="search-trotinete" placeholder="Código ou zona..." oninput="filtrarTabela('trotinete')">
                </div>
            </div>
            <div class="filter-box">
                <label for="filter-trotinete">Zona</label>
                <select id="filter-trotinete" onchange="filtrarTabela('trotinete')">
                    <option value="">Todas</option>
                    <?php foreach ($localidades as $loc): ?>
                        <option value="<?= (int) $loc['id'] ?>"><?= htmlspecialchars($loc['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-container">
            <table class="mobilidade-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Localidade</th>
                        <th>Estado</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-trotinete">
                    <?php foreach ($trotinetes as $item): ?>
                        <?php [$badgeClass, $badgeLabel] = badgeEstadoMobilidade($item['estado']); ?>
                        <tr data-codigo="<?= htmlspecialchars(strtolower($item['codigo'])) ?>"
                            data-localidade="<?= htmlspecialchars(strtolower($item['localidade_nome'] ?? '')) ?>"
                            data-zona="<?= (int) $item['localidade_id'] ?>">
                            <td class="font-bold"><?= htmlspecialchars($item['codigo']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($item['localidade_nome'] ?? '—') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
                            <td>
                                <div class="actions-cell">
                                    <button type="button" class="btn-icon" onclick="abrirEditar('trotinete', <?= (int) $item['id'] ?>)" title="Editar">
                                        <i data-lucide="edit-3"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-danger" onclick="eliminarRecurso('trotinete', <?= (int) $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['codigo'])) ?>')" title="Eliminar">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'gestao-mobilidade-form.php'; ?>
    <?php include 'gestao-mobilidade-editar-form.php'; ?>
    <?php include 'gestao-mobilidade-eliminar-form.php'; ?>

</main>

<script>
    const LOCALIDADES = <?= json_encode($localidades, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/gestao-mobilidade.js"></script>
</body>
</html>
