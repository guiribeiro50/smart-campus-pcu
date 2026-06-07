<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_utilizadores');

header('Content-Type: application/json');

$utilizadorAtual = obterUtilizador();
$utilizadorAtualId = (int) ($utilizadorAtual['id'] ?? 0);
$perfilAtual = normalizarPerfilAuth((string) ($utilizadorAtual['perfil'] ?? ''));

function perfilParaBd(string $perfilUi): string
{
    $mapa = [
        'Administrador' => 'administrador',
        'Docente' => 'docente',
        'Staff' => 'funcionario_administrativo',
        'Staff Administrativo' => 'funcionario_administrativo',
        'Estudante' => 'estudante',
    ];
    return $mapa[$perfilUi] ?? 'estudante';
}

function perfilParaUi(string $perfilBd): string
{
    $mapa = [
        'administrador' => 'Administrador',
        'docente' => 'Docente',
        'funcionario_administrativo' => 'Staff',
        'estudante' => 'Estudante',
    ];
    return $mapa[$perfilBd] ?? 'Estudante';
}

function badgeClassPorPerfil(string $perfilBd): string
{
    $mapa = [
        'administrador' => 'badge-admin',
        'funcionario_administrativo' => 'badge-staff',
        'docente' => 'badge-docente',
        'estudante' => 'badge-student',
    ];
    return $mapa[$perfilBd] ?? 'badge-student';
}

function iniciaisUtilizador(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    foreach (array_slice($partes, 0, 2) as $parte) {
        $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
    }
    return $iniciais ?: 'U';
}

function nomePermissao(string $nome): string
{
    $mapa = [
        'reservar_salas' => 'Reservar salas',
        'reservar_laboratorios' => 'Reservar laboratórios',
        'reservar_equipamentos' => 'Reservar equipamentos',
        'acesso_mobilidade' => 'Acesso mobilidade partilhada',
        'acesso_iot' => 'Acesso IoT',
        'exportar_relatorios' => 'Exportar relatórios',
        'acesso_lss' => 'Acesso LSS',
        'gestao_espacos' => 'Gestão de espaços',
        'gestao_utilizadores' => 'Gestão de utilizadores',
    ];
    return $mapa[$nome] ?? ucfirst(str_replace('_', ' ', $nome));
}

function utilizadorAjaxAlvo(PDO $conexao, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $stmt = $conexao->prepare('SELECT id, perfil FROM utilizador WHERE id = ? AND ativo = 1');
    $stmt->execute([$id]);
    $alvo = $stmt->fetch(PDO::FETCH_ASSOC);

    return $alvo ?: null;
}

function utilizadorAjaxPodeGerirAlvo(array $alvo, string $perfilAtual): bool
{
    return !(($alvo['perfil'] ?? '') === 'administrador' && $perfilAtual !== 'administrador');
}

