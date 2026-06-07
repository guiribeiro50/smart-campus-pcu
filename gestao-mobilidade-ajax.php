<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');
header('Content-Type: application/json');

function garantirTabelaRegisto(PDO $conexao): void
{
    try {
        $conexao->exec("
            CREATE TABLE IF NOT EXISTS registo_mobilidade (
                id INT(11) NOT NULL AUTO_INCREMENT,
                utilizador_id INT(11) NOT NULL,
                tipo_recurso ENUM('estacionamento','bicicleta','trotinete') NOT NULL,
                recurso_id INT(11) DEFAULT NULL,
                acao ENUM('criar','editar','remover') NOT NULL,
                detalhes TEXT DEFAULT NULL,
                registado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    } catch (Exception $e) {
    }
}

function registarAcao(PDO $conexao, int $utilizadorId, string $tipo, ?int $recursoId, string $acao, string $detalhes = ''): void
{
    try {
        garantirTabelaRegisto($conexao);
        $stmt = $conexao->prepare("
            INSERT INTO registo_mobilidade (utilizador_id, tipo_recurso, recurso_id, acao, detalhes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$utilizadorId, $tipo, $recursoId, $acao, $detalhes !== '' ? $detalhes : null]);
    } catch (Exception $e) {
    }
}

function proximoCodigo(PDO $conexao, string $tabela, string $prefixo): string
{
    $stmt = $conexao->prepare("SELECT codigo FROM {$tabela} WHERE codigo LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefixo . '%']);
    $ultimo = $stmt->fetchColumn();
    $numero = 1;
    if ($ultimo) {
        $partes = explode('-', $ultimo);
        $last = end($partes);
        if (is_numeric($last)) {
            $numero = (int)$last + 1;
        }
    }
    return $prefixo . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
}

function estadoBadge(string $estado): string
{
    $mapa = [
        'disponivel' => 'badge-disponivel',
        'em_uso'     => 'badge-em-uso',
        'manutencao' => 'badge-manutencao',
    ];
    return $mapa[$estado] ?? 'badge-disponivel';
}

function estadoLabel(string $estado): string
{
    $mapa = [
        'disponivel' => 'Disponível',
        'em_uso'     => 'Em uso',
        'manutencao' => 'Manutenção',
    ];
    return $mapa[$estado] ?? ucfirst($estado);
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$utilizadorId = (int)($_SESSION['utilizador_id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
}

try {
    if ($acao === 'obter') {
        $tipo = $_GET['tipo'] ?? '';
        $id = (int)($_GET['id'] ?? 0);
        if (!$id || !in_array($tipo, ['estacionamento', 'bicicleta', 'trotinete'], true)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetros inválidos']);
            exit;
        }

        if ($tipo === 'estacionamento') {
            $stmt = $conexao->prepare("
                SELECT le.id, le.codigo, le.localidade_id, l.nome AS localidade_nome
                FROM lugar_estacionamento le
                LEFT JOIN localidade l ON l.id = le.localidade_id
                WHERE le.id = ?
            ");
        } elseif ($tipo === 'bicicleta') {
            $stmt = $conexao->prepare("
                SELECT b.id, b.codigo, b.localidade_id, b.estado, b.ativo, l.nome AS localidade_nome
                FROM bicicleta b
                LEFT JOIN localidade l ON l.id = b.localidade_id
                WHERE b.id = ? AND b.ativo = 1
            ");
        } else {
            $stmt = $conexao->prepare("
                SELECT t.id, t.codigo, t.localidade_id, t.estado, t.ativo, l.nome AS localidade_nome
                FROM trotinete t
                LEFT JOIN localidade l ON l.id = t.localidade_id
                WHERE t.id = ? AND t.ativo = 1
            ");
        }

        $stmt->execute([$id]);
        $recurso = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$recurso) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Recurso não encontrado']);
            exit;
        }

        if ($tipo !== 'estacionamento') {
            $recurso['estado_label'] = estadoLabel($recurso['estado']);
            $recurso['badge_class']  = estadoBadge($recurso['estado']);
        }

        echo json_encode(['sucesso' => true, 'recurso' => $recurso, 'tipo' => $tipo]);
        exit;
    }

    if ($acao === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = $_POST['tipo'] ?? '';
        $localidadeId = (int)($_POST['localidade_id'] ?? 0);

        if (!in_array($tipo, ['estacionamento', 'bicicleta', 'trotinete'], true) || !$localidadeId) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
            exit;
        }

        $stmtLoc = $conexao->prepare("SELECT COUNT(*) FROM localidade WHERE id = ?");
        $stmtLoc->execute([$localidadeId]);
        if ((int)$stmtLoc->fetchColumn() === 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Localidade inválida']);
            exit;
        }

        $criados = [];

        if ($tipo === 'estacionamento') {
            $codigo = trim($_POST['codigo'] ?? '');
            if ($codigo === '') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Indique o código do estacionamento']);
                exit;
            }
            $stmtDup = $conexao->prepare("SELECT COUNT(*) FROM lugar_estacionamento WHERE codigo = ?");
            $stmtDup->execute([$codigo]);
            if ((int)$stmtDup->fetchColumn() > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Código de estacionamento já existe']);
                exit;
            }
            $stmt = $conexao->prepare("INSERT INTO lugar_estacionamento (codigo, localidade_id) VALUES (?, ?)");
            $stmt->execute([$codigo, $localidadeId]);
            $novoId = (int)$conexao->lastInsertId();
            $criados[] = $codigo;
            registarAcao($conexao, $utilizadorId, 'estacionamento', $novoId, 'criar', "Código: {$codigo}, Localidade: {$localidadeId}");
        } else {
            $quantidade = max(1, min(50, (int)($_POST['quantidade'] ?? 1)));
            $estado = $_POST['estado'] ?? 'disponivel';
            if (!in_array($estado, ['disponivel', 'manutencao'], true)) {
                $estado = 'disponivel';
            }
            $tabela  = $tipo === 'bicicleta' ? 'bicicleta' : 'trotinete';
            $prefixo = $tipo === 'bicicleta' ? 'BIKE-' : 'TROT-';
            $stmt = $conexao->prepare("INSERT INTO {$tabela} (codigo, localidade_id, estado, ativo) VALUES (?, ?, ?, 1)");

            for ($i = 0; $i < $quantidade; $i++) {
                $codigo = proximoCodigo($conexao, $tabela, $prefixo);
                $stmt->execute([$codigo, $localidadeId, $estado]);
                $novoId = (int)$conexao->lastInsertId();
                $criados[] = $codigo;
                registarAcao($conexao, $utilizadorId, $tipo, $novoId, 'criar', "Código: {$codigo}, Localidade: {$localidadeId}, Estado: {$estado}");
            }
        }

        echo json_encode(['sucesso' => true, 'criados' => $criados]);
        exit;
    }

    if ($acao === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo        = $_POST['tipo'] ?? '';
        $id          = (int)($_POST['id'] ?? 0);
        $localidadeId = (int)($_POST['localidade_id'] ?? 0);
        $codigo      = trim($_POST['codigo'] ?? '');

        if (!$id || !in_array($tipo, ['estacionamento', 'bicicleta', 'trotinete'], true) || !$localidadeId || $codigo === '') {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
            exit;
        }

        if ($tipo === 'estacionamento') {
            $stmtDup = $conexao->prepare("SELECT COUNT(*) FROM lugar_estacionamento WHERE codigo = ? AND id != ?");
            $stmtDup->execute([$codigo, $id]);
            if ((int)$stmtDup->fetchColumn() > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Código já registado']);
                exit;
            }
            $stmt = $conexao->prepare("UPDATE lugar_estacionamento SET codigo = ?, localidade_id = ? WHERE id = ?");
            $stmt->execute([$codigo, $localidadeId, $id]);
            $estadoNovo = '';
        } else {
            $estado = $_POST['estado'] ?? 'disponivel';
            if (!in_array($estado, ['disponivel', 'em_uso', 'manutencao'], true)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Estado inválido']);
                exit;
            }
            $tabela = $tipo === 'bicicleta' ? 'bicicleta' : 'trotinete';
            $stmtAtual = $conexao->prepare("SELECT estado FROM {$tabela} WHERE id = ? AND ativo = 1");
            $stmtAtual->execute([$id]);
            $estadoAtual = $stmtAtual->fetchColumn();
            if ($estadoAtual === false) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Recurso não encontrado']);
                exit;
            }
            if ($estadoAtual === 'em_uso' && $estado !== 'em_uso') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Não é possível alterar o estado de um veículo em uso']);
                exit;
            }
            $stmtDup = $conexao->prepare("SELECT COUNT(*) FROM {$tabela} WHERE codigo = ? AND id != ? AND ativo = 1");
            $stmtDup->execute([$codigo, $id]);
            if ((int)$stmtDup->fetchColumn() > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Código já registado']);
                exit;
            }
            $stmt = $conexao->prepare("UPDATE {$tabela} SET codigo = ?, localidade_id = ?, estado = ? WHERE id = ? AND ativo = 1");
            $stmt->execute([$codigo, $localidadeId, $estado, $id]);
            $estadoNovo = $estado;
        }

        $detalhes = "Código: {$codigo}, Localidade: {$localidadeId}";
        if ($estadoNovo !== '') {
            $detalhes .= ", Estado: {$estadoNovo}";
        }
        registarAcao($conexao, $utilizadorId, $tipo, $id, 'editar', $detalhes);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    if ($acao === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = $_POST['tipo'] ?? '';
        $id   = (int)($_POST['id'] ?? 0);

        if (!$id || !in_array($tipo, ['estacionamento', 'bicicleta', 'trotinete'], true)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
            exit;
        }

        if ($tipo === 'estacionamento') {
            $stmtInfo = $conexao->prepare("SELECT codigo FROM lugar_estacionamento WHERE id = ?");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if (!$info) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Estacionamento não encontrado']);
                exit;
            }
            $stmt = $conexao->prepare("DELETE FROM lugar_estacionamento WHERE id = ?");
            $stmt->execute([$id]);
            registarAcao($conexao, $utilizadorId, 'estacionamento', $id, 'remover', "Código: {$info['codigo']}");
        } else {
            $tabela       = $tipo === 'bicicleta' ? 'bicicleta' : 'trotinete';
            $tabelaReserva = $tipo === 'bicicleta' ? 'reserva_bicicleta' : 'reserva_trotinete';
            $coluna       = $tipo === 'bicicleta' ? 'bicicleta_id' : 'trotinete_id';

            $stmtInfo = $conexao->prepare("SELECT codigo, estado FROM {$tabela} WHERE id = ? AND ativo = 1");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if (!$info) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Veículo não encontrado']);
                exit;
            }
            if ($info['estado'] === 'em_uso') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Não é possível remover um veículo em uso']);
                exit;
            }
            $stmtRes = $conexao->prepare("SELECT COUNT(*) FROM {$tabelaReserva} WHERE {$coluna} = ? AND estado = 'ativa'");
            $stmtRes->execute([$id]);
            if ((int)$stmtRes->fetchColumn() > 0) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Não é possível remover um veículo com reserva ativa']);
                exit;
            }
            $stmt = $conexao->prepare("UPDATE {$tabela} SET ativo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            registarAcao($conexao, $utilizadorId, $tipo, $id, 'remover', "Código: {$info['codigo']}");
        }

        echo json_encode(['sucesso' => true]);
        exit;
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida']);
} catch (Exception $e) {
    if (isset($conexao) && $conexao->inTransaction()) {
        $conexao->rollBack();
    }
    error_log('Erro em gestao-mobilidade-ajax: ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor.']);
}
