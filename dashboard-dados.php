<?php
require_once __DIR__ . '/monitorizacao-dados.php';
require_once __DIR__ . '/alertas-dados.php';
require_once __DIR__ . '/sustentabilidade-dados.php';

function dadosDashboard(): array
{
    $iot = dadosIot();
    $sustentabilidade = dadosSustentabilidade();
    $dadosAlertas = dadosAlertas();
    $sensores = $iot['sensores'];
    $resumoAlertas = $dadosAlertas['resumo'];

    $criticos = (int) $resumoAlertas['criticos'];
    $avisos = (int) $resumoAlertas['avisos'];
    $alertasAtivos = $criticos;
    $energiaAtualKw = consumoEnergeticoDashboard($sensores);
    $humidadeMedia = humidadeMediaDashboard($sensores);

    return [
        'iot' => $iot,
        'sustentabilidade' => $sustentabilidade,
        'alertas_resumo' => $resumoAlertas,
        'resumo' => [
            [
                'chave' => 'alertas',
                'classe' => 'danger',
                'icone' => 'icon-warning',
                'titulo' => 'Alertas Ativos',
                'valor' => (string) $alertasAtivos,
                'descricao' => resumoAlertasDashboard($criticos, $avisos),
                'descricao_classe' => '',
            ],
            [
                'chave' => 'energia',
                'classe' => 'energy',
                'icone' => 'icon-bolt',
                'titulo' => 'Consumo Energético',
                'valor' => formatarPotenciaDashboard($energiaAtualKw),
                'descricao' => $energiaAtualKw > 0 ? 'Leituras atuais dos sensores' : 'Sem leituras energéticas',
                'descricao_classe' => '',
            ],
            [
                'chave' => 'sensores',
                'classe' => 'sensors',
                'icone' => 'icon-thermometer',
                'titulo' => 'Sensores Ativos',
                'valor' => $iot['ativos'],
                'descricao' => 'De ' . $iot['total_sensores'] . ' sensores',
                'descricao_classe' => '',
            ],
            [
                'chave' => 'humidade',
                'classe' => 'humidity',
                'icone' => 'icon-droplets',
                'titulo' => 'Humidade Média',
                'valor' => $humidadeMedia === null ? 'Sem leitura' : $humidadeMedia . '%',
                'descricao' => $humidadeMedia !== null ? 'Nível adequado' : 'Sem leituras',
                'descricao_classe' => '',
            ],
        ],
        'alertas' => alertasDashboardBaseDados($dadosAlertas['alertas']),
        'consumos' => consumosEdificiosDashboard($sensores),
    ];
}

function dadosDashboardDemo(): array
{
    $iot = dadosIotDemo();
    $sustentabilidade = dadosSustentabilidadeDemo();
    $sensores = $iot['sensores'];

    $criticos = (int) $iot['criticos'];
    $avisos = (int) $iot['avisos'];
    $alertasAtivos = $criticos + $avisos;
    $energiaAtualKw = consumoEnergeticoDashboard($sensores);
    $humidadeMedia = humidadeMediaDashboard($sensores);

    return [
        'iot' => $iot,
        'sustentabilidade' => $sustentabilidade,
        'alertas_resumo' => [
            'pendentes' => $alertasAtivos,
            'criticos' => $criticos,
            'avisos' => $avisos,
            'resolvidos' => 0,
        ],
        'resumo' => [
            [
                'chave' => 'alertas',
                'classe' => 'danger',
                'icone' => 'icon-warning',
                'titulo' => 'Alertas Ativos',
                'valor' => (string) $alertasAtivos,
                'descricao' => resumoAlertasDashboard($criticos, $avisos),
                'descricao_classe' => '',
            ],
            [
                'chave' => 'energia',
                'classe' => 'energy',
                'icone' => 'icon-bolt',
                'titulo' => 'Consumo Energético',
                'valor' => formatarPotenciaDashboard($energiaAtualKw),
                'descricao' => '-18% vs mês anterior',
                'descricao_classe' => 'decrease',
            ],
            [
                'chave' => 'sensores',
                'classe' => 'sensors',
                'icone' => 'icon-thermometer',
                'titulo' => 'Sensores Ativos',
                'valor' => $iot['ativos'],
                'descricao' => 'De ' . $iot['total_sensores'] . ' sensores',
                'descricao_classe' => '',
            ],
            [
                'chave' => 'humidade',
                'classe' => 'humidity',
                'icone' => 'icon-droplets',
                'titulo' => 'Humidade Média',
                'valor' => $humidadeMedia === null ? 'Sem leitura' : $humidadeMedia . '%',
                'descricao' => $humidadeMedia !== null ? 'Nível adequado' : 'Sem leituras',
                'descricao_classe' => '',
            ],
        ],
        'alertas' => alertasDashboard($sensores),
        'consumos' => consumosEdificiosDashboard($sensores),
    ];
}

function consumoEnergeticoDashboard(array $sensores): float
{
    $total = 0;

    foreach ($sensores as $sensor) {
        if ($sensor['tipo'] === 'energia' && is_numeric($sensor['valor'])) {
            $total += (float) $sensor['valor'];
        }
    }

    return $total;
}

function humidadeMediaDashboard(array $sensores): ?int
{
    $valores = [];

    foreach ($sensores as $sensor) {
        if ($sensor['tipo'] === 'humidade' && is_numeric($sensor['valor'])) {
            $valores[] = (float) $sensor['valor'];
        }
    }

    if (!$valores) {
        return null;
    }

    return (int) round(array_sum($valores) / count($valores));
}

function formatarPotenciaDashboard(float $valorKw): string
{
    if ($valorKw >= 1000) {
        return number_format($valorKw / 1000, 1, '.', '') . ' MW';
    }

    return number_format($valorKw, 0, '.', ',') . ' kW';
}

