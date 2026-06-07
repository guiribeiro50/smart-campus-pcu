<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao(['reservar_salas', 'reservar_laboratorios', 'reservar_equipamentos']);

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
$dataInicio = (string) ($_POST['data_inicio'] ?? '');
$dataFim = (string) ($_POST['data_fim'] ?? '');

if ($id <= 0 || $dataInicio === '' || $dataFim === '') {
    echo 'Preencha todos os campos obrigatórios.';
    exit;
}

$inicioTimestamp = strtotime(str_replace('T', ' ', $dataInicio));
$fimTimestamp = strtotime(str_replace('T', ' ', $dataFim));

if (!$inicioTimestamp || !$fimTimestamp) {
    echo 'Datas inválidas.';
    exit;
}

if ($fimTimestamp <= $inicioTimestamp) {
    echo 'A data de fim tem de ser depois da data de início.';
    exit;
}

if (($fimTimestamp - $inicioTimestamp) > 86400) {
    echo 'Uma reserva não pode exceder 24 horas.';
    exit;
}

$conexao->exec(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

$stmtReserva = $conexao->prepare(
    "SELECT id, tipo_recurso, recurso_id, data_inicio, data_fim, estado
     FROM reserva
     WHERE id = ?
       AND utilizador_id = ?
     LIMIT 1"
);
$stmtReserva->execute([$id, $utilizadorId]);
$reserva = $stmtReserva->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    echo 'Reserva não encontrada.';
    exit;
}

if ($reserva['estado'] !== 'confirmada' || strtotime((string) $reserva['data_fim']) <= time()) {
    echo 'Esta reserva já não está ativa.';
    exit;
}

$inicioOriginal = strtotime((string) $reserva['data_inicio']);
if ($inicioTimestamp < time() && (!$inicioOriginal || $inicioTimestamp < ($inicioOriginal - 60))) {
    echo 'A data de início não pode ser no passado.';
    exit;
}

if ($fimTimestamp <= time()) {
    echo 'A data de fim tem de ser futura.';
    exit;
}

$dataInicioSql = date('Y-m-d H:i:s', $inicioTimestamp);
$dataFimSql = date('Y-m-d H:i:s', $fimTimestamp);

$stmtCheck = $conexao->prepare(
    "SELECT id
     FROM reserva
     WHERE id <> ?
       AND recurso_id = ?
       AND tipo_recurso = ?
       AND estado = 'confirmada'
       AND data_inicio < ?
       AND data_fim > ?
     LIMIT 1"
);
$stmtCheck->execute([
    $id,
    (int) $reserva['recurso_id'],
    (string) $reserva['tipo_recurso'],
    $dataFimSql,
    $dataInicioSql,
]);

if ($stmtCheck->fetchColumn()) {
    echo 'Já existe uma reserva para este recurso nesse horário.';
    exit;
}

$stmtUpdate = $conexao->prepare(
    "UPDATE reserva
     SET data_inicio = ?,
         data_fim = ?
     WHERE id = ?
       AND utilizador_id = ?
       AND estado = 'confirmada'
       AND data_fim > NOW()"
);
$stmtUpdate->execute([$dataInicioSql, $dataFimSql, $id, $utilizadorId]);

echo 'ok';
