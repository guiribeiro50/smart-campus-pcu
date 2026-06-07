<?php
require_once __DIR__ . '/config/database.php';

if (PHP_SAPI !== 'cli') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['sucesso' => false, 'mensagem' => 'Método não permitido.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_espacos');
    validarCsrf();
}

date_default_timezone_set('Europe/Lisbon');
ini_set('serialize_precision', '-1');

function tabelaLeituraCron(string $tipo): ?string
{
    return match ($tipo) {
        'temperatura' => 'valor_temperatura',
        'qualidade_ar' => 'valor_qualidade_ar',
        'humidade' => 'valor_humidade',
        'consumo_energetico' => 'valor_consumo_energetico',
        default => null,
    };
}

function unidadeLeituraCron(string $tipo): string
{
    return match ($tipo) {
        'temperatura' => '°C',
        'qualidade_ar' => 'ppm',
        'humidade' => '%',
        'consumo_energetico' => 'kW',
        default => '',
    };
}

function sensoresAtivosCron(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT ss.id, ss.codigo, ss.tipo, ss.recurso_tipo, ss.recurso_id,
                sala.nome AS sala_nome, sala.edificio AS sala_edificio, sala.piso AS sala_piso,
                laboratorio.nome AS laboratorio_nome, laboratorio.edificio AS laboratorio_edificio, laboratorio.piso AS laboratorio_piso
         FROM sensor_sustentabilidade ss
         LEFT JOIN sala ON ss.recurso_tipo = 'sala' AND ss.recurso_id = sala.id
         LEFT JOIN laboratorio ON ss.recurso_tipo = 'laboratorio' AND ss.recurso_id = laboratorio.id
         WHERE ss.ativo = 1 AND ss.estado_tecnico = 'ativo'
         ORDER BY ss.id"
    );

    return $stmt->fetchAll();
}

function gerarValorSensorCron(array $sensor): float|array|null
{
    return match ($sensor['tipo']) {
        'temperatura' => mt_rand(185, 295) / 10,
        'qualidade_ar' => (float) mt_rand(420, 980),
        'humidade' => (float) mt_rand(38, 74),
        'consumo_energetico' => (float) mt_rand(180, 980),
        default => null,
    };
}

function inserirLeituraCron(PDO $pdo, array $sensor, float|array|null $valor): ?array
{
    if ($valor === null) {
        return null;
    }

    $tabela = tabelaLeituraCron($sensor['tipo']);

    if ($tabela === null) {
        return null;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO {$tabela} (sensor_sustentabilidade_id, valor)
         VALUES (:sensor_sustentabilidade_id, :valor)"
    );
    $stmt->execute([
        'sensor_sustentabilidade_id' => $sensor['id'],
        'valor' => $valor,
    ]);

    return [
        'codigo' => $sensor['codigo'],
        'tipo' => $sensor['tipo'],
        'tabela' => $tabela,
        'valor' => $valor,
        'unidade' => unidadeLeituraCron($sensor['tipo']),
    ];
}

function limitesSensorCron(PDO $pdo, int $sensorId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT limite_aviso, limite_critico
         FROM limites_alerta
         WHERE sensor_sustentabilidade_id = :sensor_sustentabilidade_id AND ativo = 1
         LIMIT 1"
    );
    $stmt->execute(['sensor_sustentabilidade_id' => $sensorId]);
    $limites = $stmt->fetch();

    if (!$limites) {
        return null;
    }

    return [
        'aviso' => (float) $limites['limite_aviso'],
        'critico' => (float) $limites['limite_critico'],
    ];
}

function valorParaAlertaCron(string $tipo, float|array|null $valor): ?float
{
    if ($valor === null) {
        return null;
    }

    return is_array($valor) ? null : (float) $valor;
}

