<?php
session_start();
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('acesso_lss');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/monitorizacao-dados.php';
require_once __DIR__ . '/alertas-dados.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'linhas' => [['texto' => 'Método não permitido.', 'classe' => 't-error']]]);
    exit;
}

validarCsrf();

if (empty($_SESSION['utilizador_id'])) {
    echo json_encode(['sucesso' => false, 'linhas' => [['texto' => 'Sessão expirada. Faça login novamente.', 'classe' => 't-error']]]);
    exit;
}

$comando = trim((string) ($_POST['comando'] ?? ''));
if ($comando === '') {
    echo json_encode(['sucesso' => false, 'linhas' => [['texto' => 'Comando vazio.', 'classe' => 't-error']]]);
    exit;
}

date_default_timezone_set('Europe/Lisbon');

$partes = preg_split('/\s+/', $comando);
$verb = strtoupper((string) ($partes[0] ?? ''));
$args = array_slice($partes, 1);

try {
    $pdo = obterLigacaoBD();
    $utilizadorId = (int) $_SESSION['utilizador_id'];
    $pdo->exec("UPDATE reserva SET estado = 'expirada' WHERE estado = 'confirmada' AND data_fim <= NOW()");

    $linhas = match ($verb) {
        'AJUDA' => lssCmdAjuda(),
        'STATUS' => lssCmdStatus($pdo),
        'LIMPAR' => [['texto' => '__LIMPAR__', 'classe' => '']],
        'RESERVA' => lssCmdReserva($pdo, $utilizadorId, $args),
        'CANCELAR_RESERVA' => lssCmdCancelarReserva($pdo, $utilizadorId, $args),
        'LISTAR_RESERVAS' => lssCmdListarReservas($pdo, $args),
        'SENSOR' => lssCmdSensor($pdo, $args),
        'ALERTA' => lssCmdAlerta($pdo, $args),
        'ENERGIA' => lssCmdEnergia($pdo, $args),
        'MOBILIDADE' => lssCmdMobilidade($pdo, $args),
        default => [['texto' => "Comando desconhecido: '{$comando}'. Digite AJUDA.", 'classe' => 't-error']],
    };

    echo json_encode(['sucesso' => true, 'linhas' => $linhas], JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    error_log('Erro no terminal LSS: ' . $erro->getMessage());
    echo json_encode([
        'sucesso' => false,
        'linhas' => [['texto' => 'Erro ao executar comando.', 'classe' => 't-error']],
    ], JSON_UNESCAPED_UNICODE);
}

function lssCmdAjuda(): array
{
    return [
        ['texto' => 'Comandos: STATUS, AJUDA, LIMPAR, RESERVA, CANCELAR_RESERVA, LISTAR_RESERVAS, SENSOR, ALERTA, ENERGIA, MOBILIDADE', 'classe' => 't-info'],
    ];
}

function lssCmdStatus(PDO $pdo): array
{
    $iot = dadosIotBaseDados($pdo);
    $alertas = dadosAlertasBaseDados($pdo);
    $stmtReservas = $pdo->query("SELECT COUNT(*) FROM reserva WHERE estado = 'confirmada' AND data_fim > NOW()");
    $reservasAtivas = (int) $stmtReservas->fetchColumn();

    return [
        ['texto' => '══ Estado do Sistema PCU ══════════════════', 'classe' => 't-info'],
        ['texto' => 'Servidor: Online', 'classe' => 't-success'],
        ['texto' => 'Base de Dados: Conectado', 'classe' => 't-success'],
        ['texto' => 'Reservas ativas: ' . $reservasAtivas, 'classe' => 't-data'],
        ['texto' => 'Sensores IoT: ' . $iot['criticos'] . ' críticos, ' . $iot['avisos'] . ' avisos', 'classe' => 't-warn'],
        ['texto' => 'Alertas pendentes: ' . $alertas['resumo']['pendentes'], 'classe' => 't-data'],
        ['texto' => 'Última sync: ' . date('H:i:s'), 'classe' => 't-data'],
    ];
}

function lssCmdReserva(PDO $pdo, int $utilizadorId, array $args): array
{
    if (count($args) < 2) {
        return [
            ['texto' => 'Uso: RESERVA <sala> <periodo>', 'classe' => 't-warn'],
            ['texto' => 'Exemplo: RESERVA Sala_102 HOJE', 'classe' => 't-info'],
        ];
    }

    $periodo = strtoupper(implode(' ', array_slice($args, 1)));
    $nomeRecurso = str_replace('_', ' ', $args[0]);
    $agora = time();

    $inicio = match ($periodo) {
        'AMANHA' => strtotime('tomorrow 09:00'),
        'SEMANAL' => strtotime('next monday 09:00'),
        default => strtotime('today 09:00'),
    };
    $fim = $inicio + 7200;

    if ($inicio < $agora) {
        $inicio = $agora + 300;
        $fim = $inicio + 7200;
    }

    $stmt = $pdo->prepare(
        "SELECT id, 'sala' AS tipo FROM sala WHERE ativo = 1 AND (REPLACE(nome, ' ', '_') = ? OR nome LIKE ?)
         UNION
         SELECT id, 'laboratorio' AS tipo FROM laboratorio WHERE ativo = 1 AND (REPLACE(nome, ' ', '_') = ? OR nome LIKE ?)
         LIMIT 1"
    );
    $like = '%' . $nomeRecurso . '%';
    $stmt->execute([$args[0], $like, $args[0], $like]);
    $recurso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recurso) {
        return [['texto' => 'Espaço não encontrado: ' . $nomeRecurso, 'classe' => 't-error']];
    }

    $dataInicio = date('Y-m-d H:i:s', $inicio);
    $dataFim = date('Y-m-d H:i:s', $fim);

    $stmtCheck = $pdo->prepare(
        "SELECT id FROM reserva
         WHERE recurso_id = ? AND tipo_recurso = ? AND estado = 'confirmada'
           AND data_inicio < ? AND data_fim > ?
         LIMIT 1"
    );
    $stmtCheck->execute([(int) $recurso['id'], $recurso['tipo'], $dataFim, $dataInicio]);
    if ($stmtCheck->fetch()) {
        return [['texto' => 'Espaço indisponível no período solicitado.', 'classe' => 't-error']];
    }

    $stmtInsert = $pdo->prepare(
        "INSERT INTO reserva (utilizador_id, tipo_recurso, recurso_id, data_inicio, data_fim, finalidade, estado)
         VALUES (?, ?, ?, ?, ?, 'Reserva via Terminal LSS', 'confirmada')"
    );
    $stmtInsert->execute([$utilizadorId, $recurso['tipo'], (int) $recurso['id'], $dataInicio, $dataFim]);
    $id = (int) $pdo->lastInsertId();

    return [
        ['texto' => '✓ Reserva criada com sucesso', 'classe' => 't-success'],
        ['texto' => 'ID: RES-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT), 'classe' => 't-val'],
        ['texto' => 'Recurso: ' . $nomeRecurso, 'classe' => 't-val'],
        ['texto' => 'Início: ' . date('d/m/Y H:i', $inicio), 'classe' => 't-val'],
        ['texto' => 'Estado: Confirmada', 'classe' => 't-success'],
    ];
}

function lssCmdCancelarReserva(PDO $pdo, int $utilizadorId, array $args): array
{
    if (empty($args[0])) {
        return [['texto' => 'Uso: CANCELAR_RESERVA <id_reserva>', 'classe' => 't-warn']];
    }

    $idRaw = strtoupper($args[0]);
    $id = (int) preg_replace('/\D/', '', $idRaw);

    if ($id <= 0) {
        return [['texto' => 'ID de reserva inválido.', 'classe' => 't-error']];
    }

    $stmt = $pdo->prepare(
        "UPDATE reserva SET estado = 'cancelada'
         WHERE id = ? AND utilizador_id = ? AND estado = 'confirmada' AND data_fim > NOW()"
    );
    $stmt->execute([$id, $utilizadorId]);

    if ($stmt->rowCount() === 0) {
        return [['texto' => 'Reserva não encontrada ou já encerrada.', 'classe' => 't-error']];
    }

    return [['texto' => '✓ Reserva RES-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT) . ' cancelada', 'classe' => 't-success']];
}

function lssCmdListarReservas(PDO $pdo, array $args): array
{
    $edificio = $args[0] ?? '';
    $sql = "SELECT r.id, COALESCE(s.nome, l.nome) AS recurso, r.data_inicio, r.data_fim, u.nome AS utilizador, r.estado,
                   COALESCE(s.edificio, l.edificio, '') AS edificio
            FROM reserva r
            LEFT JOIN sala s ON r.tipo_recurso = 'sala' AND s.id = r.recurso_id
            LEFT JOIN laboratorio l ON r.tipo_recurso = 'laboratorio' AND l.id = r.recurso_id
            INNER JOIN utilizador u ON u.id = r.utilizador_id
            WHERE r.estado = 'confirmada' AND r.data_fim > NOW()";

    if ($edificio !== '') {
        $sql .= " AND (s.edificio LIKE :ed OR l.edificio LIKE :ed)";
    }

    $sql .= ' ORDER BY r.data_inicio';

    $stmt = $pdo->prepare($sql);
    if ($edificio !== '') {
        $stmt->execute(['ed' => '%' . str_replace('_', ' ', $edificio) . '%']);
    } else {
        $stmt->execute();
    }

    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$reservas) {
        return [['texto' => 'Nenhuma reserva ativa encontrada.', 'classe' => 't-warn']];
    }

    $linhas = [['texto' => 'ID | Recurso | Início | Utilizador | Estado', 'classe' => 't-key']];
    foreach ($reservas as $reserva) {
        $linhas[] = [
            'texto' => 'RES-' . str_pad((string) $reserva['id'], 3, '0', STR_PAD_LEFT)
                . ' | ' . $reserva['recurso']
                . ' | ' . date('d/m H:i', strtotime($reserva['data_inicio']))
                . ' | ' . $reserva['utilizador']
                . ' | ' . ucfirst($reserva['estado']),
            'classe' => 't-data',
        ];
    }

    $linhas[] = ['texto' => 'Total: ' . count($reservas) . ' reserva(s)', 'classe' => 't-info'];
    return $linhas;
}

function lssCmdSensor(PDO $pdo, array $args): array
{
    if (empty($args[0])) {
        return [['texto' => 'Uso: SENSOR <acao> <localizacao>', 'classe' => 't-warn']];
    }

    $acao = strtoupper($args[0]);
    $local = implode(' ', array_slice($args, 1));
    $like = '%' . str_replace('_', ' ', $local) . '%';

    $stmt = $pdo->prepare(
        "SELECT ss.*, COALESCE(s.nome, l.nome) AS local_nome
         FROM sensor_sustentabilidade ss
         LEFT JOIN sala s ON ss.recurso_tipo = 'sala' AND s.id = ss.recurso_id
         LEFT JOIN laboratorio l ON ss.recurso_tipo = 'laboratorio' AND l.id = ss.recurso_id
         WHERE ss.codigo LIKE ? OR s.nome LIKE ? OR l.nome LIKE ?
         LIMIT 1"
    );
    $stmt->execute([$like, $like, $like]);
    $sensor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sensor && $acao !== 'LEITURA') {
        return [['texto' => 'Sensor não encontrado: ' . $local, 'classe' => 't-error']];
    }

    if ($acao === 'ATIVAR' || $acao === 'DESATIVAR') {
        $ativo = $acao === 'ATIVAR' ? 1 : 0;
        $estado = $acao === 'ATIVAR' ? 'ativo' : 'inativo';
        $stmtUp = $pdo->prepare('UPDATE sensor_sustentabilidade SET ativo = ?, estado_tecnico = ? WHERE id = ?');
        $stmtUp->execute([$ativo, $estado, (int) $sensor['id']]);
        return [['texto' => '✓ Sensor ' . $sensor['codigo'] . ' ' . ($acao === 'ATIVAR' ? 'ativado' : 'desativado'), 'classe' => 't-success']];
    }

    $iot = dadosIotBaseDados($pdo);
    $dados = null;
    foreach ($iot['sensores'] as $item) {
        if ($sensor && $item['codigo'] === $sensor['codigo']) {
            $dados = $item;
            break;
        }
    }

    if (!$dados) {
        return [['texto' => 'Sem leituras para: ' . $local, 'classe' => 't-warn']];
    }

    return [
        ['texto' => 'Sensor: ' . $dados['codigo'], 'classe' => 't-val'],
        ['texto' => 'Localização: ' . $dados['localizacao'], 'classe' => 't-val'],
        ['texto' => 'Valor: ' . $dados['valor_formatado'] . ' ' . $dados['unidade'], 'classe' => 't-val'],
        ['texto' => 'Estado: ' . $dados['estado_label'], 'classe' => $dados['estado'] === 'critical' ? 't-error' : ($dados['estado'] === 'warning' ? 't-warn' : 't-success')],
    ];
}

function lssCmdAlerta(PDO $pdo, array $args): array
{
    $dados = dadosAlertasBaseDados($pdo);
    $pendentes = array_filter($dados['alertas'], fn (array $a): bool => $a['estado'] === 'pendente');

    if (count($args) >= 3) {
        return [['texto' => 'Configuração de alertas disponível na gestão de sensores.', 'classe' => 't-info']];
    }

    if (!$pendentes) {
        return [['texto' => 'Sem alertas pendentes.', 'classe' => 't-success']];
    }

    $linhas = [['texto' => '══ Alertas Ativos ═══════════════════════', 'classe' => 't-info']];
    foreach ($pendentes as $alerta) {
        $classe = $alerta['nivel'] === 'critico' ? 't-error' : 't-warn';
        $linhas[] = ['texto' => '[' . strtoupper($alerta['nivel']) . '] ' . $alerta['titulo'] . ' — ' . $alerta['valor'], 'classe' => $classe];
    }

    return $linhas;
}

function lssCmdEnergia(PDO $pdo, array $args): array
{
    if (strtoupper($args[0] ?? '') !== 'CONSUMO') {
        return [['texto' => 'Uso: ENERGIA Consumo <local>', 'classe' => 't-warn']];
    }

    $local = implode(' ', array_slice($args, 1));
    $iot = dadosIotBaseDados($pdo);
    $consumos = [];

    foreach ($iot['sensores'] as $sensor) {
        if ($sensor['tipo'] !== 'energia' || !is_numeric($sensor['valor'])) {
            continue;
        }
        if ($local !== '' && $local !== 'Todos' && !str_contains(strtolower($sensor['localizacao'] . ' ' . $sensor['edificio']), strtolower(str_replace('_', ' ', $local)))) {
            continue;
        }
        $consumos[] = $sensor['localizacao'] . ' — ' . $sensor['valor_formatado'] . ' kW';
    }

    if (!$consumos) {
        return [['texto' => 'Sem dados de consumo para: ' . ($local ?: 'Todos'), 'classe' => 't-warn']];
    }

    $linhas = [['texto' => 'Consumo energético:', 'classe' => 't-info']];
    foreach ($consumos as $linha) {
        $linhas[] = ['texto' => $linha, 'classe' => 't-data'];
    }

    return $linhas;
}

function lssCmdMobilidade(PDO $pdo, array $args): array
{
    $sub = strtoupper($args[0] ?? '');
    $zona = implode(' ', array_slice($args, 1));

    if ($sub === 'BICICLETAS') {
        $stmt = $pdo->query("SELECT codigo, estado FROM bicicleta WHERE ativo = 1 ORDER BY codigo");
        $linhas = [['texto' => 'Bicicletas:', 'classe' => 't-info']];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $linhas[] = ['texto' => $row['codigo'] . ' — ' . $row['estado'], 'classe' => 't-data'];
        }
        return $linhas ?: [['texto' => 'Sem bicicletas registadas.', 'classe' => 't-warn']];
    }

    if ($sub === 'TROTINETES') {
        $stmt = $pdo->query("SELECT codigo, estado FROM trotinete WHERE ativo = 1 ORDER BY codigo");
        $linhas = [['texto' => 'Trotinetes:', 'classe' => 't-info']];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $linhas[] = ['texto' => $row['codigo'] . ' — ' . $row['estado'], 'classe' => 't-data'];
        }
        return $linhas ?: [['texto' => 'Sem trotinetes registadas.', 'classe' => 't-warn']];
    }

    if ($sub === 'ESTACIONAMENTO') {
        $sql = "SELECT l.nome AS zona, COUNT(le.id) AS total
                FROM localidade l
                LEFT JOIN lugar_estacionamento le ON le.localidade_id = l.id
                GROUP BY l.id, l.nome";
        $stmt = $pdo->query($sql);
        $linhas = [['texto' => 'Estacionamento:', 'classe' => 't-info']];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($zona !== '' && !str_contains(strtolower($row['zona']), strtolower(str_replace('_', ' ', $zona)))) {
                continue;
            }
            $linhas[] = [
                'texto' => $row['zona'] . ' — Lugares: ' . (int) $row['total'],
                'classe' => 't-data',
            ];
        }
        return count($linhas) > 1 ? $linhas : [['texto' => 'Sem dados de estacionamento.', 'classe' => 't-warn']];
    }

    return [['texto' => 'Uso: MOBILIDADE [ESTACIONAMENTO|BICICLETAS|TROTINETES] [zona]', 'classe' => 't-warn']];
}
