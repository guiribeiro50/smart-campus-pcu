<?php
session_start();
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_utilizadores');

if (!isset($_SESSION['novo_utilizador'])) {
    header('Location: utilizadores.php');
    exit;
}

$dados = $_SESSION['novo_utilizador'];
unset($_SESSION['novo_utilizador']);

function hCriado(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizador Criado | PCU - UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/utilizador-form.css">
    <link rel="stylesheet" href="assets/css/utilizadores.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content">
    <div class="page-header-flex">
        <div class="page-header-text">
            <h1>Utilizador Registado</h1>
            <p>O novo utilizador foi criado com sucesso</p>
        </div>
    </div>

    <section class="overview-section">
        <div class="modal-container" style="margin: 0 auto; max-width: 520px;">
            <div class="modal-header" style="padding: 28px 28px 0;">
                <h2 class="modal-title">Registo Concluído</h2>
                <p class="modal-subtitle">Guarde a password gerada — não será mostrada novamente</p>
            </div>
            <div class="modal-body" style="padding: 24px 28px;">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" value="<?= hCriado($dados['nome']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" value="<?= hCriado($dados['email']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Login Institucional</label>
                    <input type="text" value="<?= hCriado($dados['login']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Perfil</label>
                    <input type="text" value="<?= hCriado($dados['perfil']) ?>" readonly>
                </div>
                <div class="alerta-admin" style="display:flex;">
                    <i data-lucide="key"></i>
                    <div>
                        <strong>Password Gerada</strong>
                        <p style="font-family: monospace; font-size: 16px; margin-top: 6px; color: #1a2e4a;">
                            <?= hCriado($dados['password']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 0 28px 28px;">
                <a href="utilizadores.php" class="btn-modal-submit" style="text-decoration:none; text-align:center;">Voltar à Gestão de Utilizadores</a>
            </div>
        </div>
    </section>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
