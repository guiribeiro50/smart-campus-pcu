<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('reservar_salas');

date_default_timezone_set('Europe/Lisbon');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido.';
    exit;
}

validarCsrf();

if (!isset($_SESSION['utilizador_id'])) {
    http_response_code(401);
    echo 'Sessão expirada. Faça login novamente.';
    exit;
}

$utilizadorId = (int) $_SESSION['utilizador_id'];
$salaId = (int) ($_POST['sala_id'] ?? 0);
$dataInicio = (string) ($_POST['data_inicio'] ?? '');
$dataFim = (string) ($_POST['data_fim'] ?? '');
$finalidade = trim((string) ($_POST['finalidade'] ?? ''));

if ($salaId <= 0 || $dataInicio === '' || $dataFim === '') {
    echo 'Preencha todos os campos obrigatórios.';
    exit;
}

$inicioTimestamp = strtotime(str_replace('T', ' ', $dataInicio));
$fimTimestamp = strtotime(str_replace('T', ' ', $dataFim));

if (!$inicioTimestamp || !$fimTimestamp) {
    echo 'Datas inválidas.';
    exit;
}

$dataInicioSql = date('Y-m-d H:i:s', $inicioTimestamp);
$dataFimSql = date('Y-m-d H:i:s', $fimTimestamp);

if ($inicioTimestamp < time()) {
    echo 'A data de início não pode ser no passado.';
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

try {
    $stmtSala = $conexao->prepare('SELECT id FROM sala WHERE id = ? AND ativo = 1');
    $stmtSala->execute([$salaId]);
    if (!$stmtSala->fetchColumn()) {
        echo 'Sala inválida ou inativa.';
        exit;
    }

    $stmtCheck = $conexao->prepare(
        "SELECT id FROM reserva
         WHERE recurso_id = ?
           AND tipo_recurso = 'sala'
           AND estado = 'confirmada'
           AND data_inicio < ?
           AND data_fim > ?
         LIMIT 1"
    );
    $stmtCheck->execute([$salaId, $dataFimSql, $dataInicioSql]);

    if ($stmtCheck->fetchColumn()) {
        echo 'Já existe uma reserva para esta sala nesse horário.';
        exit;
    }

    $stmt = $conexao->prepare(
        "INSERT INTO reserva
            (utilizador_id, recurso_id, tipo_recurso, estado, data_inicio, data_fim, finalidade)
         VALUES
            (?, ?, 'sala', 'confirmada', ?, ?, ?)"
    );
    $stmt->execute([$utilizadorId, $salaId, $dataInicioSql, $dataFimSql, $finalidade]);

    echo 'ok';
} catch (Throwable $e) {
    error_log('Erro ao criar reserva: ' . $e->getMessage());
    echo 'Erro ao guardar a reserva.';
}
