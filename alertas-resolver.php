<?php
session_start();
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

validarCsrf();

$id = (int) ($_POST['id'] ?? 0);
$resolvidoPor = (int) ($_SESSION['utilizador_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Alerta inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = obterLigacaoBD();
    $pdo->beginTransaction();

    $stmtAlerta = $pdo->prepare(
        "SELECT id, sensor_sustentabilidade_id, tipo, estado
         FROM alerta
         WHERE id = :id AND estado = 'pendente'
         LIMIT 1"
    );
    $stmtAlerta->execute(['id' => $id]);
    $alerta = $stmtAlerta->fetch(PDO::FETCH_ASSOC);

    if (!$alerta) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Alerta não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sensorId = (int) ($alerta['sensor_sustentabilidade_id'] ?? 0);
    $resolvidoPorValor = $resolvidoPor > 0 ? $resolvidoPor : null;

    if ($sensorId > 0) {
        $stmt = $pdo->prepare(
            "UPDATE alerta
             SET estado = 'resolvido', resolvido_em = NOW(), resolvido_por = :resolvido_por
             WHERE estado = 'pendente'
               AND (id = :id OR sensor_sustentabilidade_id = :sensor_id)"
        );
        $stmt->execute([
            'id' => $id,
            'sensor_id' => $sensorId,
            'resolvido_por' => $resolvidoPorValor,
        ]);

        $stmtSensor = $pdo->prepare(
            "UPDATE sensor_sustentabilidade
             SET ativo = 1, estado_tecnico = 'ativo'
             WHERE id = :sensor_id"
        );
        $stmtSensor->execute(['sensor_id' => $sensorId]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE alerta
             SET estado = 'resolvido', resolvido_em = NOW(), resolvido_por = :resolvido_por
             WHERE id = :id AND estado = 'pendente'"
        );
        $stmt->execute([
            'id' => $id,
            'resolvido_por' => $resolvidoPorValor,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Alerta resolvido.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao resolver alerta.'], JSON_UNESCAPED_UNICODE);
}
