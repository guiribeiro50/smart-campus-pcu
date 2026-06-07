<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_espacos');

$msg = '';
$erro = '';

function espacoTemDependencias(PDO $conexao, string $tipo, int $id): ?string
{
    if ($id <= 0) {
        return null;
    }

    $verificacoes = match ($tipo) {
        'sala' => [
            ["SELECT COUNT(*) FROM reserva WHERE tipo_recurso = 'sala' AND recurso_id = ? AND estado IN ('confirmada', 'ativa')", 'reservas ativas'],
            ["SELECT COUNT(*) FROM sensor_sustentabilidade WHERE recurso_tipo = 'sala' AND recurso_id = ?", 'sensores associados'],
            ["SELECT COUNT(*) FROM equipamento WHERE sala_id = ?", 'equipamentos associados'],
        ],
        'laboratorio' => [
            ["SELECT COUNT(*) FROM reserva WHERE tipo_recurso = 'laboratorio' AND recurso_id = ? AND estado IN ('confirmada', 'ativa')", 'reservas ativas'],
            ["SELECT COUNT(*) FROM sensor_sustentabilidade WHERE recurso_tipo = 'laboratorio' AND recurso_id = ?", 'sensores associados'],
            ["SELECT COUNT(*) FROM equipamento WHERE laboratorio_id = ?", 'equipamentos associados'],
        ],
        'equipamento' => [
            ["SELECT COUNT(*) FROM reserva WHERE tipo_recurso = 'equipamento' AND recurso_id = ? AND estado IN ('confirmada', 'ativa')", 'reservas ativas'],
        ],
        default => [],
    };

    foreach ($verificacoes as [$sql, $motivo]) {
        $stmt = $conexao->prepare($sql);
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return "Não é possível remover: existem {$motivo}.";
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    if (isset($_POST['delete'])) {
        $tipo = $_POST['tipo_edit'] ?? '';
        $id = (int) ($_POST['item_id'] ?? 0);
        $dependencia = espacoTemDependencias($conexao, $tipo, $id);

        if ($dependencia) {
            $erro = $dependencia;
        } elseif ($tipo === 'sala' && $id > 0) {
            $conexao->prepare('DELETE FROM sala WHERE id = ?')->execute([$id]);
            $msg = 'Sala removida com sucesso.';
        } elseif ($tipo === 'laboratorio' && $id > 0) {
            $conexao->prepare('DELETE FROM laboratorio WHERE id = ?')->execute([$id]);
            $msg = 'Laboratório removido com sucesso.';
        } elseif ($tipo === 'equipamento' && $id > 0) {
            $conexao->prepare('DELETE FROM equipamento WHERE id = ?')->execute([$id]);
            $msg = 'Equipamento removido com sucesso.';
        }
    }

    if (isset($_POST['update'])) {
        $tipo = $_POST['tipo_edit'] ?? '';
        $id = (int) ($_POST['item_id'] ?? 0);

        if ($tipo === 'sala' && $id > 0) {
            $nome = trim($_POST['nome'] ?? '');
            if ($nome === '') {
                $erro = 'Indique o nome da sala.';
            } elseif (salaExisteComNome($conexao, $nome, $id)) {
                $erro = 'Ja existe uma sala com esse nome.';
            } else {
                $stmt = $conexao->prepare(
                    'UPDATE sala SET nome = ?, edificio = ?, piso = ?, capacidade = ?, descricao = ?, ativo = ? WHERE id = ?'
                );
                $stmt->execute([
                    $nome,
                    trim($_POST['edificio'] ?? ''),
                    (int) ($_POST['piso'] ?? 0),
                    (int) ($_POST['capacidade'] ?? 0),
                    trim($_POST['descricao'] ?? ''),
                    (int) ($_POST['ativo'] ?? 0) === 1 ? 1 : 0,
                    $id,
                ]);
                $msg = 'Sala atualizada com sucesso.';
            }
        } elseif ($tipo === 'laboratorio' && $id > 0) {
            $stmt = $conexao->prepare(
                'UPDATE laboratorio SET nome = ?, edificio = ?, piso = ?, capacidade = ?, descricao = ?, area_cientifica = ?, ativo = ? WHERE id = ?'
            );
            $stmt->execute([
                trim($_POST['nome'] ?? ''),
                trim($_POST['edificio'] ?? ''),
                (int) ($_POST['piso'] ?? 0),
                (int) ($_POST['capacidade'] ?? 0),
                trim($_POST['descricao'] ?? ''),
                trim($_POST['area'] ?? ''),
                (int) ($_POST['ativo'] ?? 0) === 1 ? 1 : 0,
                $id,
            ]);
            $msg = 'Laboratório atualizado com sucesso.';
        } elseif ($tipo === 'equipamento' && $id > 0) {
            $nome = trim($_POST['nome'] ?? '');
            $tipoEquip = trim($_POST['tipo'] ?? '');
            $localizacao = trim($_POST['localizacao'] ?? '');
            $recurso = obterRecursoPorValor($conexao, $localizacao);

            if (!$recurso) {
                $erro = 'Localizacao invalida. Selecione uma sala ou laboratorio.';
            } else {
                $stmt = $conexao->prepare(
                    'UPDATE equipamento SET nome = ?, tipo = ?, sala_id = ?, laboratorio_id = ?, numero_serie = ? WHERE id = ?'
                );
                $stmt->execute([
                    $nome,
                    $tipoEquip,
                    $recurso['tipo'] === 'sala' ? $recurso['id'] : null,
                    $recurso['tipo'] === 'laboratorio' ? $recurso['id'] : null,
                    trim($_POST['numero'] ?? ''),
                    $id,
                ]);
                $msg = 'Equipamento atualizado com sucesso.';
            }
        }
    }

    if (isset($_POST['tipo']) && !isset($_POST['update']) && !isset($_POST['delete'])) {
        $tipo = $_POST['tipo'] ?? '';

        if ($tipo === 'sala') {
            $nomeSala = trim($_POST['nome_sala_lab'] ?? '');
            if ($nomeSala === '') {
                $erro = 'Indique o nome da sala.';
            } elseif (salaExisteComNome($conexao, $nomeSala)) {
                $erro = 'Ja existe uma sala com esse nome.';
            } else {
                $stmt = $conexao->prepare(
                    'INSERT INTO sala (nome, edificio, piso, capacidade, descricao, ativo) VALUES (?, ?, ?, ?, ?, 1)'
                );
                $stmt->execute([
                    $nomeSala,
                    trim($_POST['edificio'] ?? ''),
                    (int) ($_POST['piso'] ?? 0),
                    (int) ($_POST['capacidade'] ?? 0),
                    trim($_POST['descricao'] ?? ''),
                ]);
                $msg = 'Sala registada com sucesso.';
            }
        } elseif ($tipo === 'laboratorio') {
            $stmt = $conexao->prepare(
                'INSERT INTO laboratorio (nome, edificio, piso, capacidade, descricao, area_cientifica, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                trim($_POST['nome_sala_lab'] ?? ''),
                trim($_POST['edificio'] ?? ''),
                (int) ($_POST['piso'] ?? 0),
                (int) ($_POST['capacidade'] ?? 0),
                trim($_POST['descricao'] ?? ''),
                trim($_POST['area_cientifica'] ?? ''),
            ]);
            $msg = 'Laboratório registado com sucesso.';
        } elseif ($tipo === 'equipamento') {
            $localizacao = trim($_POST['localizacao'] ?? '');
            $recurso = obterRecursoPorValor($conexao, $localizacao);

            if (!$recurso) {
                $erro = 'Localizacao invalida. Selecione uma sala ou laboratorio.';
            } else {
                $stmt = $conexao->prepare(
                    'INSERT INTO equipamento (nome, tipo, sala_id, laboratorio_id, numero_serie, ativo) VALUES (?, ?, ?, ?, ?, 1)'
                );
                $stmt->execute([
                    trim($_POST['nome_equipamento'] ?? ''),
                    trim($_POST['tipo_equip'] ?? ''),
                    $recurso['tipo'] === 'sala' ? $recurso['id'] : null,
                    $recurso['tipo'] === 'laboratorio' ? $recurso['id'] : null,
                    trim($_POST['numero_serie'] ?? ''),
                ]);
                $msg = 'Equipamento registado com sucesso.';
            }
        }
    }
}

