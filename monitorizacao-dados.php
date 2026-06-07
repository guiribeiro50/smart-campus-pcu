<?php
require_once __DIR__ . '/sensores-dados.php';

function dadosIot(): array
{
    date_default_timezone_set('Europe/Lisbon');

    try {
        return dadosIotBaseDados(obterLigacaoBD());
    } catch (Throwable $erro) {
        return dadosIotDemo();
    }
}

function dadosIotBaseDados(PDO $pdo): array
{
    require_once __DIR__ . '/alertas-dados.php';

    $sensoresBase = sensoresBaseDados($pdo);
    $leiturasPorTipo = ultimasLeiturasIotBaseDados($pdo);
    $limitesPorSensor = limitesIotBaseDados($pdo);
    $alertasPendentes = alertasPendentesPorSensor($pdo);
    $sensorIdsPorCodigo = sensorIdsPorCodigo($pdo);
    $atualizadoEm = date('H:i:s');
    $sensores = [];

    foreach ($sensoresBase as $codigo => $sensor) {
        $meta = metaTipoIot($sensor['tipo']);
        $leitura = $leiturasPorTipo[$sensor['tipo']][$codigo] ?? null;
        $limites = $limitesPorSensor[$codigo] ?? limitePadraoIot($sensor['tipo']);
        $sensorId = $sensorIdsPorCodigo[$codigo] ?? 0;
        $nivelAlerta = $alertasPendentes[$sensorId] ?? null;
        $estadoLeitura = estadoLeituraIot($sensor, $leitura, $limites, $nivelAlerta);

        if ($leitura !== null && isset($leitura['registado_em'])) {
            $atualizadoEm = max($atualizadoEm, date('H:i:s', strtotime((string) $leitura['registado_em']) ?: time()));
        }

        $sensores[] = [
            'codigo' => $codigo,
            'tipo' => $sensor['tipo'],
            'tipo_label' => $sensor['tipo_label'],
            'classe' => $meta['classe'],
            'icone' => $meta['icone'],
            'valor' => $leitura['valor'] ?? null,
            'valor_formatado' => $estadoLeitura['valor_formatado'],
            'unidade' => $sensor['unidade'],
            'localizacao' => $sensor['localizacao'],
            'recurso_tipo' => $sensor['recurso_tipo'] ?? '',
            'edificio' => $sensor['edificio_label'],
            'edificio_key' => $sensor['edificio'],
            'piso' => $sensor['piso'],
            'estado' => $estadoLeitura['estado'],
            'estado_label' => $estadoLeitura['estado_label'],
            'estado_icone' => $estadoLeitura['estado_icone'],
            'atualizado' => $leitura ? formatarHoraIot($leitura['registado_em']) : 'Sem leitura',
            'pesquisa' => strtolower($codigo . ' ' . $sensor['tipo_label'] . ' ' . $sensor['localizacao'] . ' ' . $sensor['edificio_label']),
        ];
    }

    $total = count($sensoresBase);
    $ativos = count(array_filter($sensoresBase, fn (array $sensor): bool => $sensor['estado'] === 'ativo'));
    $criticos = count(array_filter($sensores, fn (array $sensor): bool => $sensor['estado'] === 'critical'));
    $avisos = count(array_filter($sensores, fn (array $sensor): bool => $sensor['estado'] === 'warning'));

    return [
        'total_sensores' => formatarNumeroSensores($total),
        'ativos' => formatarNumeroSensores($ativos),
        'percentagem_ativos' => percentagemAtivosIot($ativos, $total),
        'criticos' => (string) $criticos,
        'avisos' => (string) $avisos,
        'atualizado_em' => $atualizadoEm,
        'sensores' => $sensores,
    ];
}

function ultimasLeiturasIotBaseDados(PDO $pdo): array
{
    $tabelas = [
        'temperatura' => 'valor_temperatura',
        'co2' => 'valor_qualidade_ar',
        'humidade' => 'valor_humidade',
        'energia' => 'valor_consumo_energetico',
    ];

    $leituras = [];

    foreach ($tabelas as $tipo => $tabela) {
        $stmt = $pdo->query(
            "SELECT s.codigo, v.valor, v.registado_em
             FROM {$tabela} v
             INNER JOIN sensor_sustentabilidade s ON s.id = v.sensor_sustentabilidade_id
             ORDER BY v.registado_em DESC, v.id DESC"
        );

        foreach ($stmt->fetchAll() as $linha) {
            if (!isset($leituras[$tipo][$linha['codigo']])) {
                $leituras[$tipo][$linha['codigo']] = [
                    'valor' => (float) $linha['valor'],
                    'registado_em' => $linha['registado_em'],
                ];
            }
        }
    }

    return $leituras;
}

