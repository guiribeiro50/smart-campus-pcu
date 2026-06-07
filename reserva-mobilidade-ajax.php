<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('acesso_mobilidade');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

validarCsrf();

$utilizador_id = (int)($_SESSION['utilizador_id'] ?? 1);
$tipo = $_POST['tipo'] ?? '';
$zona_id = (int)($_POST['zona_id'] ?? 0);
$data = $_POST['data'] ?? '';
$inicio = $_POST['inicio'] ?? '';
$fim = $_POST['fim'] ?? '';

if (!$tipo || !$zona_id || !$data || !$inicio || !$fim) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
    exit;
}

if (strlen($inicio) === 5) {
    $inicio .= ':00';
}
if (strlen($fim) === 5) {
    $fim .= ':00';
}

if ($inicio >= $fim) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A hora de fim deve ser posterior à hora de início']);
    exit;
}

$datetime_inicio = $data . ' ' . $inicio;
$datetime_fim = $data . ' ' . $fim;

$tz = new DateTimeZone('Europe/Lisbon');
$dt_inicio = DateTime::createFromFormat('Y-m-d H:i:s', $datetime_inicio, $tz);
$dt_fim = DateTime::createFromFormat('Y-m-d H:i:s', $datetime_fim, $tz);
$agora = new DateTime('now', $tz);

if (!$dt_inicio || !$dt_fim) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Data ou hora inválida']);
    exit;
}

if ($dt_inicio <= $agora) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A reserva deve começar numa hora futura']);
    exit;
}

if ($dt_fim <= $agora) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A reserva deve terminar numa hora futura']);
    exit;
}

try {
    $conexao->exec("
        UPDATE reserva_bicicleta
        SET estado = 'concluida'
        WHERE estado = 'ativa' AND fim IS NOT NULL AND fim <= NOW()
    ");

    $conexao->exec("
        UPDATE reserva_trotinete
        SET estado = 'concluida'
        WHERE estado = 'ativa' AND fim IS NOT NULL AND fim <= NOW()
    ");

    $stmt_check_ativa = $conexao->prepare("
        SELECT
            (SELECT COUNT(*) FROM reserva_bicicleta WHERE utilizador_id = ? AND estado = 'ativa')
          + (SELECT COUNT(*) FROM reserva_trotinete WHERE utilizador_id = ? AND estado = 'ativa')
    ");
    $stmt_check_ativa->execute([$utilizador_id, $utilizador_id]);

    if ((int)$stmt_check_ativa->fetchColumn() > 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'reserva_ativa']);
        exit;
    }

    $conexao->beginTransaction();

    if ($tipo === 'bicicleta') {
        $stmt_get_bicicleta = $conexao->prepare("
            SELECT b.id, b.codigo
            FROM bicicleta b
            JOIN localidade l ON b.localidade_id = l.id
            WHERE b.estado = 'disponivel'
              AND b.ativo = 1
              AND l.id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM reserva_bicicleta rb
                  WHERE rb.bicicleta_id = b.id AND rb.estado = 'ativa'
              )
            LIMIT 1
        ");
        $stmt_get_bicicleta->execute([$zona_id]);
        $bicicleta = $stmt_get_bicicleta->fetch(PDO::FETCH_ASSOC);

        if (!$bicicleta) {
            $conexao->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma bicicleta disponível']);
            exit;
        }

        $stmt_reserva = $conexao->prepare("
            INSERT INTO reserva_bicicleta (utilizador_id, bicicleta_id, inicio, fim, estado)
            VALUES (?, ?, ?, ?, 'ativa')
        ");
        $stmt_reserva->execute([$utilizador_id, $bicicleta['id'], $datetime_inicio, $datetime_fim]);

        $stmt_update_bicicleta = $conexao->prepare("
            UPDATE bicicleta SET estado = 'em_uso' WHERE id = ? AND estado = 'disponivel'
        ");
        $stmt_update_bicicleta->execute([$bicicleta['id']]);

        if ($stmt_update_bicicleta->rowCount() === 0) {
            $conexao->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma bicicleta disponível']);
            exit;
        }

        $conexao->commit();
        echo json_encode(['sucesso' => true, 'codigo' => $bicicleta['codigo'], 'tipo' => 'bicicleta']);
    } elseif ($tipo === 'trotinete') {
        $stmt_get_trotinete = $conexao->prepare("
            SELECT t.id, t.codigo
            FROM trotinete t
            JOIN localidade l ON t.localidade_id = l.id
            WHERE t.estado = 'disponivel'
              AND t.ativo = 1
              AND l.id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM reserva_trotinete rt
                  WHERE rt.trotinete_id = t.id AND rt.estado = 'ativa'
              )
            LIMIT 1
        ");
        $stmt_get_trotinete->execute([$zona_id]);
        $trotinete = $stmt_get_trotinete->fetch(PDO::FETCH_ASSOC);

        if (!$trotinete) {
            $conexao->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma trotinete disponível']);
            exit;
        }

        $stmt_reserva = $conexao->prepare("
            INSERT INTO reserva_trotinete (utilizador_id, trotinete_id, inicio, fim, estado)
            VALUES (?, ?, ?, ?, 'ativa')
        ");
        $stmt_reserva->execute([$utilizador_id, $trotinete['id'], $datetime_inicio, $datetime_fim]);

        $stmt_update_trotinete = $conexao->prepare("
            UPDATE trotinete SET estado = 'em_uso' WHERE id = ? AND estado = 'disponivel'
        ");
        $stmt_update_trotinete->execute([$trotinete['id']]);

        if ($stmt_update_trotinete->rowCount() === 0) {
            $conexao->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma trotinete disponível']);
            exit;
        }

        $conexao->commit();
        echo json_encode(['sucesso' => true, 'codigo' => $trotinete['codigo'], 'tipo' => 'trotinete']);
    } else {
        $conexao->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tipo de veículo inválido']);
    }
} catch (Exception $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar reserva']);
}
