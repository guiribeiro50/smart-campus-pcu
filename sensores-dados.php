<?php
require_once __DIR__ . '/config/database.php';

if (!function_exists('h')) {
    function h(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}

function sensoresGestao(): array
{
    try {
        return ['sensores' => array_values(sensoresBaseDados(obterLigacaoBD()))];
    } catch (Throwable $erro) {
        return ['sensores' => array_values(sensoresDemo())];
    }
}

function sensoresBaseDados(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT ss.*,
                sala.nome AS sala_nome, sala.edificio AS sala_edificio, sala.piso AS sala_piso,
                laboratorio.nome AS laboratorio_nome, laboratorio.edificio AS laboratorio_edificio, laboratorio.piso AS laboratorio_piso
         FROM sensor_sustentabilidade ss
         LEFT JOIN sala ON ss.recurso_tipo = 'sala' AND ss.recurso_id = sala.id
         LEFT JOIN laboratorio ON ss.recurso_tipo = 'laboratorio' AND ss.recurso_id = laboratorio.id
         ORDER BY ss.codigo"
    );

    $sensores = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $sensor = normalizarSensorBaseDados($linha);
        $sensores[$sensor['codigo']] = $sensor;
    }

    return $sensores;
}

function normalizarSensorBaseDados(array $linha): array
{
    $tipo = tipoSensorInterface((string) ($linha['tipo'] ?? 'temperatura'));
    $recursoTipo = (string) ($linha['recurso_tipo'] ?? 'sala');
    $prefixo = $recursoTipo === 'laboratorio' ? 'laboratorio' : 'sala';
    $nome = trim((string) ($linha[$prefixo . '_nome'] ?? 'Recurso por definir'));
    $edificio = normalizarEdificioChave((string) ($linha[$prefixo . '_edificio'] ?? ''));
    $piso = (string) ($linha[$prefixo . '_piso'] ?? '');
    $estadoTecnico = (string) ($linha['estado_tecnico'] ?? 'ativo');
    $ativo = (int) ($linha['ativo'] ?? 1) === 1;
    $estado = $ativo ? normalizarEstadoSensor($estadoTecnico) : 'inativo';

    return [
        'codigo' => (string) ($linha['codigo'] ?? ''),
        'tipo' => $tipo,
        'tipo_label' => labelTipoSensor($tipo),
        'unidade' => unidadeTipoSensor($tipo),
        'recurso_tipo' => $recursoTipo,
        'recurso_id' => (string) ($linha['recurso_id'] ?? ''),
        'localizacao' => $nome,
        'edificio' => $edificio,
        'edificio_label' => labelEdificioSensor($edificio),
        'piso' => $piso !== '' ? $piso : '-',
        'estado' => $estado,
        'firmware' => (string) ($linha['firmware'] ?? 'v2.3.1'),
        'intervalo_recolha' => (string) ($linha['intervalo_recolha'] ?? '30'),
        'data_instalacao' => (string) ($linha['data_instalacao'] ?? ''),
    ];
}

