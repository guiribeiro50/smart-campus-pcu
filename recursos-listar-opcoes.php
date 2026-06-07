<?php
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

$tipo = $_GET['tipo'];

if ($tipo === "sala") {
    $res = $conn->query("SELECT id, nome FROM sala ORDER BY nome");

} elseif ($tipo === "laboratorio") {
    $res = $conn->query("SELECT id, nome FROM laboratorio ORDER BY nome");

} else {
    $res = $conn->query("SELECT id, nome FROM equipamento ORDER BY nome");
}

echo '<option value="">Selecionar...</option>';
while ($row = $res->fetch_assoc()) {
    echo '<option value="' . (int) $row['id'] . '">' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '</option>';
}
