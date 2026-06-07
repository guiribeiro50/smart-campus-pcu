<?php
require_once __DIR__ . '/config/database.php';

if (!function_exists('h')) {
    function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

function dadosDashboardUtilizador(array $utilizadorSessao): array
{
    date_default_timezone_set('Europe/Lisbon');

    $perfil = normalizarPerfilDashboardUtilizador((string) ($utilizadorSessao['perfil'] ?? 'estudante'));
    $dados = dadosDashboardUtilizadorDemo($perfil);
    $dados['utilizador'] = identidadeDashboardUtilizador($utilizadorSessao, $perfil);
    $dados['perfil'] = $perfil;

    try {
        $pdo = obterLigacaoBD();
        $pdo->exec("UPDATE reserva SET estado = 'expirada' WHERE estado = 'confirmada' AND data_fim <= NOW()");
        $dados['notificacoes'] = notificacoesDashboardUtilizadorBaseDados($pdo);
        $dados['resumo'] = resumoDashboardUtilizadorBaseDados($pdo);
        $dados['reservas'] = reservasDashboardUtilizadorBaseDados($pdo, (int) ($utilizadorSessao['id'] ?? 0));
        $dados['ocupacao'] = ocupacaoDashboardUtilizadorBaseDados($pdo);
    } catch (Throwable $erro) {
    }

    return $dados;
}

function dadosDashboardUtilizadorDemo(string $perfil = 'estudante'): array
{
    $perfil = normalizarPerfilDashboardUtilizador($perfil);
    $docente = $perfil === 'docente';

    return [
        'perfil' => $perfil,
        'utilizador' => [
            'nome' => $docente ? 'Prof. João Silva' : 'Ana Costa',
            'email' => $docente ? 'docente@upt.pt' : 'aluno@upt.pt',
            'role' => $docente ? 'Docente' : 'Estudante',
            'avatar' => $docente ? 'P' : 'A',
            'boas_vindas' => $docente ? 'Bem-vindo, Prof. João Silva' : 'Bem-vindo, Ana Costa',
        ],
        'notificacoes' => $docente
            ? ['criticas' => 1, 'avisos' => 1]
            : ['criticas' => 0, 'avisos' => 1],
        'resumo' => [
            [
                'classe' => 'rooms',
                'icone' => 'icon-building',
                'titulo' => 'Salas Livres Agora',
                'valor' => '12',
                'descricao' => 'De 45 salas disponíveis',
            ],
            [
                'classe' => 'parking',
                'icone' => 'icon-car',
                'titulo' => 'Estacionamento',
                'valor' => '38',
                'descricao' => 'Lugares livres',
            ],
            [
                'classe' => 'temperature',
                'icone' => 'icon-thermometer',
                'titulo' => 'Temperatura Média',
                'valor' => '21°C',
                'descricao' => 'Conforto térmico ideal',
            ],
            [
                'classe' => 'air',
                'icone' => 'icon-wind',
                'titulo' => 'Qualidade do Ar',
                'valor' => 'Boa',
                'descricao' => 'CO₂: 420 ppm',
                'estado' => 'Normal',
            ],
        ],
        'reservas' => [
            [
                'espaco' => 'Sala 202 - Edifício A',
                'periodo' => 'Hoje, 14:00 - 16:00',
                'estado' => 'Confirmada',
            ],
            [
                'espaco' => 'Lab Informática 3',
                'periodo' => 'Amanhã, 10:00 - 12:00',
                'estado' => 'Confirmada',
            ],
        ],
        'ocupacao' => [
            ['nome' => 'Edifício A', 'percentagem' => 68],
            ['nome' => 'Edifício B', 'percentagem' => 45],
            ['nome' => 'Biblioteca', 'percentagem' => 82],
            ['nome' => 'Laboratórios', 'percentagem' => 34],
        ],
    ];
}

function normalizarPerfilDashboardUtilizador(string $perfil): string
{
    $perfil = strtolower(trim($perfil));

    return $perfil === 'docente' ? 'docente' : 'estudante';
}

function identidadeDashboardUtilizador(array $utilizadorSessao, string $perfil): array
{
    $nome = trim((string) ($utilizadorSessao['nome'] ?? 'Utilizador'));
    $email = trim((string) ($utilizadorSessao['email'] ?? ''));
    $role = $perfil === 'docente' ? 'Docente' : 'Estudante';
    $avatar = mb_strtoupper(mb_substr($nome !== '' ? $nome : $role, 0, 1, 'UTF-8'), 'UTF-8');

    return [
        'nome' => $nome !== '' ? $nome : $role,
        'email' => $email,
        'role' => $role,
        'avatar' => $avatar,
        'boas_vindas' => 'Bem-vindo, ' . ($nome !== '' ? $nome : $role),
    ];
}

function notificacoesDashboardUtilizadorBaseDados(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT tipo, COUNT(*) AS total
         FROM alerta
         WHERE estado = 'pendente'
         GROUP BY tipo"
    );

    $notificacoes = ['criticas' => 0, 'avisos' => 0];

    foreach ($stmt->fetchAll() as $linha) {
        if ($linha['tipo'] === 'critico') {
            $notificacoes['criticas'] = (int) $linha['total'];
        } elseif ($linha['tipo'] === 'aviso') {
            $notificacoes['avisos'] = (int) $linha['total'];
        }
    }

    return $notificacoes;
}