function sensoresDemo(): array
{
    $base = [
        'S-A-101-T' => ['tipo' => 'temperatura', 'localizacao' => 'Sala 101', 'edificio' => 'a', 'piso' => '1', 'estado' => 'ativo'],
        'S-A-101-C' => ['tipo' => 'co2', 'localizacao' => 'Sala 101', 'edificio' => 'a', 'piso' => '1', 'estado' => 'ativo'],
        'S-A-101-H' => ['tipo' => 'humidade', 'localizacao' => 'Sala 101', 'edificio' => 'a', 'piso' => '1', 'estado' => 'ativo'],
        'S-A-201-T' => ['tipo' => 'temperatura', 'localizacao' => 'Sala 201', 'edificio' => 'a', 'piso' => '2', 'estado' => 'inativo'],
        'S-B-301-T' => ['tipo' => 'temperatura', 'localizacao' => 'Lab Informática 3', 'edificio' => 'b', 'piso' => '3', 'estado' => 'ativo'],
        'S-B-301-C' => ['tipo' => 'co2', 'localizacao' => 'Lab Informática 3', 'edificio' => 'b', 'piso' => '3', 'estado' => 'ativo'],
        'S-C-L1-E' => ['tipo' => 'energia', 'localizacao' => 'Biblioteca', 'edificio' => 'c', 'piso' => '0', 'estado' => 'ativo'],
        'S-C-L2-E' => ['tipo' => 'energia', 'localizacao' => 'Laboratórios', 'edificio' => 'c', 'piso' => '2', 'estado' => 'manutencao'],
    ];

    $sensores = [];
    foreach ($base as $codigo => $sensor) {
        $tipo = $sensor['tipo'];
        $sensores[$codigo] = [
            'codigo' => $codigo,
            'tipo' => $tipo,
            'tipo_label' => labelTipoSensor($tipo),
            'unidade' => unidadeTipoSensor($tipo),
            'recurso_tipo' => str_contains(strtolower($sensor['localizacao']), 'lab') ? 'laboratorio' : 'sala',
            'recurso_id' => '',
            'localizacao' => $sensor['localizacao'],
            'edificio' => $sensor['edificio'],
            'edificio_label' => labelEdificioSensor($sensor['edificio']),
            'piso' => $sensor['piso'],
            'estado' => $sensor['estado'],
            'firmware' => 'v2.3.1',
            'intervalo_recolha' => '30',
            'data_instalacao' => '',
        ];
    }

    return $sensores;
}

function obterSensorGestao(?string $codigo): ?array
{
    if ($codigo === null || trim($codigo) === '') {
        return null;
    }

    foreach (sensoresGestao()['sensores'] as $sensor) {
        if ($sensor['codigo'] === $codigo) {
            return $sensor;
        }
    }

    return null;
}

function recursosSensorDisponiveis(): array
{
    try {
        $pdo = obterLigacaoBD();
        $recursos = [];

        foreach ([['sala', 'Sala', 'sala'], ['laboratorio', 'Laboratório', 'laboratorio']] as [$tipo, $label, $tabela]) {
            $stmt = $pdo->query("SELECT id, nome, edificio, piso FROM {$tabela} WHERE ativo = 1 ORDER BY edificio, piso, nome");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $edificio = normalizarEdificioChave((string) ($linha['edificio'] ?? ''));
                $recursos[] = [
                    'valor' => $tipo . ':' . $linha['id'],
                    'id' => (string) $linha['id'],
                    'tipo' => $tipo,
                    'tipo_label' => $label,
                    'nome' => (string) $linha['nome'],
                    'edificio' => labelEdificioSensor($edificio),
                    'edificio_key' => $edificio,
                    'piso' => (string) ($linha['piso'] ?? ''),
                ];
            }
        }

        return $recursos;
    } catch (Throwable $erro) {
        return [];
    }
}

function obterRecursoSensor(string $tipo, mixed $valor): ?array
{
    $tipo = $tipo === 'laboratorio' ? 'laboratorio' : 'sala';
    $id = (string) $valor;

    if (str_contains($id, ':')) {
        [$tipoValor, $idValor] = explode(':', $id, 2);
        $tipo = $tipoValor === 'laboratorio' ? 'laboratorio' : 'sala';
        $id = $idValor;
    }

    if (!ctype_digit($id)) {
        return null;
    }

    try {
        $stmt = obterLigacaoBD()->prepare("SELECT id FROM {$tipo} WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        if ($stmt->fetch()) {
            return ['tipo' => $tipo, 'id' => (int) $id];
        }
    } catch (Throwable $erro) {
    }

    return null;
}

