<?php
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

header('Content-Type: text/html; charset=utf-8');

$conn->query(
    "UPDATE reserva
     SET estado = 'expirada'
     WHERE estado = 'confirmada'
       AND data_fim <= NOW()"
);

$sql = "
    SELECT
        r.id,
        r.tipo_recurso,
        r.recurso_id,
        r.data_inicio,
        r.data_fim,
        u.nome AS utilizador_nome,
        CASE
            WHEN r.tipo_recurso = 'sala' THEN s.nome
            WHEN r.tipo_recurso = 'laboratorio' THEN l.nome
            WHEN r.tipo_recurso = 'equipamento' THEN e.nome
            ELSE CONCAT('Recurso #', r.recurso_id)
        END AS recurso_nome
    FROM reserva r
    INNER JOIN utilizador u ON u.id = r.utilizador_id
    LEFT JOIN sala s ON r.tipo_recurso = 'sala' AND s.id = r.recurso_id
    LEFT JOIN laboratorio l ON r.tipo_recurso = 'laboratorio' AND l.id = r.recurso_id
    LEFT JOIN equipamento e ON r.tipo_recurso = 'equipamento' AND e.id = r.recurso_id
    WHERE r.estado = 'confirmada'
      AND r.data_fim > NOW()
    ORDER BY r.data_inicio, u.nome
";

$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $label = sprintf(
        '#%d - %s - %s - %s a %s',
        $row['id'],
        $row['utilizador_nome'],
        $row['recurso_nome'],
        date('d/m H:i', strtotime($row['data_inicio'])),
        date('H:i', strtotime($row['data_fim']))
    );

    echo '<option value="' . (int) $row['id'] . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
}