function resumoDashboardUtilizadorBaseDados(PDO $pdo): array
{
    $salas = resumoSalasLivresDashboardUtilizador($pdo);
    $estacionamento = resumoEstacionamentoDashboardUtilizador($pdo);
    $temperatura = resumoTemperaturaDashboardUtilizador($pdo);
    $qualidadeAr = resumoQualidadeArDashboardUtilizador($pdo);

    return [$salas, $estacionamento, $temperatura, $qualidadeAr];
}

function resumoSalasLivresDashboardUtilizador(PDO $pdo): array
{
    $total = (int) $pdo->query("SELECT COUNT(*) AS total FROM sala WHERE ativo = 1")->fetch()['total'];
    $ocupadas = 0;

    if ($total > 0) {
        $stmt = $pdo->query(
            "SELECT COUNT(DISTINCT recurso_id) AS total
             FROM reserva
             WHERE tipo_recurso = 'sala'
               AND estado = 'confirmada'
               AND data_inicio <= NOW()
               AND data_fim > NOW()"
        );
        $ocupadas = (int) $stmt->fetch()['total'];
    }

    $livres = max(0, $total - $ocupadas);

    return [
        'classe' => 'rooms',
        'icone' => 'icon-building',
        'titulo' => 'Salas Livres Agora',
        'valor' => (string) $livres,
        'descricao' => $total > 0 ? 'De ' . $total . ' salas disponíveis' : 'Sem salas registadas',
    ];
}

function resumoEstacionamentoDashboardUtilizador(PDO $pdo): array
{
    $total = (int) $pdo->query("SELECT COUNT(*) AS total FROM lugar_estacionamento")->fetch()['total'];

    return [
        'classe' => 'parking',
        'icone' => 'icon-car',
        'titulo' => 'Estacionamento',
        'valor' => (string) $total,
        'descricao' => $total === 1 ? 'Lugar registado' : 'Lugares registados',
    ];
}

function resumoTemperaturaDashboardUtilizador(PDO $pdo): array
{
    $valor = mediaUltimasLeiturasDashboardUtilizador(
        $pdo,
        'valor_temperatura',
        'temperatura'
    );

    return [
        'classe' => 'temperature',
        'icone' => 'icon-thermometer',
        'titulo' => 'Temperatura Média',
        'valor' => $valor === null ? 'Sem leitura' : formatarNumeroDashboardUtilizador($valor, 1) . '°C',
        'descricao' => $valor === null ? 'Sem leituras reais' : descricaoTemperaturaDashboardUtilizador($valor),
    ];
}

function resumoQualidadeArDashboardUtilizador(PDO $pdo): array
{
    $valor = mediaUltimasLeiturasDashboardUtilizador(
        $pdo,
        'valor_qualidade_ar',
        'qualidade_ar'
    );

    $estado = estadoQualidadeArDashboardUtilizador($valor);

    return [
        'classe' => 'air',
        'icone' => 'icon-wind',
        'titulo' => 'Qualidade do Ar',
        'valor' => $valor === null ? 'Sem leitura' : $estado['valor'],
        'descricao' => $valor === null ? 'Sem leituras reais' : 'CO₂: ' . formatarNumeroDashboardUtilizador($valor, 0) . ' ppm',
        'estado' => $valor === null ? 'Sem dados' : $estado['estado'],
    ];
}

