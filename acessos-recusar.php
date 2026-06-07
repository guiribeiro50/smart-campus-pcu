<?php
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['msg' => 'Método não permitido.', 'resultado' => 'negado'], JSON_UNESCAPED_UNICODE);
    exit;
}

validarCsrf();

$reservaId = (int) ($_POST['reserva_id'] ?? 0);

$conn->query(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

if ($reservaId <= 0) {
    echo json_encode([
        'msg' => 'Seleciona uma reserva.',
        'resultado' => 'negado',
        'nome' => 'Reserva inválida',
        'tipo' => '',
        'espaco_id' => '',
        'hora' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare(
    "SELECT r.*, u.nome
     FROM reserva r
     INNER JOIN utilizador u ON u.id = r.utilizador_id
     WHERE r.id = ?
       AND r.estado = 'confirmada'
       AND r.data_fim > NOW()
     LIMIT 1"
);
$stmt->bind_param('i', $reservaId);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();

if (!$reserva) {
    echo json_encode([
        'msg' => 'Reserva não encontrada ou já encerrada.',
        'resultado' => 'negado',
        'nome' => 'Reserva inválida',
        'tipo' => '',
        'espaco_id' => '',
        'hora' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtCancel = $conn->prepare(
    "UPDATE reserva SET estado = 'cancelada' WHERE id = ? AND estado = 'confirmada' AND data_fim > NOW()"
);
$stmtCancel->bind_param('i', $reservaId);
$stmtCancel->execute();

$motivo = 'Reserva recusada pelo staff no controlo de acessos.';
$metodo = 'manual';

$stmtInsert = $conn->prepare(
    "INSERT INTO acesso
        (utilizador_id, tipo_recurso, recurso_id, metodo, resultado, motivo_negacao)
     VALUES
        (?, ?, ?, ?, 'negado', ?)"
);
$stmtInsert->bind_param(
    'isiss',
    $reserva['utilizador_id'],
    $reserva['tipo_recurso'],
    $reserva['recurso_id'],
    $metodo,
    $motivo
);
$stmtInsert->execute();

echo json_encode([
    'msg' => 'Reserva recusada e acesso negado.',
    'resultado' => 'negado',
    'nome' => $reserva['nome'],
    'tipo' => $reserva['tipo_recurso'],
    'espaco_id' => (int) $reserva['recurso_id'],
    'reserva_id' => $reservaId,
    'hora' => date('H:i:s'),
], JSON_UNESCAPED_UNICODE);