function salaExisteComNome(PDO $conexao, string $nome, ?int $ignorarId = null): bool
{
    if ($nome === '') {
        return false;
    }

    $sql = 'SELECT id FROM sala WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?))';
    $params = [$nome];

    if ($ignorarId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $ignorarId;
    }

    $sql .= ' LIMIT 1';
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

function recursosLocalizacaoDisponiveis(PDO $conexao): array
{
    $recursos = [];

    foreach ($conexao->query("SELECT id, nome, edificio, piso FROM sala WHERE ativo = 1 ORDER BY nome") as $sala) {
        $recursos[] = [
            'valor' => 'sala:' . (int) $sala['id'],
            'label' => 'Sala - ' . $sala['nome'] . ' (' . $sala['edificio'] . ', Piso ' . $sala['piso'] . ')',
        ];
    }

    foreach ($conexao->query("SELECT id, nome, edificio, piso FROM laboratorio WHERE ativo = 1 ORDER BY nome") as $laboratorio) {
        $recursos[] = [
            'valor' => 'laboratorio:' . (int) $laboratorio['id'],
            'label' => 'Laboratorio - ' . $laboratorio['nome'] . ' (' . $laboratorio['edificio'] . ', Piso ' . $laboratorio['piso'] . ')',
        ];
    }

    return $recursos;
}

function obterRecursoPorValor(PDO $conexao, string $valor): ?array
{
    if (!str_contains($valor, ':')) {
        return null;
    }

    [$tipo, $id] = explode(':', $valor, 2);
    $tipo = $tipo === 'laboratorio' ? 'laboratorio' : 'sala';

    if (!ctype_digit($id)) {
        return null;
    }

    $stmt = $conexao->prepare("SELECT id FROM {$tipo} WHERE id = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([(int) $id]);

    return $stmt->fetchColumn() ? ['tipo' => $tipo, 'id' => (int) $id] : null;
}

function obterRecursoPorNome(PDO $conexao, string $nome): ?array
{
    if ($nome === '') {
        return null;
    }

    $stmt = $conexao->prepare('SELECT id FROM sala WHERE nome = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$nome]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return ['tipo' => 'sala', 'id' => (int) $id];
    }

    $stmt = $conexao->prepare('SELECT id FROM laboratorio WHERE nome = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$nome]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return ['tipo' => 'laboratorio', 'id' => (int) $id];
    }

    return null;
}

$localizacoesDisponiveis = recursosLocalizacaoDisponiveis($conexao);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Espaço – PCU UPT</title>
    <link rel="stylesheet" href="assets/css/sidebar-admin.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/page-layout.css">
    <link rel="stylesheet" href="assets/css/registar-espaco.css">
</head>
<body>

<?php include 'sidebar-admin.php'; ?>

<main class="main-content">

    <div class="page-header">
        <h1>Registar Espaço</h1>
        <p>Gestão de salas, laboratórios e equipamentos do campus</p>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="alerta alerta-sucesso"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="gestao-grid">

        <section class="overview-section">
            <h2>Novo Registo</h2>
            <p class="section-subtitle">Adicionar um recurso ao campus</p>

            <form method="POST" class="form-espaco" id="formRegisto">
                <?= csrfField() ?>
                <label for="tipo">Tipo de recurso</label>
                <select name="tipo" id="tipo" onchange="toggleCampos()" required>
                    <option value="sala">Sala</option>
                    <option value="laboratorio">Laboratório</option>
                    <option value="equipamento">Equipamento</option>
                </select>

                <div id="sala_lab_box" class="campos-grupo">
                    <label for="nome_sala_lab">Nome</label>
                    <input type="text" id="nome_sala_lab" name="nome_sala_lab" placeholder="Ex: Sala 101">

                    <label for="edificio">Edifício</label>
                    <input type="text" id="edificio" name="edificio" placeholder="Ex: Bloco A">

                    <label for="piso">Piso</label>
                    <input type="number" id="piso" name="piso" placeholder="Ex: 1">

                    <label for="capacidade">Capacidade</label>
                    <input type="number" id="capacidade" name="capacidade" placeholder="Ex: 30">

                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Descrição opcional"></textarea>

                    <div id="area_cientifica_box" style="display:none;">
                        <label for="area_cientifica">Área científica</label>
                        <input type="text" id="area_cientifica" name="area_cientifica" placeholder="Ex: Engenharia Informática">
                    </div>
                </div>

                <div id="equipamento_box" class="campos-grupo" style="display:none;">
                    <label for="nome_equipamento">Nome do equipamento</label>
                    <input type="text" id="nome_equipamento" name="nome_equipamento" placeholder="Ex: Projetor 4K">

                    <label for="tipo_equip">Tipo</label>
                    <input type="text" id="tipo_equip" name="tipo_equip" placeholder="Ex: Multimédia">

                    <label for="localizacao">Localização (sala ou lab)</label>
                    <select id="localizacao" name="localizacao" required>
                        <option value="">Selecionar localizacao...</option>
                        <?php foreach ($localizacoesDisponiveis as $localizacao): ?>
                            <option value="<?= htmlspecialchars($localizacao['valor'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($localizacao['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="numero_serie">Número de série</label>
                    <input type="text" id="numero_serie" name="numero_serie" placeholder="Opcional">
                </div>

                <button type="submit">Registar</button>
            </form>
        </section>

        <section class="overview-section">
            <h2>Alterar ou Remover</h2>
            <p class="section-subtitle">Editar recursos existentes</p>

            <form method="POST" class="form-espaco" id="formEditar">
                <?= csrfField() ?>
                <label for="tipo_edit">Tipo</label>
                <select name="tipo_edit" id="tipo_edit" onchange="loadEditOptions()">
                    <option value="sala">Sala</option>
                    <option value="laboratorio">Laboratório</option>
                    <option value="equipamento">Equipamento</option>
                </select>

                <label for="item_id">Recurso</label>
                <select name="item_id" id="item_id" onchange="loadItemData()" required>
                    <option value="">Selecionar...</option>
                </select>

                <div id="edit_fields"></div>

                <div class="form-actions">
                    <button type="submit" name="update" value="1">Guardar alterações</button>
                    <button type="submit" name="delete" value="1" class="btn-delete" onclick="return confirm('Eliminar este recurso?')">Eliminar</button>
                </div>
            </form>
        </section>

    </div>

</main>

<script>
function toggleCampos() {
    const tipo = document.getElementById('tipo').value;
    const salaLab = document.getElementById('sala_lab_box');
    const area = document.getElementById('area_cientifica_box');
    const equip = document.getElementById('equipamento_box');
    const camposSalaLab = salaLab.querySelectorAll('input, textarea');
    const camposEquipamento = equip.querySelectorAll('input, select');

    if (tipo === 'equipamento') {
        salaLab.style.display = 'none';
        equip.style.display = 'grid';
        camposSalaLab.forEach((campo) => campo.disabled = true);
        camposEquipamento.forEach((campo) => campo.disabled = false);
        document.getElementById('localizacao').required = true;
    } else {
        salaLab.style.display = 'grid';
        equip.style.display = 'none';
        area.style.display = (tipo === 'laboratorio') ? 'grid' : 'none';
        camposSalaLab.forEach((campo) => campo.disabled = false);
        camposEquipamento.forEach((campo) => campo.disabled = true);
        document.getElementById('localizacao').required = false;
    }
}

function loadEditOptions() {
    const tipo = document.getElementById('tipo_edit').value;
    fetch('recursos-listar-opcoes.php?tipo=' + encodeURIComponent(tipo))
        .then((r) => r.text())
        .then((data) => {
            document.getElementById('item_id').innerHTML = data;
            document.getElementById('edit_fields').innerHTML = '';
            if (document.getElementById('item_id').value) loadItemData();
        });
}

function loadItemData() {
    const tipo = document.getElementById('tipo_edit').value;
    const id = document.getElementById('item_id').value;
    if (!id) return;

    fetch('recursos-obter-form.php?tipo=' + encodeURIComponent(tipo) + '&id=' + encodeURIComponent(id))
        .then((r) => r.text())
        .then((data) => {
            document.getElementById('edit_fields').innerHTML = data;
        });
}

toggleCampos();
loadEditOptions();
</script>
</body>
</html>
