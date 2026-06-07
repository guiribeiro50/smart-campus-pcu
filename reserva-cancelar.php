<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarAutenticacao();

header('Content-Type: text/plain; charset=utf-8');
date_default_timezone_set('Europe/Lisbon');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido.';
    exit;
}

validarCsrf();

if (!isset($_SESSION['utilizador_id'])) {
    http_response_code(401);
    echo 'Sessão expirada.';
    exit;
}

$utilizadorId = (int) $_SESSION['utilizador_id'];
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo 'Reserva inválida.';
    exit;
}

$conn->query(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

$stmt = $conn->prepare(
    "UPDATE reserva
     SET estado = 'cancelada'
     WHERE id = ?
       AND utilizador_id = ?
       AND estado = 'confirmada'
       AND data_fim > NOW()"
);
$stmt->bind_param('ii', $id, $utilizadorId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    echo 'Reserva não encontrada ou já concluída.';
    exit;
}

echo 'ok';