function limitesIotBaseDados(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT s.codigo, l.limite_aviso, l.limite_critico
         FROM limites_alerta l
         INNER JOIN sensor_sustentabilidade s ON s.id = l.sensor_sustentabilidade_id
         WHERE l.ativo = 1"
    );

    $limites = [];

    foreach ($stmt->fetchAll() as $linha) {
        $limites[$linha['codigo']] = [
            'aviso' => (float) $linha['limite_aviso'],
            'critico' => (float) $linha['limite_critico'],
        ];
    }

    return $limites;
}

function metaTipoIot(string $tipo): array
{
    return match ($tipo) {
        'temperatura' => ['classe' => 'temperature', 'icone' => 'thermometer'],
        'co2' => ['classe' => 'air', 'icone' => 'wind'],
        'humidade' => ['classe' => 'humidity', 'icone' => 'droplets'],
        'energia' => ['classe' => 'energy', 'icone' => 'zap'],
        default => ['classe' => 'occupancy', 'icone' => 'gauge'],
    };
}

function sensorIdsPorCodigo(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, codigo FROM sensor_sustentabilidade');
    $mapa = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $mapa[(string) $linha['codigo']] = (int) $linha['id'];
    }

    return $mapa;
}

function estadoLeituraIot(array $sensor, ?array $leitura, ?array $limites, ?string $nivelAlerta = null): array
{
    if ($nivelAlerta === 'critico') {
        return [
            'estado' => 'critical',
            'estado_label' => 'Crítico',
            'estado_icone' => 'alert',
            'valor_formatado' => $leitura ? formatarValorIot((float) $leitura['valor']) : 'Sem leitura',
        ];
    }

    if ($nivelAlerta === 'aviso') {
        return [
            'estado' => 'warning',
            'estado_label' => 'Aviso',
            'estado_icone' => 'alert',
            'valor_formatado' => $leitura ? formatarValorIot((float) $leitura['valor']) : 'Sem leitura',
        ];
    }

    if ($sensor['estado'] === 'inativo') {
        return [
            'estado' => 'warning',
            'estado_label' => 'Inativo',
            'estado_icone' => 'alert',
            'valor_formatado' => 'Sem comunicação',
        ];
    }

    if ($sensor['estado'] === 'manutencao') {
        return [
            'estado' => 'warning',
            'estado_label' => 'Manutenção',
            'estado_icone' => 'alert',
            'valor_formatado' => 'Sem comunicação',
        ];
    }

    if ($leitura === null) {
        return [
            'estado' => 'warning',
            'estado_label' => 'Sem leitura',
            'estado_icone' => 'alert',
            'valor_formatado' => 'Sem leitura',
        ];
    }

    $valor = (float) $leitura['valor'];

    return [
        'estado' => 'normal',
        'estado_label' => 'Normal',
        'estado_icone' => 'check',
        'valor_formatado' => formatarValorIot($valor),
    ];
}

function limitePadraoIot(string $tipo): ?array
{
    return match ($tipo) {
        'temperatura' => ['aviso' => 28, 'critico' => 32],
        'co2' => ['aviso' => 800, 'critico' => 1000],
        'humidade' => ['aviso' => 70, 'critico' => 85],
        'energia' => ['aviso' => 900, 'critico' => 1200],
        default => null,
    };
}

function formatarValorIot(float $valor): string
{
    if (floor($valor) === $valor) {
        return (string) (int) $valor;
    }

    return number_format($valor, 1, '.', '');
}

function formatarHoraIot(?string $timestamp): string
{
    $tempo = strtotime((string) $timestamp);

    return $tempo ? date('H:i:s', $tempo) : 'Sem leitura';
}

function percentagemAtivosIot(int $ativos, int $total): string
{
    if ($total === 0) {
        return '0% operacionais';
    }

    return number_format(($ativos / $total) * 100, 1, '.', '') . '% operacionais';
}