function mediaUltimasLeiturasDashboardUtilizador(PDO $pdo, string $tabela, string $tipoSensor): ?float
{
    $stmt = $pdo->query(
        "SELECT AVG(v.valor) AS media
         FROM {$tabela} v
         INNER JOIN (
            SELECT sensor_sustentabilidade_id, MAX(id) AS ultima_id
            FROM {$tabela}
            GROUP BY sensor_sustentabilidade_id
         ) ultimas ON ultimas.ultima_id = v.id
         INNER JOIN sensor_sustentabilidade s ON s.id = v.sensor_sustentabilidade_id
         WHERE s.tipo = '{$tipoSensor}'
           AND s.ativo = 1
           AND s.estado_tecnico = 'ativo'"
    );

    $media = $stmt->fetch()['media'] ?? null;

    return $media === null ? null : (float) $media;
}

function descricaoTemperaturaDashboardUtilizador(float $valor): string
{
    if ($valor < 18) {
        return 'Temperatura abaixo do ideal';
    }

    if ($valor > 26) {
        return 'Temperatura acima do ideal';
    }

    return 'Conforto térmico ideal';
}

function estadoQualidadeArDashboardUtilizador(?float $valor): array
{
    if ($valor === null) {
        return ['valor' => 'Sem leitura', 'estado' => 'Sem dados'];
    }

    if ($valor <= 800) {
        return ['valor' => 'Boa', 'estado' => 'Normal'];
    }

    if ($valor <= 1000) {
        return ['valor' => 'Moderada', 'estado' => 'Aviso'];
    }

    return ['valor' => 'Má', 'estado' => 'Crítico'];
}

