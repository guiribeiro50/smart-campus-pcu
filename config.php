<?php
require_once __DIR__ . '/helpers/env.php';
carregarEnv(__DIR__ . '/.env');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers/csrf.php';

if (PHP_SAPI !== 'cli') {
    $scriptAtual = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $publicos = ['login.php', 'mfa.php', 'logout.php'];

    if (!in_array($scriptAtual, $publicos, true) && empty($_SESSION['utilizador_id'])) {
        header('Location: login.php');
        exit;
    }
}

$host = env('DB_HOST', 'localhost');
$utilizador = env('DB_USER', 'root');
$password = env('DB_PASS', '');
$baseDados = env('DB_NAME', 'pcu');
$porta = (int) env('DB_PORT', '3306');

try {
    $conexao = new PDO("mysql:host=$host;port=$porta;dbname=$baseDados;charset=utf8", $utilizador, $password);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Erro de ligação à base de dados: ' . $e->getMessage());
    die('Erro de ligação à base de dados.');
}

$conn = new mysqli($host, $utilizador, $password, $baseDados, $porta);
if ($conn->connect_error) {
    error_log('Erro de ligação à base de dados: ' . $conn->connect_error);
    die('Erro de ligação à base de dados.');
}
