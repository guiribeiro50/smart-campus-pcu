<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('acesso_mobilidade');
header('Content-Type: application/json');

$utilizador_id = (int)($_SESSION['utilizador_id'] ?? 1);

$conexao->exec("
    UPDATE reserva_bicicleta
    SET estado = 'concluida'
    WHERE estado = 'ativa' AND fim IS NOT NULL AND fim <= NOW()
");

$conexao->exec("
    UPDATE reserva_trotinete
    SET estado = 'concluida'
    WHERE estado = 'ativa' AND fim IS NOT NULL AND fim <= NOW()
");

$conexao->exec("
    UPDATE bicicleta b
    INNER JOIN reserva_bicicleta rb ON rb.bicicleta_id = b.id AND rb.estado = 'ativa'
    SET b.estado = 'em_uso'
    WHERE b.estado = 'disponivel' AND b.ativo = 1
");

$conexao->exec("
    UPDATE trotinete t
    INNER JOIN reserva_trotinete rt ON rt.trotinete_id = t.id AND rt.estado = 'ativa'
    SET t.estado = 'em_uso'
    WHERE t.estado = 'disponivel' AND t.ativo = 1
");

$conexao->exec("
    UPDATE bicicleta
    SET estado = 'disponivel'
    WHERE estado = 'em_uso'
    AND id NOT IN (
        SELECT bicicleta_id FROM reserva_bicicleta WHERE estado = 'ativa'
    )
");

$conexao->exec("
    UPDATE trotinete
    SET estado = 'disponivel'
    WHERE estado = 'em_uso'
    AND id NOT IN (
        SELECT trotinete_id FROM reserva_trotinete WHERE estado = 'ativa'
    )
");

$r = $conexao->query("SELECT COUNT(*) AS total FROM bicicleta WHERE estado = 'disponivel' AND ativo = 1");
$total_bicicletas = (int)($r->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$r = $conexao->query("SELECT COUNT(*) AS total FROM trotinete WHERE estado = 'disponivel' AND ativo = 1");
$total_trotinetes = (int)($r->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$r_lugares = $conexao->query("
    SELECT l.id, l.nome,
           COUNT(le.id) AS total_lugares
    FROM localidade l
    LEFT JOIN lugar_estacionamento le ON le.localidade_id = l.id
    GROUP BY l.id, l.nome
");
$lugares_por_zona = $r_lugares->fetchAll(PDO::FETCH_ASSOC);

$r_lugares_detalhe = $conexao->query("
    SELECT le.id, le.codigo, l.id AS localidade_id
    FROM lugar_estacionamento le
    JOIN localidade l ON l.id = le.localidade_id
    ORDER BY l.nome, le.codigo
");
$lugares_detalhe = $r_lugares_detalhe->fetchAll(PDO::FETCH_ASSOC);

$lugares_por_zona_map = [];
foreach ($lugares_detalhe as $lugar) {
    $lugares_por_zona_map[$lugar['localidade_id']][] = ['codigo' => $lugar['codigo']];
}
foreach ($lugares_por_zona as &$zona) {
    $zona['lugares'] = $lugares_por_zona_map[$zona['id']] ?? [];
}
unset($zona);

$r = $conexao->query("
    SELECT l.id AS localidade_id, l.nome AS localidade_nome,
           COUNT(b.id) AS total,
           SUM(CASE WHEN b.estado = 'disponivel' THEN 1 ELSE 0 END) AS disponiveis,
           SUM(CASE WHEN b.estado = 'em_uso' THEN 1 ELSE 0 END) AS em_uso
    FROM localidade l
    LEFT JOIN bicicleta b ON b.localidade_id = l.id AND b.ativo = 1
    GROUP BY l.id, l.nome
    ORDER BY l.nome
");
$bicicletas_por_zona = $r->fetchAll(PDO::FETCH_ASSOC);

$r = $conexao->query("
    SELECT l.id AS localidade_id, l.nome AS localidade_nome,
           COUNT(t.id) AS total,
           SUM(CASE WHEN t.estado = 'disponivel' THEN 1 ELSE 0 END) AS disponiveis,
           SUM(CASE WHEN t.estado = 'em_uso' THEN 1 ELSE 0 END) AS em_uso
    FROM localidade l
    LEFT JOIN trotinete t ON t.localidade_id = l.id AND t.ativo = 1
    GROUP BY l.id, l.nome
    ORDER BY l.nome
");
$trotinetes_por_zona = $r->fetchAll(PDO::FETCH_ASSOC);

$stmt_ativa = $conexao->prepare("
    SELECT
        (SELECT COUNT(*) FROM reserva_bicicleta WHERE utilizador_id = ? AND estado = 'ativa')
      + (SELECT COUNT(*) FROM reserva_trotinete WHERE utilizador_id = ? AND estado = 'ativa')
");
$stmt_ativa->execute([$utilizador_id, $utilizador_id]);
$tem_reserva_ativa = (int)$stmt_ativa->fetchColumn() > 0;

echo json_encode([
    'total_bicicletas'    => $total_bicicletas,
    'total_trotinetes'    => $total_trotinetes,
    'bicicletas_por_zona' => $bicicletas_por_zona,
    'trotinetes_por_zona' => $trotinetes_por_zona,
    'lugares_por_zona'    => $lugares_por_zona,
    'tem_reserva_ativa'   => $tem_reserva_ativa,
]);
