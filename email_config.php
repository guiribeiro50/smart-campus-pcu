<?php
require_once __DIR__ . '/helpers/env.php';
carregarEnv(__DIR__ . '/.env');

if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $scriptAtual = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (!in_array($scriptAtual, ['login.php', 'mfa.php'], true) && empty($_SESSION['utilizador_id'])) {
        http_response_code(403);
        exit;
    }
}

define('SMTP_HOST', env('SMTP_HOST', 'localhost'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM', env('SMTP_FROM', 'noreply@example.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'PCU - Campus Universitário'));
