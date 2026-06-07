<?php
require_once __DIR__ . '/config/database.php';

ini_set('serialize_precision', '-1');

if (!function_exists('h')) {
    function h(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

function dadosSustentabilidade(): array
{
    try {
        $leituras = leiturasEnergiaSustentabilidade(obterLigacaoBD());

        if (!$leituras) {
            return dadosSustentabilidadeSemLeituras();
        }

        return montarDadosSustentabilidade($leituras);
    } catch (Throwable $erro) {
        return dadosSustentabilidadeDemo();
    }
}

function leiturasEnergiaSustentabilidade(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT v.valor, v.registado_em, ss.codigo, ss.recurso_tipo,
                sala.nome AS sala_nome, sala.edificio AS sala_edificio,
                laboratorio.nome AS laboratorio_nome, laboratorio.edificio AS laboratorio_edificio
         FROM valor_consumo_energetico v
         INNER JOIN sensor_sustentabilidade ss ON ss.id = v.sensor_sustentabilidade_id
         LEFT JOIN sala ON ss.recurso_tipo = 'sala' AND ss.recurso_id = sala.id
         LEFT JOIN laboratorio ON ss.recurso_tipo = 'laboratorio' AND ss.recurso_id = laboratorio.id
         ORDER BY v.registado_em DESC, v.id DESC
         LIMIT 80"
    );

    return array_map('normalizarLeituraEnergiaSustentabilidade', $stmt->fetchAll());
}

function normalizarLeituraEnergiaSustentabilidade(array $linha): array
{
    $recursoTipo = (string) ($linha['recurso_tipo'] ?? '');
    $prefixo = $recursoTipo === 'laboratorio' ? 'laboratorio' : 'sala';
    $recurso = trim((string) ($linha[$prefixo . '_nome'] ?? ''));
    $edificio = trim((string) ($linha[$prefixo . '_edificio'] ?? ''));

    return [
        'codigo' => (string) $linha['codigo'],
        'valor' => (float) $linha['valor'],
        'registado_em' => (string) $linha['registado_em'],
        'timestamp' => strtotime((string) $linha['registado_em']) ?: time(),
        'recurso_tipo' => $recursoTipo,
        'recurso' => $recurso !== '' ? $recurso : 'Recurso por definir',
        'edificio' => $edificio,
        'categoria' => categoriaEnergiaSustentabilidade($recursoTipo, $recurso, $edificio),
    ];
}

function montarDadosSustentabilidade(array $leituras): array
{
    $leiturasPorSensor = ultimasLeiturasPorSensorSustentabilidade($leituras);
    $serieCompleta = serieConsumoAtualSustentabilidade($leituras);
    $serieEnergia = array_slice($serieCompleta, -6);
    $energiaAtual = ultimoValorSerieSustentabilidade($serieCompleta);
    $energiaPico = max($energiaAtual, max(array_column($serieCompleta, 'valor') ?: [0]));
    $poupancaKw = max(0, $energiaPico - $energiaAtual);
    $poupancaPercentagem = $energiaPico > 0 ? (int) round(($poupancaKw / $energiaPico) * 100) : 0;
    $periodo = periodoLeiturasSustentabilidade($leituras);

    return [
        'periodo' => $periodo,
        'resumo' => [
            [
                'icone' => 'bolt',
                'cor' => 'orange',
                'titulo' => 'Poupança Energética',
                'valor' => '-' . $poupancaPercentagem . '%',
                'valor_cor' => $poupancaPercentagem > 0 ? 'green' : 'orange',
                'descricao' => formatarValorSustentabilidade($poupancaKw) . ' kWh estimados',
                'descricao_cor' => $poupancaPercentagem > 0 ? 'green' : 'orange',
                'tendencia' => $poupancaPercentagem > 0,
            ],
            [
                'icone' => 'bolt',
                'cor' => 'green',
                'titulo' => 'Consumo Atual',
                'valor' => formatarValorSustentabilidade($energiaAtual) . ' kWh',
                'valor_cor' => '',
                'descricao' => 'Última leitura consolidada',
                'descricao_cor' => '',
                'tendencia' => false,
            ],
            [
                'icone' => 'medal',
                'cor' => 'orange',
                'titulo' => 'Pico Energético',
                'valor' => formatarValorSustentabilidade($energiaPico) . ' kWh',
                'valor_cor' => 'orange',
                'descricao' => 'Valor máximo do período',
                'descricao_cor' => '',
                'tendencia' => false,
            ],
        ],
        'metas' => metasSustentabilidade($poupancaPercentagem),
        'evolucao' => evolucaoExportacaoSustentabilidade($serieEnergia, $energiaPico),
        'tendencia' => tendenciaSustentabilidade($serieEnergia, $poupancaPercentagem, 'energia'),
        'graficos' => [
            'energia' => graficoSustentabilidade(
                'Evolução do Consumo Energético',
                'Últimas leituras reais de consumo e poupança estimada (kWh)',
                'Consumo por Recolha',
                'Consumo (kWh)',
                'Poupança estimada (kWh)',
                $serieEnergia,
                1,
                1,
                'energia',
                $energiaPico
            ),
        ],
        'edificios' => edificiosSustentabilidade($leiturasPorSensor),
        'metricas' => [
            'energia_atual_kw' => $energiaAtual,
            'energia_pico_kw' => $energiaPico,
            'poupanca_kw' => $poupancaKw,
        ],
    ];
}

function dadosSustentabilidadeSemLeituras(): array
{
    $dados = dadosSustentabilidadeDemo();
    $dados['periodo'] = 'Sem leituras energéticas registadas';
    $dados['resumo'][0]['valor'] = '0%';
    $dados['resumo'][0]['descricao'] = 'Sem dados reais';
    $dados['resumo'][1]['valor'] = '0 kWh';
    $dados['resumo'][1]['descricao'] = 'Sem dados reais';
    $dados['resumo'][2]['valor'] = '0 kWh';
    $dados['resumo'][2]['descricao'] = 'Sem dados reais';

    return $dados;
}

function ultimasLeiturasPorSensorSustentabilidade(array $leituras): array
{
    $porSensor = [];

    foreach ($leituras as $leitura) {
        if (!isset($porSensor[$leitura['codigo']])) {
            $porSensor[$leitura['codigo']] = $leitura;
        }
    }

    return array_values($porSensor);
}

function serieConsumoAtualSustentabilidade(array $leituras): array
{
    usort($leituras, fn (array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);

    $porRecolha = [];
    $estadoSensores = [];

    foreach ($leituras as $leitura) {
        $chave = $leitura['registado_em'];

        if (!isset($porRecolha[$chave])) {
            $porRecolha[$chave] = [
                'timestamp' => $leitura['timestamp'],
                'mes' => labelRecolhaSustentabilidade($leitura['timestamp']),
                'leituras' => [],
            ];
        }

        $porRecolha[$chave]['leituras'][] = $leitura;
    }

    usort($porRecolha, fn (array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);

    return array_map(function (array $recolha) use (&$estadoSensores): array {
        foreach ($recolha['leituras'] as $leitura) {
            $estadoSensores[$leitura['codigo']] = $leitura['valor'];
        }

        return [
            'timestamp' => $recolha['timestamp'],
            'mes' => $recolha['mes'],
            'valor' => array_sum($estadoSensores),
        ];
    }, array_values($porRecolha));
}

function ultimoValorSerieSustentabilidade(array $serie): float
{
    if (!$serie) {
        return 0;
    }

    $ultimo = $serie[array_key_last($serie)];

    return (float) ($ultimo['valor'] ?? 0);
}

function graficoSustentabilidade(
    string $titulo,
    string $descricao,
    string $tituloPeriodo,
    string $legendaPrincipal,
    string $legendaSecundaria,
    array $serieEnergia,
    float $fatorPrincipal,
    float $fatorSecundaria,
    string $tipo,
    float $energiaPico
): array {
    $principalBase = array_map(fn (array $linha): float => $linha['valor'] * $fatorPrincipal, $serieEnergia);
    $secundariaBase = array_map(fn (array $linha): float => max(0, $energiaPico - $linha['valor']) * $fatorSecundaria, $serieEnergia);
    $maxPrincipal = max(1, max($principalBase ?: [0]));
    $maxSecundaria = max(1, max($secundariaBase ?: [0]));

    $evolucao = array_map(function (array $linha) use ($energiaPico, $fatorPrincipal, $fatorSecundaria, $maxPrincipal): array {
        $principal = $linha['valor'] * $fatorPrincipal;
        $secundaria = max(0, $energiaPico - $linha['valor']) * $fatorSecundaria;

        return [
            'mes' => $linha['mes'],
            'principal' => round($principal, 1),
            'secundaria' => round($secundaria, 1),
            'barra' => (int) round(($principal / $maxPrincipal) * 100),
        ];
    }, $serieEnergia);

    return [
        'titulo' => $titulo,
        'descricao' => $descricao,
        'titulo_periodo' => $tituloPeriodo,
        'legenda_principal' => $legendaPrincipal,
        'legenda_secundaria' => $legendaSecundaria,
        'escala' => escalaSustentabilidade($maxPrincipal),
        'min_principal' => 0,
        'max_principal' => $maxPrincipal,
        'min_secundaria' => 0,
        'max_secundaria' => $maxSecundaria,
        'evolucao' => $evolucao,
        'tendencia' => tendenciaSustentabilidade($serieEnergia, percentagemPoupancaSustentabilidade($serieEnergia, $energiaPico), $tipo),
    ];
}

function evolucaoExportacaoSustentabilidade(array $serieEnergia, float $energiaPico): array
{
    return array_map(fn (array $linha): array => [
        'mes' => $linha['mes'],
        'consumo_kwh' => round($linha['valor'], 1),
        'poupanca_kwh' => round(max(0, $energiaPico - $linha['valor']), 1),
        'barra' => $energiaPico > 0 ? (int) round(($linha['valor'] / $energiaPico) * 100) : 0,
    ], $serieEnergia);
}

function tendenciaSustentabilidade(array $serieEnergia, int $poupancaPercentagem, string $tipo): array
{
    $primeiro = (float) ($serieEnergia[0]['valor'] ?? 0);
    $ultimo = ultimoValorSerieSustentabilidade($serieEnergia);
    $estado = $ultimo < $primeiro ? 'Decrescente' : ($ultimo > $primeiro ? 'Crescente' : 'Estável');
    $descricao = 'A tendência usa as últimas leituras reais de energia recolhidas pelos sensores de sustentabilidade.';

    return [
        'estado' => $estado,
        'descricao' => $descricao . ' A poupança atual face ao pico registado é de ' . $poupancaPercentagem . '%.',
        'fatores' => [
            'Leituras reais dos sensores de consumo energético',
            'Comparação com o pico registado no período',
            'Separação por salas, laboratórios e edifícios',
            'Atualização sempre que o cron recolhe novos dados',
        ],
        'projecao' => 'Com mais leituras mensais, este relatório passa a mostrar uma tendência anual mais estável.',
    ];
}

function percentagemPoupancaSustentabilidade(array $serieEnergia, float $energiaPico): int
{
    if ($energiaPico <= 0 || !$serieEnergia) {
        return 0;
    }

    $ultimo = ultimoValorSerieSustentabilidade($serieEnergia);

    return (int) round((max(0, $energiaPico - $ultimo) / $energiaPico) * 100);
}

function edificiosSustentabilidade(array $leiturasPorSensor): array
{
    $categorias = [];

    foreach ($leiturasPorSensor as $leitura) {
        $categoria = $leitura['categoria'];

        if (!isset($categorias[$categoria])) {
            $categorias[$categoria] = [
                'edificio' => $categoria,
                'energia_kwh' => 0,
            ];
        }

        $categorias[$categoria]['energia_kwh'] += $leitura['valor'];
    }

    return array_values(array_map(function (array $categoria): array {
        $categoria['energia_kwh'] = round($categoria['energia_kwh'], 1);

        return $categoria;
    }, $categorias));
}

function metasSustentabilidade(int $poupancaPercentagem): array
{
    $progressoEnergia = min(100, (int) round(($poupancaPercentagem / 20) * 100));

    return [
        ['classe' => 'green-goal', 'titulo' => 'Meta Energética', 'descricao' => '-20% até Dezembro', 'estado' => $progressoEnergia . '% Atingido'],
    ];
}

function categoriaEnergiaSustentabilidade(string $recursoTipo, string $recurso, string $edificio): string
{
    $texto = mb_strtolower($recurso . ' ' . $edificio, 'UTF-8');

    if ($recursoTipo === 'laboratorio' || str_contains($texto, 'lab')) {
        return 'Laboratórios';
    }

    if (str_contains($texto, 'biblioteca')) {
        return 'Biblioteca';
    }

    return normalizarEdificioSustentabilidade($edificio);
}

function normalizarEdificioSustentabilidade(string $edificio): string
{
    $edificioNormalizado = mb_strtolower(trim($edificio), 'UTF-8');

    if ($edificioNormalizado === 'a' || str_ends_with($edificioNormalizado, ' a')) {
        return 'Edifício A';
    }

    if ($edificioNormalizado === 'b' || str_ends_with($edificioNormalizado, ' b')) {
        return 'Edifício B';
    }

    if ($edificioNormalizado === 'c' || str_ends_with($edificioNormalizado, ' c')) {
        return 'Edifício C';
    }

    return trim($edificio) !== '' ? trim($edificio) : 'Outro recurso';
}

function periodoLeiturasSustentabilidade(array $leituras): string
{
    $timestamps = array_column($leituras, 'timestamp');
    $inicio = min($timestamps);
    $fim = max($timestamps);

    if (date('Y-m-d', $inicio) === date('Y-m-d', $fim)) {
        return date('d/m/Y, H:i', $inicio) . ' - ' . date('H:i', $fim);
    }

    return date('d/m/Y, H:i', $inicio) . ' - ' . date('d/m/Y, H:i', $fim);
}

function labelRecolhaSustentabilidade(int $timestamp): string
{
    if (date('Y-m-d', $timestamp) === date('Y-m-d')) {
        return date('H:i:s', $timestamp);
    }

    return date('d/m H:i:s', $timestamp);
}

function escalaSustentabilidade(float $max): array
{
    $max = max(1, (float) ceil($max));

    return [
        formatarValorSustentabilidade($max),
        formatarValorSustentabilidade($max * 0.75),
        formatarValorSustentabilidade($max * 0.5),
        formatarValorSustentabilidade($max * 0.25),
    ];
}

function formatarValorSustentabilidade(float $valor): string
{
    if (abs($valor) >= 100) {
        return number_format($valor, 0, ',', ' ');
    }

    if (floor($valor) === $valor) {
        return (string) (int) $valor;
    }

    return number_format($valor, 1, ',', ' ');
}

function formatarNumeroSustentabilidade(int $valor): string
{
    return number_format($valor, 0, ',', ' ');
}

function dadosSustentabilidadeDemo(): array
{
    return [
        'periodo' => 'Janeiro a Junho de 2026',
        'resumo' => [
            [
                'icone' => 'bolt',
                'cor' => 'orange',
                'titulo' => 'Poupança Energética',
                'valor' => '-18%',
                'valor_cor' => 'green',
                'descricao' => '800 kWh poupados/mês',
                'descricao_cor' => 'green',
                'tendencia' => true,
            ],
            [
                'icone' => 'bolt',
                'cor' => 'green',
                'titulo' => 'Consumo Atual',
                'valor' => '2 016 kWh',
                'valor_cor' => '',
                'descricao' => 'Última leitura consolidada',
                'descricao_cor' => '',
                'tendencia' => false,
            ],
            [
                'icone' => 'medal',
                'cor' => 'orange',
                'titulo' => 'Pico Energético',
                'valor' => '2 800 kWh',
                'valor_cor' => 'orange',
                'descricao' => 'Valor máximo do período',
                'descricao_cor' => '',
                'tendencia' => false,
            ],
        ],
        'metas' => [
            ['classe' => 'green-goal', 'titulo' => 'Meta Energética', 'descricao' => '-20% até Dezembro', 'estado' => '90% Atingido'],
        ],
        'evolucao' => [
            ['mes' => 'Jan', 'consumo_kwh' => 2800, 'poupanca_kwh' => 0, 'barra' => 100],
            ['mes' => 'Fev', 'consumo_kwh' => 2630, 'poupanca_kwh' => 170, 'barra' => 94],
            ['mes' => 'Mar', 'consumo_kwh' => 2460, 'poupanca_kwh' => 340, 'barra' => 88],
            ['mes' => 'Abr', 'consumo_kwh' => 2300, 'poupanca_kwh' => 500, 'barra' => 82],
            ['mes' => 'Mai', 'consumo_kwh' => 2160, 'poupanca_kwh' => 640, 'barra' => 77],
            ['mes' => 'Jun', 'consumo_kwh' => 2016, 'poupanca_kwh' => 800, 'barra' => 72],
        ],
        'tendencia' => [
            'estado' => 'Decrescente',
            'descricao' => 'O consumo tem diminuído consistentemente nos últimos 6 meses, com uma redução média de 3% ao mês.',
            'fatores' => [
                'Otimização do sistema AVAC',
                'Instalação de iluminação LED',
                'Sensores de presença em salas',
                'Consciencialização da comunidade',
            ],
            'projecao' => 'Com a tendência atual, estima-se uma poupança de 9.600 kWh até ao final do ano.',
        ],
        'graficos' => [
            'energia' => [
                'titulo' => 'Evolução do Consumo Energético',
                'descricao' => 'Consumo mensal e poupança acumulada (kWh)',
                'titulo_periodo' => 'Consumo por Período',
                'legenda_principal' => 'Consumo (kWh)',
                'legenda_secundaria' => 'Poupança (kWh)',
                'escala' => ['2800', '2100', '1400', '700'],
                'min_principal' => 700,
                'max_principal' => 2800,
                'min_secundaria' => 0,
                'max_secundaria' => 900,
                'evolucao' => [
                    ['mes' => 'Jan', 'principal' => 2800, 'secundaria' => 0, 'barra' => 100],
                    ['mes' => 'Fev', 'principal' => 2630, 'secundaria' => 170, 'barra' => 94],
                    ['mes' => 'Mar', 'principal' => 2460, 'secundaria' => 340, 'barra' => 88],
                    ['mes' => 'Abr', 'principal' => 2300, 'secundaria' => 500, 'barra' => 82],
                    ['mes' => 'Mai', 'principal' => 2160, 'secundaria' => 640, 'barra' => 77],
                    ['mes' => 'Jun', 'principal' => 2016, 'secundaria' => 800, 'barra' => 72],
                ],
                'tendencia' => [
                    'estado' => 'Decrescente',
                    'descricao' => 'O consumo tem diminuído consistentemente nos últimos 6 meses, com uma redução média de 3% ao mês.',
                    'fatores' => ['Otimização do sistema AVAC', 'Instalação de iluminação LED', 'Sensores de presença em salas', 'Consciencialização da comunidade'],
                    'projecao' => 'Com a tendência atual, estima-se uma poupança de 9.600 kWh até ao final do ano.',
                ],
            ],
        ],
        'edificios' => [
            ['edificio' => 'Edifício A', 'energia_kwh' => 780],
            ['edificio' => 'Edifício B', 'energia_kwh' => 690],
            ['edificio' => 'Edifício C', 'energia_kwh' => 546],
        ],
    ];
}

function pontosGraficoSustentabilidade(array $evolucao, string $campo, float $min = 700, float $max = 2800): string
{
    $xInicial = 75;
    $xPasso = 178;
    $yTopo = 30;
    $altura = 285;
    $pontos = [];

    foreach (array_values($evolucao) as $indice => $linha) {
        $valor = (float) $linha[$campo];
        $valorLimitado = max($min, min($max, $valor));
        $x = $xInicial + ($xPasso * $indice);
        $y = $yTopo + (($max - $valorLimitado) / ($max - $min) * $altura);
        $pontos[] = round($x) . ',' . round($y);
    }

    return implode(' ', $pontos);
}

