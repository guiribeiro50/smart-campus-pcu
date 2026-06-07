<?php
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['msg' => 'Método não permitido.', 'resultado' => 'negado'], JSON_UNESCAPED_UNICODE);
    exit;
}

validarCsrf();

header('Content-Type: application/json; charset=utf-8');

function respostaAcesso(
    string $msg,
    string $resultado,
    string $nome,
    string $tipo,
    int $recursoId,
    string $espacoNome,
    ?int $reservaId = null
): void {
    echo json_encode([
        'msg' => $msg,
        'resultado' => $resultado,
        'nome' => $nome,
        'tipo' => $tipo,
        'espaco_id' => $recursoId,
        'espaco_nome' => $espacoNome,
        'reserva_id' => $reservaId,
        'hora' => date('d/m/Y H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function obterEspacoSelecionado(mysqli $conn, string $valor): ?array
{
    if (!str_contains($valor, ':')) {
        return null;
    }

    [$tipo, $idRaw] = explode(':', $valor, 2);
    $tipo = $tipo === 'laboratorio' ? 'laboratorio' : ($tipo === 'sala' ? 'sala' : '');

    if ($tipo === '' || !ctype_digit($idRaw)) {
        return null;
    }

    $id = (int) $idRaw;
    $stmt = $conn->prepare("SELECT nome, edificio, piso FROM {$tipo} WHERE id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return null;
    }

    return [
        'tipo' => $tipo,
        'id' => $id,
        'nome' => ucfirst($tipo) . ' - ' . $row['nome'] . ' (' . $row['edificio'] . ', Piso ' . $row['piso'] . ')',
    ];
}

function registarAcesso(mysqli $conn, int $utilizadorId, string $tipo, int $recursoId, string $resultado, ?string $motivo): void
{
    $stmt = $conn->prepare(
        "INSERT INTO acesso
            (utilizador_id, tipo_recurso, recurso_id, metodo, resultado, motivo_negacao)
         VALUES
            (?, ?, ?, 'manual', ?, ?)"
    );
    $stmt->bind_param('isiss', $utilizadorId, $tipo, $recursoId, $resultado, $motivo);
    $stmt->execute();
}

$utilizadorId = (int) ($_POST['utilizador_id'] ?? 0);
$espaco = obterEspacoSelecionado($conn, trim((string) ($_POST['espaco'] ?? '')));

if ($utilizadorId <= 0) {
    respostaAcesso('Indica o ID do utilizador.', 'negado', 'Utilizador invalido', '', 0, '');
}

if (!$espaco) {
    respostaAcesso('Seleciona um espaço válido.', 'negado', 'Espaco invalido', '', 0, '');
}

$conn->query(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

$stmtUtilizador = $conn->prepare('SELECT nome FROM utilizador WHERE id = ? AND ativo = 1 LIMIT 1');
$stmtUtilizador->bind_param('i', $utilizadorId);
$stmtUtilizador->execute();
$utilizador = $stmtUtilizador->get_result()->fetch_assoc();

if (!$utilizador) {
    respostaAcesso('Utilizador nao encontrado.', 'negado', 'Utilizador invalido', $espaco['tipo'], $espaco['id'], $espaco['nome']);
}

$stmt = $conn->prepare(
    "SELECT r.*
     FROM reserva r
     WHERE r.utilizador_id = ?
       AND r.tipo_recurso = ?
       AND r.recurso_id = ?
       AND r.estado = 'confirmada'
       AND r.data_inicio <= NOW()
       AND r.data_fim > NOW()
     ORDER BY r.data_fim ASC
     LIMIT 1"
);
$stmt->bind_param('isi', $utilizadorId, $espaco['tipo'], $espaco['id']);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();

if ($reserva) {
    registarAcesso($conn, $utilizadorId, $espaco['tipo'], $espaco['id'], 'autorizado', null);
    respostaAcesso('ACESSO AUTORIZADO', 'autorizado', $utilizador['nome'], $espaco['tipo'], $espaco['id'], $espaco['nome'], (int) $reserva['id']);
}

$stmtReserva = $conn->prepare(
    "SELECT data_inicio, data_fim
     FROM reserva
     WHERE utilizador_id = ?
       AND tipo_recurso = ?
       AND recurso_id = ?
       AND estado = 'confirmada'
     ORDER BY data_fim DESC
     LIMIT 1"
);
$stmtReserva->bind_param('isi', $utilizadorId, $espaco['tipo'], $espaco['id']);
$stmtReserva->execute();
$reservaExistente = $stmtReserva->get_result()->fetch_assoc();

$motivo = 'O utilizador nao tem uma reserva valida para este espaco neste horario.';
if ($reservaExistente && strtotime((string) $reservaExistente['data_fim']) < time()) {
    $motivo = 'A hora atual ja passou da hora de fim da reserva.';
}

registarAcesso($conn, $utilizadorId, $espaco['tipo'], $espaco['id'], 'negado', $motivo);
respostaAcesso('NEGADO - ' . $motivo, 'negado', $utilizador['nome'], $espaco['tipo'], $espaco['id'], $espaco['nome']);