function reservasDashboardUtilizadorBaseDados(PDO $pdo, int $utilizadorId): array
{
    if ($utilizadorId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare(
        "SELECT r.tipo_recurso, r.data_inicio, r.data_fim, r.estado,
                sala.nome AS sala_nome, sala.edificio AS sala_edificio,
                laboratorio.nome AS laboratorio_nome, laboratorio.edificio AS laboratorio_edificio,
                equipamento.nome AS equipamento_nome
         FROM reserva r
         LEFT JOIN sala ON r.tipo_recurso = 'sala' AND r.recurso_id = sala.id
         LEFT JOIN laboratorio ON r.tipo_recurso = 'laboratorio' AND r.recurso_id = laboratorio.id
         LEFT JOIN equipamento ON r.tipo_recurso = 'equipamento' AND r.recurso_id = equipamento.id
         WHERE r.utilizador_id = :utilizador_id
           AND r.estado = 'confirmada'
           AND r.data_fim > NOW()
         ORDER BY r.data_inicio ASC
         LIMIT 3"
    );
    $stmt->execute(['utilizador_id' => $utilizadorId]);

    return array_map('normalizarReservaDashboardUtilizador', $stmt->fetchAll());
}

function normalizarReservaDashboardUtilizador(array $linha): array
{
    $tipo = (string) $linha['tipo_recurso'];
    $prefixo = $tipo === 'laboratorio' ? 'laboratorio' : 'sala';
    $nome = (string) ($linha[$prefixo . '_nome'] ?? '');
    $edificio = normalizarEdificioLabelDashboardUtilizador((string) ($linha[$prefixo . '_edificio'] ?? ''));

    if ($tipo === 'equipamento') {
        $nome = (string) ($linha['equipamento_nome'] ?? 'Equipamento');
        $edificio = '';
    }

    if ($nome === '') {
        $nome = ucfirst($tipo);
    }

    return [
        'espaco' => trim($nome . ($edificio !== '' ? ' - ' . $edificio : '')),
        'periodo' => formatarPeriodoReservaDashboardUtilizador($linha['data_inicio'], $linha['data_fim']),
        'estado' => 'Confirmada',
    ];
}

function formatarPeriodoReservaDashboardUtilizador(?string $inicio, ?string $fim): string
{
    $inicioTs = strtotime((string) $inicio);
    $fimTs = strtotime((string) $fim);

    if (!$inicioTs || !$fimTs) {
        return 'Data por definir';
    }

    $dataInicio = date('Y-m-d', $inicioTs);
    $hoje = date('Y-m-d');
    $amanha = date('Y-m-d', strtotime('+1 day'));

    $dia = match ($dataInicio) {
        $hoje => 'Hoje',
        $amanha => 'Amanhã',
        default => date('d/m/Y', $inicioTs),
    };

    return $dia . ', ' . date('H:i', $inicioTs) . ' - ' . date('H:i', $fimTs);
}

function ocupacaoDashboardUtilizadorBaseDados(PDO $pdo): array
{
    $totais = recursosPorGrupoDashboardUtilizador($pdo);
    $ocupados = recursosOcupadosPorGrupoDashboardUtilizador($pdo);

    $ordem = ['a' => 'Edifício A', 'b' => 'Edifício B', 'c' => 'Edifício C', 'laboratorios' => 'Laboratórios'];
    $resultado = [];

    foreach ($ordem as $chave => $nome) {
        $total = $totais[$chave] ?? 0;
        $ocupado = $ocupados[$chave] ?? 0;
        $percentagem = $total > 0 ? (int) round(($ocupado / $total) * 100) : 0;

        $resultado[] = [
            'nome' => $nome,
            'percentagem' => max(0, min(100, $percentagem)),
        ];
    }

    return $resultado;
}

function recursosPorGrupoDashboardUtilizador(PDO $pdo): array
{
    $totais = ['a' => 0, 'b' => 0, 'c' => 0, 'laboratorios' => 0];

    $salas = $pdo->query("SELECT edificio, COUNT(*) AS total FROM sala WHERE ativo = 1 GROUP BY edificio")->fetchAll();
    foreach ($salas as $linha) {
        $chave = chaveEdificioDashboardUtilizador((string) $linha['edificio']);
        if (isset($totais[$chave])) {
            $totais[$chave] += (int) $linha['total'];
        }
    }

    $laboratorios = $pdo->query("SELECT edificio, COUNT(*) AS total FROM laboratorio WHERE ativo = 1 GROUP BY edificio")->fetchAll();
    foreach ($laboratorios as $linha) {
        $chave = chaveEdificioDashboardUtilizador((string) $linha['edificio']);
        if (isset($totais[$chave])) {
            $totais[$chave] += (int) $linha['total'];
        }
        $totais['laboratorios'] += (int) $linha['total'];
    }

    return $totais;
}

function recursosOcupadosPorGrupoDashboardUtilizador(PDO $pdo): array
{
    $ocupados = ['a' => 0, 'b' => 0, 'c' => 0, 'laboratorios' => 0];

    $stmt = $pdo->query(
        "SELECT r.tipo_recurso,
                sala.edificio AS sala_edificio,
                laboratorio.edificio AS laboratorio_edificio,
                COUNT(DISTINCT CONCAT(r.tipo_recurso, '-', r.recurso_id)) AS total
         FROM reserva r
         LEFT JOIN sala ON r.tipo_recurso = 'sala' AND r.recurso_id = sala.id
         LEFT JOIN laboratorio ON r.tipo_recurso = 'laboratorio' AND r.recurso_id = laboratorio.id
         WHERE r.estado = 'confirmada'
           AND r.tipo_recurso IN ('sala', 'laboratorio')
           AND r.data_inicio <= NOW()
           AND r.data_fim > NOW()
         GROUP BY r.tipo_recurso, sala.edificio, laboratorio.edificio"
    );

    foreach ($stmt->fetchAll() as $linha) {
        $edificio = $linha['tipo_recurso'] === 'laboratorio'
            ? (string) $linha['laboratorio_edificio']
            : (string) $linha['sala_edificio'];
        $chave = chaveEdificioDashboardUtilizador($edificio);

        if (isset($ocupados[$chave])) {
            $ocupados[$chave] += (int) $linha['total'];
        }

        if ($linha['tipo_recurso'] === 'laboratorio') {
            $ocupados['laboratorios'] += (int) $linha['total'];
        }
    }

    return $ocupados;
}

function chaveEdificioDashboardUtilizador(string $edificio): string
{
    $edificio = mb_strtolower(trim($edificio), 'UTF-8');

    if ($edificio === 'a' || str_ends_with($edificio, ' a')) {
        return 'a';
    }

    if ($edificio === 'b' || str_ends_with($edificio, ' b')) {
        return 'b';
    }

    if ($edificio === 'c' || str_ends_with($edificio, ' c')) {
        return 'c';
    }

    return '';
}

function normalizarEdificioLabelDashboardUtilizador(string $edificio): string
{
    return match (chaveEdificioDashboardUtilizador($edificio)) {
        'a' => 'Edifício A',
        'b' => 'Edifício B',
        'c' => 'Edifício C',
        default => trim($edificio),
    };
}

function formatarNumeroDashboardUtilizador(float $valor, int $decimais): string
{
    $formatado = number_format($valor, $decimais, '.', '');

    return $decimais > 0 ? rtrim(rtrim($formatado, '0'), '.') : $formatado;
}