function resumoAlertasDashboard(int $criticos, int $avisos): string
{
    $textoCriticos = $criticos . ' ' . ($criticos === 1 ? 'crítico' : 'críticos');
    $textoAvisos = $avisos . ' ' . ($avisos === 1 ? 'aviso' : 'avisos');

    return $textoCriticos . ' e ' . $textoAvisos;
}

function alertasDashboard(array $sensores): array
{
    $alertas = [];

    foreach ($sensores as $sensor) {
        if (!in_array($sensor['estado'], ['critical', 'warning'], true)) {
            continue;
        }

        $alertas[] = [
            'classe' => $sensor['estado'] === 'critical' ? 'critical' : 'warning',
            'titulo' => tituloAlertaDashboard($sensor),
            'detalhe' => detalheAlertaDashboard($sensor),
            'nivel' => $sensor['estado'] === 'critical' ? 'Crítico' : 'Aviso',
        ];
    }

    usort($alertas, fn ($a, $b) => ($a['classe'] === 'critical' ? 0 : 1) <=> ($b['classe'] === 'critical' ? 0 : 1));

    return $alertas;
}

function alertasDashboardBaseDados(array $alertas): array
{
    $pendentes = array_filter(
        $alertas,
        fn (array $alerta): bool => $alerta['estado'] === 'pendente' && $alerta['nivel'] === 'critico'
    );

    usort($pendentes, function (array $a, array $b): int {
        $pesoA = $a['nivel'] === 'critico' ? 0 : 1;
        $pesoB = $b['nivel'] === 'critico' ? 0 : 1;

        return $pesoA <=> $pesoB;
    });

    return array_values(array_map(function (array $alerta): array {
        return [
            'classe' => $alerta['nivel'] === 'critico' ? 'critical' : 'warning',
            'titulo' => $alerta['titulo'],
            'detalhe' => trim($alerta['localizacao'] . ' - ' . $alerta['valor'], ' -'),
            'nivel' => $alerta['nivel_label'],
        ];
    }, $pendentes));
}

function tituloAlertaDashboard(array $sensor): string
{
    if (($sensor['valor_formatado'] ?? '') === 'Sem comunicação') {
        return 'Sensor offline - ' . $sensor['codigo'];
    }

    return match ($sensor['tipo']) {
        'co2' => 'CO₂ elevado - ' . $sensor['localizacao'],
        'temperatura' => 'Temperatura alta - ' . $sensor['localizacao'],
        'humidade' => 'Humidade fora do ideal - ' . $sensor['localizacao'],
        'energia' => 'Consumo elevado - ' . $sensor['localizacao'],
        default => 'Alerta - ' . $sensor['codigo'],
    };
}

function detalheAlertaDashboard(array $sensor): string
{
    $valor = $sensor['valor_formatado'] === 'Sem comunicação'
        ? 'Sem comunicação'
        : $sensor['valor_formatado'] . ' ' . $sensor['unidade'];

    return $sensor['edificio'] . ', Piso ' . $sensor['piso'] . ' - ' . $valor;
}

function consumosEdificiosDashboard(array $sensores): array
{
    $consumosPorEdificio = [
        'a' => ['nome' => 'Edifício A', 'valor_kw' => 0],
        'b' => ['nome' => 'Edifício B', 'valor_kw' => 0],
        'c' => ['nome' => 'Edifício C', 'valor_kw' => 0],
        'biblioteca' => ['nome' => 'Biblioteca', 'valor_kw' => 0],
        'laboratorios' => ['nome' => 'Laboratórios', 'valor_kw' => 0],
    ];

    foreach ($sensores as $sensor) {
        if (($sensor['tipo'] ?? '') !== 'energia' || !is_numeric($sensor['valor'] ?? null)) {
            continue;
        }

        $chaveEdificio = chaveConsumoDashboard($sensor);

        if (!isset($consumosPorEdificio[$chaveEdificio])) {
            $consumosPorEdificio[$chaveEdificio] = [
                'nome' => $sensor['edificio'] ?: 'Outro edifício',
                'valor_kw' => 0,
            ];
        }

        $consumosPorEdificio[$chaveEdificio]['valor_kw'] += (float) $sensor['valor'];
    }

    $consumos = [
        $consumosPorEdificio['a'],
        $consumosPorEdificio['b'],
        $consumosPorEdificio['c'],
        $consumosPorEdificio['biblioteca'],
        $consumosPorEdificio['laboratorios'],
    ];

    $maximo = max(1, max(array_column($consumos, 'valor_kw')));

    return array_map(function (array $consumo) use ($maximo): array {
        $consumo['valor_kw'] = round((float) $consumo['valor_kw']);
        $consumo['percentagem'] = $maximo > 0 ? (int) round(($consumo['valor_kw'] / $maximo) * 100) : 0;
        $consumo['valor'] = number_format($consumo['valor_kw'], 0, '.', ',') . ' kW';

        return $consumo;
    }, $consumos);
}

function chaveConsumoDashboard(array $sensor): string
{
    $recursoTipo = (string) ($sensor['recurso_tipo'] ?? '');
    $localizacao = mb_strtolower((string) ($sensor['localizacao'] ?? ''), 'UTF-8');
    $edificio = mb_strtolower((string) ($sensor['edificio'] ?? ''), 'UTF-8');

    if ($recursoTipo === 'laboratorio' || str_contains($localizacao, 'lab')) {
        return 'laboratorios';
    }

    if (str_contains($localizacao, 'biblioteca') || str_contains($edificio, 'biblioteca')) {
        return 'biblioteca';
    }

    return (string) ($sensor['edificio_key'] ?? '');
}
