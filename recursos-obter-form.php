<?php
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

$tipo = $_GET['tipo'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    exit;
}

function hRecursoForm(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function opcoesLocalizacaoEquipamento(mysqli $conn, string $selecionado): string
{
    $html = "<select name='localizacao' required>";
    $html .= "<option value=''>Selecionar localizacao...</option>";

    $salas = $conn->query("SELECT id, nome, edificio, piso FROM sala WHERE ativo = 1 ORDER BY nome");
    while ($sala = $salas->fetch_assoc()) {
        $valor = 'sala:' . (int) $sala['id'];
        $label = 'Sala - ' . $sala['nome'] . ' (' . $sala['edificio'] . ', Piso ' . $sala['piso'] . ')';
        $selected = $valor === $selecionado ? ' selected' : '';
        $html .= "<option value='" . hRecursoForm($valor) . "'{$selected}>" . hRecursoForm($label) . '</option>';
    }

    $laboratorios = $conn->query("SELECT id, nome, edificio, piso FROM laboratorio WHERE ativo = 1 ORDER BY nome");
    while ($laboratorio = $laboratorios->fetch_assoc()) {
        $valor = 'laboratorio:' . (int) $laboratorio['id'];
        $label = 'Laboratorio - ' . $laboratorio['nome'] . ' (' . $laboratorio['edificio'] . ', Piso ' . $laboratorio['piso'] . ')';
        $selected = $valor === $selecionado ? ' selected' : '';
        $html .= "<option value='" . hRecursoForm($valor) . "'{$selected}>" . hRecursoForm($label) . '</option>';
    }

    return $html . '</select>';
}

if ($tipo === 'sala') {
    $stmt = $conn->prepare('SELECT * FROM sala WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) exit;

    echo "
    <input name='nome' value='" . hRecursoForm($r['nome']) . "'>
    <input name='edificio' value='" . hRecursoForm($r['edificio']) . "'>
    <input name='piso' value='" . hRecursoForm($r['piso']) . "'>
    <input name='capacidade' value='" . hRecursoForm($r['capacidade']) . "'>
    <input name='descricao' value='" . hRecursoForm($r['descricao']) . "'>
    <select name='ativo'>
        <option value='1' " . ((int) $r['ativo'] === 1 ? 'selected' : '') . ">Ativo</option>
        <option value='0' " . ((int) $r['ativo'] === 0 ? 'selected' : '') . ">Inativo</option>
    </select>";
    exit;
}

if ($tipo === 'laboratorio') {
    $stmt = $conn->prepare('SELECT * FROM laboratorio WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) exit;

    echo "
    <input name='nome' value='" . hRecursoForm($r['nome']) . "'>
    <input name='edificio' value='" . hRecursoForm($r['edificio']) . "'>
    <input name='piso' value='" . hRecursoForm($r['piso']) . "'>
    <input name='capacidade' value='" . hRecursoForm($r['capacidade']) . "'>
    <input name='descricao' value='" . hRecursoForm($r['descricao']) . "'>
    <input name='area' value='" . hRecursoForm($r['area_cientifica']) . "'>
    <select name='ativo'>
        <option value='1' " . ((int) $r['ativo'] === 1 ? 'selected' : '') . ">Ativo</option>
        <option value='0' " . ((int) $r['ativo'] === 0 ? 'selected' : '') . ">Inativo</option>
    </select>";
    exit;
}

$stmt = $conn->prepare('SELECT * FROM equipamento WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
if (!$r) exit;

$selecionado = !empty($r['laboratorio_id'])
    ? 'laboratorio:' . (int) $r['laboratorio_id']
    : (!empty($r['sala_id']) ? 'sala:' . (int) $r['sala_id'] : '');

echo "
<input name='nome' value='" . hRecursoForm($r['nome']) . "'>
<input name='tipo' value='" . hRecursoForm($r['tipo']) . "'>"
    . opcoesLocalizacaoEquipamento($conn, $selecionado) .
"<input name='numero' value='" . hRecursoForm($r['numero_serie']) . "'>";