function dadosIotDemo(): array
{
    date_default_timezone_set('Europe/Lisbon');

    $dadosLeituras = carregarLeiturasIotDemo();
    $leituras = $dadosLeituras['leituras'];
    $agora = $dadosLeituras['atualizado_em'];

    $tipoMeta = [
        'temperatura' => ['classe' => 'temperature', 'icone' => 'thermometer'],
        'co2' => ['classe' => 'air', 'icone' => 'wind'],
        'humidade' => ['classe' => 'humidity', 'icone' => 'droplets'],
        'energia' => ['classe' => 'energy', 'icone' => 'zap'],
    ];

    $sensores = [];

    foreach (sensoresDemo() as $codigo => $sensor) {
        $leitura = $leituras[$codigo] ?? ['valor' => null, 'estado' => 'warning', 'mensagem' => 'Sem leitura'];
        $meta = $tipoMeta[$sensor['tipo']];
        $valor = $leitura['valor'];
        $estado = $leitura['estado'];

        $sensores[] = [
            'codigo' => $codigo,
            'tipo' => $sensor['tipo'],
            'tipo_label' => $sensor['tipo_label'],
            'classe' => $meta['classe'],
            'icone' => $meta['icone'],
            'valor' => $valor,
            'valor_formatado' => $leitura['mensagem'] ?? formatarValorIotDemo($valor),
            'unidade' => $sensor['unidade'],
            'localizacao' => $sensor['localizacao'],
            'recurso_tipo' => $sensor['recurso_tipo'] ?? '',
            'edificio' => $sensor['edificio_label'],
            'edificio_key' => $sensor['edificio'],
            'piso' => $sensor['piso'],
            'estado' => $estado,
            'estado_label' => $estado === 'critical' ? 'Crítico' : ($estado === 'warning' ? 'Aviso' : 'Normal'),
            'estado_icone' => $estado === 'normal' ? 'check' : 'alert',
            'atualizado' => $agora,
            'pesquisa' => strtolower($codigo . ' ' . $sensor['tipo_label'] . ' ' . $sensor['localizacao'] . ' ' . $sensor['edificio_label']),
        ];
    }

    $criticos = count(array_filter($sensores, fn ($sensor) => $sensor['estado'] === 'critical'));
    $avisos = count(array_filter($sensores, fn ($sensor) => $sensor['estado'] === 'warning'));

    return [
        'total_sensores' => '10 000',
        'ativos' => '9,847',
        'percentagem_ativos' => '98.5% operacionais',
        'criticos' => (string) $criticos,
        'avisos' => (string) $avisos,
        'atualizado_em' => $agora,
        'sensores' => $sensores,
    ];
}

function leiturasIotDemoPadrao(): array
{
    return [
        'S-A-101-T' => ['valor' => 22, 'estado' => 'normal'],
        'S-A-101-C' => ['valor' => 650, 'estado' => 'normal'],
        'S-A-101-H' => ['valor' => 58, 'estado' => 'normal'],
        'S-A-201-T' => ['valor' => 0, 'estado' => 'warning', 'mensagem' => 'Sem comunicação'],
        'S-B-301-T' => ['valor' => 28, 'estado' => 'warning'],
        'S-B-301-C' => ['valor' => 850, 'estado' => 'critical'],
        'S-C-L1-E' => ['valor' => 890, 'estado' => 'normal'],
        'S-C-L2-E' => ['valor' => 420, 'estado' => 'normal'],
    ];
}

function carregarLeiturasIotDemo(): array
{
    $arquivo = __DIR__ . '/../cron/leituras_sensores_demo.json';

    if (!is_file($arquivo)) {
        return [
            'leituras' => leiturasIotDemoPadrao(),
            'atualizado_em' => date('H:i:s'),
        ];
    }

    $conteudo = file_get_contents($arquivo);
    $dados = json_decode($conteudo ?: '', true);

    if (!is_array($dados) || !isset($dados['leituras']) || !is_array($dados['leituras'])) {
        return [
            'leituras' => leiturasIotDemoPadrao(),
            'atualizado_em' => date('H:i:s'),
        ];
    }

    $timestamp = isset($dados['gerado_em']) ? strtotime($dados['gerado_em']) : time();

    return [
        'leituras' => $dados['leituras'],
        'atualizado_em' => date('H:i:s', $timestamp ?: time()),
    ];
}

function formatarValorIotDemo(mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return 'Sem leitura';
    }

    return is_float($valor) ? number_format($valor, 1, '.', '') : (string) $valor;
}

