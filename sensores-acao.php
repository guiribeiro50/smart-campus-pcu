<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('gestao_espacos');

require_once __DIR__ . '/config/database.php';

define('RECOLHER_DADOS_SKIP_OUTPUT', true);
require_once __DIR__ . '/recolher-dados.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

validarCsrf();

$codigo = trim($_POST['codigo'] ?? '');
$acao = trim($_POST['acao'] ?? '');

if ($codigo === '' || !in_array($acao, ['toggle', 'refresh'], true)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Pedido inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = obterLigacaoBD();

    if ($acao === 'toggle') {
        $stmt = $pdo->prepare(
            "SELECT ativo, estado_tecnico FROM sensor_sustentabilidade WHERE codigo = :codigo LIMIT 1"
        );
        $stmt->execute(['codigo' => $codigo]);
        $sensor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sensor) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sensor não encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ativo = (int) $sensor['ativo'] === 1 ? 0 : 1;
        $estado = $ativo ? 'ativo' : 'inativo';

        $update = $pdo->prepare(
            "UPDATE sensor_sustentabilidade
             SET ativo = :ativo, estado_tecnico = :estado
             WHERE codigo = :codigo"
        );
        $update->execute([
            'ativo' => $ativo,
            'estado' => $estado,
            'codigo' => $codigo,
        ]);

        echo json_encode([
            'sucesso' => true,
            'mensagem' => $ativo ? 'Sensor ativado.' : 'Sensor desativado.',
            'estado' => $estado,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    recolherDadosSensoresCron();
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Leitura atualizada.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível executar a ação.',
    ], JSON_UNESCAPED_UNICODE);
}
