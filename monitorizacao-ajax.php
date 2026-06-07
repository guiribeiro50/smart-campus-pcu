<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('acesso_iot');

require_once __DIR__ . '/monitorizacao-dados.php';

ini_set('serialize_precision', '-1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode(dadosIot(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

