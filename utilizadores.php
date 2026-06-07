<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_utilizadores');

$utilizadorAtual = obterUtilizador();
$utilizadorAtualId = (int) ($utilizadorAtual['id'] ?? 0);
$perfilAtual = normalizarPerfilAuth((string) ($utilizadorAtual['perfil'] ?? ''));
$podeCriarAdmin = $perfilAtual === 'administrador';

function perfilLabel(string $perfilBd): string
{
    $mapa = [
        'administrador' => 'Administrador',
        'docente' => 'Docente',
        'funcionario_administrativo' => 'Staff',
        'estudante' => 'Estudante',
    ];
    return $mapa[$perfilBd] ?? ucfirst($perfilBd);
}

function perfilBadge(string $perfilBd): string
{
    $mapa = [
        'administrador' => 'badge-admin',
        'funcionario_administrativo' => 'badge-staff',
        'docente' => 'badge-docente',
        'estudante' => 'badge-student',
    ];
    return $mapa[$perfilBd] ?? 'badge-student';
}

function perfilFiltro(string $perfilBd): string
{
    return perfilLabel($perfilBd);
}

$total_users = 0;
$total_admins = 0;
$total_staff_docentes = 0;
$total_estudantes = 0;
$utilizadores = [];

try {
    $query_stats = "
        SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN perfil = 'administrador' THEN 1 ELSE 0 END) AS total_admins,
            SUM(CASE WHEN perfil IN ('funcionario_administrativo', 'docente') THEN 1 ELSE 0 END) AS total_staff_docentes,
            SUM(CASE WHEN perfil = 'estudante' THEN 1 ELSE 0 END) AS total_estudantes
        FROM utilizador
        WHERE ativo = 1
    ";
    $result_stats = $conexao->query($query_stats);
    if ($result_stats) {
        $row_stats = $result_stats->fetch(PDO::FETCH_ASSOC);
        $total_users = (int)$row_stats['total_users'];
        $total_admins = (int)$row_stats['total_admins'];
        $total_staff_docentes = (int)$row_stats['total_staff_docentes'];
        $total_estudantes = (int)$row_stats['total_estudantes'];
    }

    $query_users = "
        SELECT id, nome, email, login, perfil
        FROM utilizador
        WHERE ativo = 1
        ORDER BY nome
    ";
    $result_users = $conexao->query($query_users);
    if ($result_users) {
        $utilizadores = $result_users->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
}

