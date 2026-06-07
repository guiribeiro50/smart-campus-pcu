<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/sensores-dados.php';

function dadosAlertas(): array
{
    try {
        return dadosAlertasBaseDados(obterLigacaoBD());
    } catch (Throwable $erro) {
        return dadosAlertasDemo();
    }
}

function dadosAlertasBaseDados(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT a.*, ss.codigo, ss.tipo AS sensor_tipo,
                sala.nome AS sala_nome, sala.edificio AS sala_edificio, sala.piso AS sala_piso,
                laboratorio.nome AS laboratorio_nome, laboratorio.edificio AS laboratorio_edificio, laboratorio.piso AS laboratorio_piso
         FROM alerta a
         LEFT JOIN sensor_sustentabilidade ss ON ss.id = a.sensor_sustentabilidade_id
         LEFT JOIN sala ON ss.recurso_tipo = 'sala' AND ss.recurso_id = sala.id
         LEFT JOIN laboratorio ON ss.recurso_tipo = 'laboratorio' AND ss.recurso_id = laboratorio.id
         ORDER BY COALESCE(a.criado_em, NOW()) DESC"
    );

    $alertas = array_map('normalizarAlertaDashboard', $stmt->fetchAll(PDO::FETCH_ASSOC));
    return ['resumo' => resumoAlertas($alertas), 'alertas' => $alertas];
}

function normalizarAlertaDashboard(array $linha): array
{
    $nivel = strtolower((string) ($linha['tipo'] ?? $linha['nivel'] ?? 'aviso'));
    $nivel = $nivel === 'critico' || $nivel === 'crítico' ? 'critico' : 'aviso';
    $estado = strtolower((string) ($linha['estado'] ?? 'pendente'));
    $recurso = trim((string) ($linha['sala_nome'] ?? $linha['laboratorio_nome'] ?? ''));
    $edificio = trim((string) ($linha['sala_edificio'] ?? $linha['laboratorio_edificio'] ?? ''));
    $piso = trim((string) ($linha['sala_piso'] ?? $linha['laboratorio_piso'] ?? ''));
    $localizacao = trim($recurso . ($edificio !== '' ? ', Edifício ' . strtoupper($edificio) : '') . ($piso !== '' ? ', Piso ' . $piso : ''));
    $codigo = (string) ($linha['codigo'] ?? '');
    $valor = isset($linha['valor_registado']) ? (float) $linha['valor_registado'] : null;
    $criadoEm = (string) ($linha['criado_em'] ?? $linha['registado_em'] ?? '');
    $tipoSensor = tipoSensorInterface((string) ($linha['sensor_tipo'] ?? 'temperatura'));

    return [
        'id' => (int) ($linha['id'] ?? 0),
        'estado' => $estado !== '' ? $estado : 'pendente',
        'estado_label' => $estado === 'resolvido' ? 'Resolvido' : 'Pendente',
        'nivel' => $nivel,
        'nivel_label' => $nivel === 'critico' ? 'Crítico' : 'Aviso',
        'titulo' => (string) ($linha['mensagem'] ?? ('Alerta do sensor ' . $codigo)),
        'localizacao' => $localizacao !== '' ? $localizacao : $codigo,
        'codigo' => $codigo,
        'valor' => $valor !== null ? formatarValorAlerta($valor, $tipoSensor) : '',
        'data' => $criadoEm !== '' ? date('d/m/Y, H:i', strtotime($criadoEm)) : '-',
        'resolvido_em' => (string) ($linha['resolvido_em'] ?? ''),
    ];
}

function formatarValorAlerta(float $valor, string $tipo): string
{
    $unidade = match ($tipo) {
        'co2' => 'ppm',
        'humidade' => '%',
        'energia' => 'kW',
        default => '°C',
    };

    return number_format($valor, $tipo === 'co2' ? 0 : 1, ',', '.') . ' ' . $unidade;
}

function alertasPendentesPorSensor(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT sensor_sustentabilidade_id, tipo
         FROM alerta
         WHERE estado = 'pendente' AND sensor_sustentabilidade_id IS NOT NULL"
    );

    $mapa = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $sensorId = (int) ($linha['sensor_sustentabilidade_id'] ?? 0);
        if ($sensorId <= 0) {
            continue;
        }

        $tipo = strtolower((string) ($linha['tipo'] ?? 'aviso'));
        $nivel = $tipo === 'critico' ? 'critico' : 'aviso';

        if (!isset($mapa[$sensorId]) || $mapa[$sensorId] === 'aviso') {
            $mapa[$sensorId] = $nivel;
        }
    }

    return $mapa;
}

function resumoAlertas(array $alertas): array
{
    $pendentes = array_filter($alertas, fn (array $alerta): bool => $alerta['estado'] === 'pendente');
    $hoje = date('Y-m-d');
    $resolvidosHoje = array_filter($alertas, function (array $alerta) use ($hoje): bool {
        if ($alerta['estado'] !== 'resolvido' || $alerta['resolvido_em'] === '') {
            return false;
        }
        return str_starts_with($alerta['resolvido_em'], $hoje);
    });

    return [
        'pendentes' => count($pendentes),
        'criticos' => count(array_filter($pendentes, fn (array $alerta): bool => $alerta['nivel'] === 'critico')),
        'avisos' => count(array_filter($pendentes, fn (array $alerta): bool => $alerta['nivel'] !== 'critico')),
        'resolvidos' => count(array_filter($alertas, fn (array $alerta): bool => $alerta['estado'] !== 'pendente')),
        'resolvidos_hoje' => count($resolvidosHoje),
    ];
}

function dadosAlertasDemo(): array
{
    $alertas = [
        [
            'id' => 1,
            'estado' => 'pendente',
            'estado_label' => 'Pendente',
            'nivel' => 'critico',
            'nivel_label' => 'Crítico',
            'titulo' => 'CO2 elevado - Lab 3',
            'localizacao' => 'Lab Informática 3, Edifício B',
            'codigo' => 'S-B-301-C',
            'valor' => '1000 ppm',
            'data' => date('d/m/Y, H:i'),
            'resolvido_em' => '',
        ],
        [
            'id' => 2,
            'estado' => 'pendente',
            'estado_label' => 'Pendente',
            'nivel' => 'aviso',
            'nivel_label' => 'Aviso',
            'titulo' => 'Temperatura alta - Sala 201',
            'localizacao' => 'Sala 201, Edifício A',
            'codigo' => 'S-A-201-T',
            'valor' => '28,0 °C',
            'data' => date('d/m/Y, H:i'),
            'resolvido_em' => '',
        ],
    ];

    return ['resumo' => resumoAlertas($alertas), 'alertas' => $alertas];
}