function resumoSensoresGestao(array $sensores): array
{
    $total = count($sensores);
    $ativos = count(array_filter($sensores, fn (array $sensor): bool => $sensor['estado'] === 'ativo'));
    $inativos = count(array_filter($sensores, fn (array $sensor): bool => $sensor['estado'] === 'inativo'));
    $manutencao = count(array_filter($sensores, fn (array $sensor): bool => $sensor['estado'] === 'manutencao'));

    return [
        'total_formatado' => formatarNumeroSensores($total),
        'ativos_formatado' => formatarNumeroSensores($ativos),
        'inativos_formatado' => formatarNumeroSensores($inativos),
        'manutencao_formatado' => formatarNumeroSensores($manutencao),
    ];
}

function distribuicaoSensoresGestao(array $sensores): array
{
    $tipos = ['temperatura', 'co2', 'humidade', 'energia'];
    return array_map(function (string $tipo) use ($sensores): array {
        return [
            'label' => labelTipoSensor($tipo),
            'total' => (string) count(array_filter($sensores, fn (array $sensor): bool => $sensor['tipo'] === $tipo)),
            'icone' => iconeTipoSensor($tipo),
            'classe' => classeTipoSensor($tipo),
        ];
    }, $tipos);
}

function textoPesquisaSensor(array $sensor): string
{
    return strtolower(implode(' ', [$sensor['codigo'], $sensor['tipo_label'], $sensor['localizacao'], $sensor['edificio_label'], $sensor['estado']]));
}

function tipoSensorInterface(string $tipo): string
{
    return match ($tipo) {
        'qualidade_ar', 'co2' => 'co2',
        'consumo_energetico', 'energia' => 'energia',
        'humidade' => 'humidade',
        default => 'temperatura',
    };
}

function labelTipoSensor(string $tipo): string
{
    return match ($tipo) {
        'co2' => 'Qualidade do Ar (CO2)',
        'humidade' => 'Humidade',
        'energia' => 'Energia',
        default => 'Temperatura',
    };
}

function unidadeTipoSensor(string $tipo): string
{
    return match ($tipo) {
        'co2' => 'ppm',
        'humidade' => '%',
        'energia' => 'kW',
        default => '°C',
    };
}

function iconeTipoSensor(string $tipo): string
{
    return match ($tipo) {
        'co2' => 'wind',
        'humidade' => 'droplets',
        'energia' => 'zap',
        default => 'thermometer',
    };
}

function classeTipoSensor(string $tipo): string
{
    return match ($tipo) {
        'co2' => 'air',
        'humidade' => 'humidity',
        'energia' => 'energy',
        default => 'temperature',
    };
}

function normalizarEstadoSensor(string $estado): string
{
    return match (strtolower($estado)) {
        'inativo' => 'inativo',
        'manutencao', 'manutenção' => 'manutencao',
        default => 'ativo',
    };
}

function labelEstadoSensor(string $estado): string
{
    return match ($estado) {
        'inativo' => 'Inativo',
        'manutencao' => 'Manutenção',
        default => 'Ativo',
    };
}

function classeEstadoSensor(string $estado): string
{
    return match ($estado) {
        'inativo' => 'badge-inactive',
        'manutencao' => 'badge-maintenance',
        default => 'badge-active',
    };
}

function normalizarEdificioChave(string $edificio): string
{
    $edificio = strtolower(trim($edificio));
    if ($edificio === 'a' || str_ends_with($edificio, ' a')) return 'a';
    if ($edificio === 'b' || str_ends_with($edificio, ' b')) return 'b';
    if ($edificio === 'c' || str_ends_with($edificio, ' c')) return 'c';
    return $edificio !== '' ? $edificio : 'a';
}

function labelEdificioSensor(string $edificio): string
{
    return match ($edificio) {
        'a' => 'Edifício A',
        'b' => 'Edifício B',
        'c' => 'Edifício C',
        default => $edificio !== '' ? ucfirst($edificio) : 'Edifício A',
    };
}

function formatarNumeroSensores(int $valor): string
{
    return number_format($valor, 0, ',', ' ');
}