$mensagem = '';
if (isset($_GET['sucesso'])) {
    $mensagem = 'Utilizador criado com sucesso.';
} elseif (isset($_GET['erro'])) {
    $erros = [
        'dados' => 'Preencha todos os campos obrigatórios.',
        'email' => 'Já existe um utilizador com esse email real ou login institucional.',
        'permissao' => 'Nao tem permissao para executar essa acao.',
        'geral' => 'Ocorreu um erro ao criar o utilizador.',
    ];
    $mensagem = $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores – PCU UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/utilizadores.css">
    <link rel="stylesheet" href="assets/css/utilizador-form.css">
</head>

<body>

    <?php include 'sidebar-admin.php'; ?>

    <main class="main-content">

        <div class="page-header-flex">
            <div class="page-header-text">
                <h1>Gestão de Utilizadores</h1>
                <p>Administração de contas e permissões RBAC</p>
            </div>
            <button onClick="alternarModal(true)" class="novo-utilizador-btn">
                <i data-lucide="user-plus"></i>
                Novo Utilizador
            </button>
        </div>

        <?php if ($mensagem !== ''): ?>
            <div class="alerta-pagina <?= isset($_GET['sucesso']) ? 'alerta-sucesso' : 'alerta-erro' ?>" id="alerta-pagina">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-title-top">Total de Utilizadores</div>
                <div class="stat-card-value-large blue-text"><?= $total_users ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title-top">Administradores</div>
                <div class="stat-card-value-large red-text"><?= $total_admins ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title-top">Staff & Docentes</div>
                <div class="stat-card-value-large light-blue-text"><?= $total_staff_docentes ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title-top">Estudantes</div>
                <div class="stat-card-value-large green-text"><?= $total_estudantes ?></div>
            </div>
        </div>

        <div class="overview-section">

            <div class="table-section-header">
                <h2>Lista de Utilizadores</h2>
                <p>Gerir utilizadores registados no sistema</p>
            </div>

            <div class="filters-row">
                <div class="search-box">
                    <label for="search">Pesquisar</label>
                    <div class="input-wrapper">
                        <i data-lucide="search"></i>
                        <input type="text" id="search" placeholder="Nome ou email..." oninput="filtrarTabela()">
                    </div>
                </div>
                <div class="filter-box">
                    <label for="filter-perfil">Filtrar por Perfil</label>
                    <div class="custom-select-wrapper" id="wrapper-filtro">
                        <input type="hidden" id="filter-perfil" value="">
                        <div class="custom-select-trigger" onclick="alternarDropdownFiltro(event)">
                            <span id="custom-filter-text">Todos os Perfis</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container">
                            <div class="custom-option selected" data-value="" onclick="selecionarOpcaoFiltro(this)">
                                <span>Todos os Perfis</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Administrador" onclick="selecionarOpcaoFiltro(this)">
                                <span>Administradores</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Staff" onclick="selecionarOpcaoFiltro(this)">
                                <span>Staff</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Docente" onclick="selecionarOpcaoFiltro(this)">
                                <span>Docentes</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Estudante" onclick="selecionarOpcaoFiltro(this)">
                                <span>Estudantes</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email Real</th>
                            <th>Login Institucional</th>
                            <th>Perfil</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        <?php foreach ($utilizadores as $user): ?>
                            <?php
                            $perfilUi = perfilLabel($user['perfil']);
                            $badge_class = perfilBadge($user['perfil']);
                            $isAdminAlvo = $user['perfil'] === 'administrador';
                            $isSelf = (int) $user['id'] === $utilizadorAtualId;
                            $adminBloqueado = $isAdminAlvo && $perfilAtual !== 'administrador';
                            $podeGerirAlvo = !$adminBloqueado;
                            $podeEliminarAlvo = $podeGerirAlvo && !$isSelf;
                            ?>
                            <tr data-perfil="<?= htmlspecialchars($perfilUi) ?>" data-nome="<?= htmlspecialchars(strtolower($user['nome'])) ?>" data-email="<?= htmlspecialchars(strtolower($user['email'] . ' ' . $user['login'])) ?>">
                                <td class="font-bold"><?= htmlspecialchars($user['nome']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($user['login']) ?></td>
                                <td>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($perfilUi) ?></span>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if ($podeGerirAlvo): ?>
                                            <button type="button" class="btn-action-outline" onclick="abrirPermissoes(<?= (int)$user['id'] ?>)">
                                                <i data-lucide="shield"></i> Gerir Permissões
                                            </button>
                                            <button type="button" class="btn-icon" onclick="abrirEditar(<?= (int)$user['id'] ?>)" title="Editar">
                                                <i data-lucide="edit-3"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-action-outline" disabled>
                                                <i data-lucide="shield"></i> Gerir Permissões
                                            </button>
                                            <button type="button" class="btn-icon btn-delete-disabled" disabled title="Sem permissao para editar administradores">
                                                <i data-lucide="edit-3"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($podeEliminarAlvo): ?>
                                            <button type="button" class="btn-icon text-danger" onclick="eliminarUtilizador(<?= (int)$user['id'] ?>, '<?= htmlspecialchars(addslashes($user['nome'])) ?>')" title="Eliminar">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-icon btn-delete-disabled" disabled title="<?= $isSelf ? 'Nao pode eliminar a propria conta' : 'Sem permissao para eliminar este utilizador' ?>">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <?php include 'utilizador-form.php'; ?>
        <?php include 'utilizador-editar-form.php'; ?>
        <?php include 'utilizador-permissoes-form.php'; ?>
        <?php include 'utilizador-eliminar-form.php'; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/utilizador-form.js"></script>
    <script src="assets/js/modal-utilizador.js"></script>
    <script>
        lucide.createIcons();

        (function () {
            const alerta = document.getElementById("alerta-pagina");
            if (!alerta) return;

            if (window.location.search) {
                const url = new URL(window.location.href);
                url.searchParams.delete("sucesso");
                url.searchParams.delete("erro");
                window.history.replaceState({}, "", url.pathname + (url.search || ""));
            }

            setTimeout(() => {
                alerta.style.transition = "opacity 0.3s ease";
                alerta.style.opacity = "0";
                setTimeout(() => alerta.remove(), 300);
            }, 5000);
        })();
    </script>
</body>

</html>