function utilizadorAjaxNegar(string $mensagem = 'Nao tem permissao para executar esta acao.'): void
{
    echo json_encode(['sucesso' => false, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$atribuidoPor = (int)($_SESSION['utilizador_id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
}

try {
    if ($acao === 'obter') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conexao->prepare("SELECT id, nome, email, login, perfil FROM utilizador WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Utilizador não encontrado']);
            exit;
        }
        if (!utilizadorAjaxPodeGerirAlvo($user, $perfilAtual)) {
            utilizadorAjaxNegar();
        }
        $user['perfil_ui'] = perfilParaUi($user['perfil']);
        echo json_encode(['sucesso' => true, 'utilizador' => $user]);
        exit;
    }

    if ($acao === 'permissoes') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conexao->prepare("SELECT id, nome, email, login, perfil FROM utilizador WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Utilizador não encontrado']);
            exit;
        }

        if (!utilizadorAjaxPodeGerirAlvo($user, $perfilAtual)) {
            utilizadorAjaxNegar();
        }

        $stmtAtivas = $conexao->prepare("
            SELECT up.id, up.permissao_id, up.atribuido_em, p.nome
            FROM utilizador_permissao up
            JOIN permissao p ON p.id = up.permissao_id
            WHERE up.utilizador_id = ?
            ORDER BY p.nome
        ");
        $stmtAtivas->execute([$id]);
        $ativas = $stmtAtivas->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ativas as &$a) {
            $a['label'] = nomePermissao($a['nome']);
        }
        unset($a);

        $stmtDisp = $conexao->prepare("
            SELECT p.id, p.nome
            FROM permissao p
            WHERE p.id NOT IN (
                SELECT permissao_id FROM utilizador_permissao WHERE utilizador_id = ?
            )
            ORDER BY p.nome
        ");
        $stmtDisp->execute([$id]);
        $disponiveis = $stmtDisp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($disponiveis as &$d) {
            $d['label'] = nomePermissao($d['nome']);
        }
        unset($d);

        echo json_encode([
            'sucesso' => true,
            'utilizador' => [
                'id' => (int)$user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'login' => $user['login'],
                'perfil' => $user['perfil'],
                'perfil_ui' => perfilParaUi($user['perfil']),
                'badge_class' => badgeClassPorPerfil($user['perfil']),
                'iniciais' => iniciaisUtilizador($user['nome']),
            ],
            'permissoes_ativas' => $ativas,
            'permissoes_disponiveis' => $disponiveis,
        ]);
        exit;
    }

    if ($acao === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $login = strtolower(trim($_POST['login'] ?? ''));
        $perfilUi = $_POST['perfil'] ?? 'Estudante';

        if (!$id || $nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($login, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
            exit;
        }

        $alvo = utilizadorAjaxAlvo($conexao, $id);
        if (!$alvo) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Utilizador nÃ£o encontrado']);
            exit;
        }
        if (!utilizadorAjaxPodeGerirAlvo($alvo, $perfilAtual)) {
            utilizadorAjaxNegar();
        }

        $stmtCheck = $conexao->prepare("SELECT COUNT(*) FROM utilizador WHERE (email = ? OR login = ?) AND id != ?");
        $stmtCheck->execute([$email, $login, $id]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Email já registado']);
            exit;
        }

        $perfilBd = perfilParaBd($perfilUi);
        if ($perfilAtual !== 'administrador' && $perfilBd === 'administrador') {
            utilizadorAjaxNegar('Nao pode atribuir perfil Administrador.');
        }

        $stmt = $conexao->prepare("UPDATE utilizador SET nome = ?, email = ?, login = ?, perfil = ? WHERE id = ? AND ativo = 1");
        $stmt->execute([$nome, $email, $login, $perfilBd, $id]);

        echo json_encode(['sucesso' => true]);
        exit;
    }

    if ($acao === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
            exit;
        }
        if ($id === $utilizadorAtualId) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nao pode eliminar a propria conta']);
            exit;
        }

        $stmtPerfil = $conexao->prepare("SELECT perfil FROM utilizador WHERE id = ? AND ativo = 1");
        $stmtPerfil->execute([$id]);
        $perfil = $stmtPerfil->fetchColumn();
        if (!$perfil) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Utilizador não encontrado']);
            exit;
        }
        if ($perfil === 'administrador' && $perfilAtual !== 'administrador') {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Não é possível eliminar o administrador']);
            exit;
        }
        $stmt = $conexao->prepare("UPDATE utilizador SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    if ($acao === 'adicionar_permissao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $utilizadorId = (int)($_POST['utilizador_id'] ?? 0);
        $permissaoId = (int)($_POST['permissao_id'] ?? 0);
        if (!$utilizadorId || !$permissaoId) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
            exit;
        }
        $alvo = utilizadorAjaxAlvo($conexao, $utilizadorId);
        if (!$alvo || !utilizadorAjaxPodeGerirAlvo($alvo, $perfilAtual)) {
            utilizadorAjaxNegar();
        }

        $stmtCheck = $conexao->prepare("SELECT COUNT(*) FROM utilizador_permissao WHERE utilizador_id = ? AND permissao_id = ?");
        $stmtCheck->execute([$utilizadorId, $permissaoId]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Permissão já atribuída']);
            exit;
        }
        $stmt = $conexao->prepare("INSERT INTO utilizador_permissao (utilizador_id, permissao_id, atribuido_por) VALUES (?, ?, ?)");
        $stmt->execute([$utilizadorId, $permissaoId, $atribuidoPor]);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    if ($acao === 'remover_permissao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $utilizadorId = (int)($_POST['utilizador_id'] ?? 0);
        $permissaoId = (int)($_POST['permissao_id'] ?? 0);
        if (!$utilizadorId || !$permissaoId) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
            exit;
        }
        $alvo = utilizadorAjaxAlvo($conexao, $utilizadorId);
        if (!$alvo || !utilizadorAjaxPodeGerirAlvo($alvo, $perfilAtual)) {
            utilizadorAjaxNegar();
        }

        $stmt = $conexao->prepare("DELETE FROM utilizador_permissao WHERE utilizador_id = ? AND permissao_id = ?");
        $stmt->execute([$utilizadorId, $permissaoId]);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida']);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor']);
}