function criarAlertaLeituraCron(PDO $pdo, array $sensor, float|array|null $valor): ?array
{
    $valorAlerta = valorParaAlertaCron($sensor['tipo'], $valor);
    $limites = limitesSensorCron($pdo, (int) $sensor['id']);

    if ($valorAlerta === null || $limites === null) {
        return null;
    }

    if ($valorAlerta >= $limites['critico']) {
        $tipo = 'critico';
        $mensagem = mensagemAlertaCron($sensor['tipo'], 'crítico');
    } elseif ($valorAlerta >= $limites['aviso']) {
        $tipo = 'aviso';
        $mensagem = mensagemAlertaCron($sensor['tipo'], 'aviso');
    } else {
        return null;
    }

    if (existeAlertaPendenteCron($pdo, (int) $sensor['id'], $tipo)) {
        return null;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO alerta (sensor_sustentabilidade_id, tipo, mensagem, valor_registado, estado, lido)
         VALUES (:sensor_sustentabilidade_id, :tipo, :mensagem, :valor_registado, 'pendente', 0)"
    );
    $stmt->execute([
        'sensor_sustentabilidade_id' => $sensor['id'],
        'tipo' => $tipo,
        'mensagem' => $mensagem,
        'valor_registado' => $valorAlerta,
    ]);

    return [
        'sensor' => $sensor['codigo'],
        'tipo' => $tipo,
        'mensagem' => $mensagem,
        'valor' => $valorAlerta,
    ];
}

function existeAlertaPendenteCron(PDO $pdo, int $sensorId, string $tipo): bool
{
    $stmt = $pdo->prepare(
        "SELECT id
         FROM alerta
         WHERE sensor_sustentabilidade_id = :sensor_sustentabilidade_id AND tipo = :tipo AND estado = 'pendente'
         LIMIT 1"
    );
    $stmt->execute([
        'sensor_sustentabilidade_id' => $sensorId,
        'tipo' => $tipo,
    ]);

    return (bool) $stmt->fetch();
}

function mensagemAlertaCron(string $tipoSensor, string $nivel): string
{
    return match ($tipoSensor) {
        'temperatura' => 'Temperatura acima do limite ' . $nivel,
        'qualidade_ar' => 'CO₂ acima do limite ' . $nivel,
        'humidade' => 'Humidade acima do limite ' . $nivel,
        'consumo_energetico' => 'Consumo energético acima do limite ' . $nivel,
        default => 'Sensor acima do limite ' . $nivel,
    };
}

function recolherDadosSensoresCron(): array
{
    $pdo = obterLigacaoBD();
    $sensores = sensoresAtivosCron($pdo);
    $leituras = [];
    $alertas = [];
    $ignorados = [];

    $pdo->beginTransaction();

    try {
        foreach ($sensores as $sensor) {
            $valor = gerarValorSensorCron($sensor);
            $leitura = inserirLeituraCron($pdo, $sensor, $valor);

            if ($leitura === null) {
                $ignorados[] = [
                    'codigo' => $sensor['codigo'],
                    'tipo' => $sensor['tipo'],
                ];
                continue;
            }

            $leituras[] = $leitura;
            $alerta = criarAlertaLeituraCron($pdo, $sensor, $valor);

            if ($alerta !== null) {
                $alertas[] = $alerta;
            }
        }

        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }

    return [
        'sucesso' => true,
        'mensagem' => 'Leituras dos sensores recolhidas e guardadas na base de dados.',
        'gerado_em' => date('c'),
        'sensores_ativos' => count($sensores),
        'leituras_inseridas' => count($leituras),
        'alertas_criados' => count($alertas),
        'sensores_ignorados' => count($ignorados),
        'leituras' => $leituras,
        'alertas' => $alertas,
        'ignorados' => $ignorados,
    ];
}

if (!defined('RECOLHER_DADOS_SKIP_OUTPUT')) {
    try {
        $resultado = recolherDadosSensoresCron();
    } catch (Throwable $erro) {
        error_log('Erro ao recolher dados dos sensores: ' . $erro->getMessage());
        $resultado = [
            'sucesso' => false,
            'mensagem' => 'Não foi possível recolher leituras dos sensores.',
            'gerado_em' => date('c'),
        ];
    }

    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (PHP_SAPI === 'cli') {
        echo PHP_EOL;
    }
}
