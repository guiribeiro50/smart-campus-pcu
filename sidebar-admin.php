<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarAutenticacao();
}

require_once __DIR__ . '/permissoes-utilizador.php';

$current_page = basename($_SERVER['PHP_SELF'] ?? '', '.php');
$session = $_SESSION ?? [];
$perfil = strtolower((string) ($session['perfil'] ?? 'administrador'));
$perfil = $perfil === 'staff' ? 'funcionario_administrativo' : $perfil;
$nome = (string) ($session['nome'] ?? 'Administrador Sistema');
$email = (string) ($session['email'] ?? 'admin@upt.pt');
$avatar = mb_strtoupper(mb_substr($nome !== '' ? $nome : 'A', 0, 1, 'UTF-8'), 'UTF-8');
$isAdmin = $perfil === 'administrador';
$permissoes = obterPermissoesUtilizador((int) ($session['utilizador_id'] ?? 0), $perfil);
$temPermissao = fn(string $nomePermissao): bool => in_array($nomePermissao, $permissoes, true);

$roleLabel = match ($perfil) {
    'administrador' => 'Administrador',
    'funcionario_administrativo' => 'Staff',
    'docente' => 'Docente',
    'estudante' => 'Estudante',
    default => 'Utilizador',
};

$mostrarMonitorizacao = $temPermissao('acesso_iot');
$mostrarReservas = $temPermissao('reservar_salas') || $temPermissao('reservar_laboratorios') || $temPermissao('reservar_equipamentos');
$mostrarMobilidade = $temPermissao('acesso_mobilidade');
$mostrarSustentabilidade = $temPermissao('acesso_iot');
$mostrarAcessos = $temPermissao('gestao_espacos');
$mostrarRegistarEspaco = $temPermissao('gestao_espacos');
$mostrarUtilizadores = $temPermissao('gestao_utilizadores');
$mostrarSensores = $temPermissao('gestao_espacos');
$mostrarGestaoMobilidade = $temPermissao('gestao_espacos');
$mostrarTerminalLss = $temPermissao('acesso_lss');
$mostrarAdministracao = $mostrarAcessos || $mostrarRegistarEspaco || $mostrarUtilizadores || $mostrarSensores || $mostrarGestaoMobilidade || $mostrarTerminalLss;

function sidebarActive(array $pages, string $current_page): string
{
    return in_array($current_page, $pages, true) ? 'active' : '';
}
?>
<link rel="stylesheet" href="assets/css/sidebar-admin.css">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidbar-brande-icon"></div>
        <div class="sidebar-brand-text">
            <h1>PCU - UPT</h1>
            <p>Campus Inteligente</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="sidebar-group-label">Menu Principal</span>

        <a href="dashboard.php" class="sidebar-item <?= sidebarActive(['dashboard', 'dashboard-admin', 'dashboard-utilizador', 'dashboard-demo'], $current_page) ?>">
            <i data-lucide="layout-dashboard"></i>
            Dashboard
        </a>
        <?php if ($mostrarMonitorizacao): ?>
            <a href="monitorizacao.php" class="sidebar-item <?= sidebarActive(['monitorizacao'], $current_page) ?>">
                <i data-lucide="wifi"></i>
                Monitorização IoT
            </a>
        <?php endif; ?>
        <?php if ($mostrarReservas): ?>
            <a href="reservas.php" class="sidebar-item <?= sidebarActive(['reservas', 'minhas-reservas'], $current_page) ?>">
                <i data-lucide="calendar"></i>
                Reservas
            </a>
        <?php endif; ?>
        <?php if ($mostrarMobilidade): ?>
            <a href="mobilidade.php" class="sidebar-item <?= sidebarActive(['mobilidade'], $current_page) ?>">
                <i data-lucide="map-pin"></i>
                Mobilidade
            </a>
        <?php endif; ?>
        <?php if ($mostrarSustentabilidade): ?>
            <a href="sustentabilidade.php" class="sidebar-item <?= sidebarActive(['sustentabilidade'], $current_page) ?>">
                <i data-lucide="leaf"></i>
                Sustentabilidade
            </a>
        <?php endif; ?>

        <?php if ($mostrarAdministracao): ?>
            <span class="sidebar-group-label">Administração</span>
            <?php if ($mostrarAcessos): ?>
                <a href="acessos.php" class="sidebar-item <?= sidebarActive(['acessos'], $current_page) ?>">
                    <i data-lucide="shield"></i>
                    Controlo de Acessos
                </a>
            <?php endif; ?>
            <?php if ($mostrarRegistarEspaco): ?>
                <a href="registar-espaco.php" class="sidebar-item <?= sidebarActive(['registar-espaco'], $current_page) ?>">
                    <i data-lucide="microscope"></i>
                    Gestão Recursos
                </a>
            <?php endif; ?>
            <?php if ($mostrarUtilizadores): ?>
                <a href="utilizadores.php" class="sidebar-item <?= sidebarActive(['utilizadores', 'utilizador-criado'], $current_page) ?>">
                    <i data-lucide="users"></i>
                    Gestão Utilizadores
                </a>
            <?php endif; ?>
            <?php if ($mostrarSensores): ?>
                <a href="sensores-listar.php" class="sidebar-item <?= sidebarActive(['sensores-listar', 'sensores-criar', 'sensores-editar', 'sensores-gerir', 'sensores-remover', 'alertas', 'alertas-configurar-limites'], $current_page) ?>">
                    <i data-lucide="cpu"></i>
                    Gestão Sensores
                </a>
            <?php endif; ?>
            <?php if ($mostrarGestaoMobilidade): ?>
                <a href="gestao-mobilidade.php" class="sidebar-item <?= sidebarActive(['gestao-mobilidade'], $current_page) ?>">
                    <i data-lucide="car"></i>
                    Gestão Mobilidade
                </a>
            <?php endif; ?>
            <?php if ($mostrarTerminalLss): ?>
                <a href="lss-terminal.php" class="sidebar-item <?= sidebarActive(['lss-terminal'], $current_page) ?>">
                    <i data-lucide="terminal"></i>
                    Terminal LSS
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-profile">
        <div class="sidebar-avatar"><?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sidebar-profile-email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sidebar-badge"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></div>
        <a href="logout.php" class="sidebar-logout <?= sidebarActive(['logout'], $current_page) ?>">
            <i data-lucide="log-out"></i>
            Sair
        </a>
    </div>
</aside>

<script>
    window.CSRF_TOKEN = <?= json_encode(csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="assets/js/csrf.js"></script>
<script>
    if (window.lucide) {
        window.lucide.createIcons();
    }
</script>