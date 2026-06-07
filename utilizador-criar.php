<?php
session_start();
include 'config.php';
require_once __DIR__ . '/middleware/auth.php';

verificarPermissao('gestao_utilizadores');

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

function gerarPasswordSegura(int $comprimento = 12): string
{
    $caracteres = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
    $password = '';
    $max = strlen($caracteres) - 1;

    for ($i = 0; $i < $comprimento; $i++) {
        $password .= $caracteres[random_int(0, $max)];
    }

    return $password;
}

function perfilLabelUi(string $perfilBd): string
{
    return match ($perfilBd) {
        'administrador' => 'Administrador',
        'docente' => 'Docente',
        'funcionario_administrativo' => 'Staff Administrativo',
        default => 'Estudante',
    };
}

function permissoesPadraoIdsPorPerfil(string $perfilBd): array
{
    $mapa = [
        'estudante' => [1, 4],
        'docente' => [1, 4],
        'funcionario_administrativo' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'administrador' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
    ];
    return $mapa[$perfilBd] ?? [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: utilizadores.php');
    exit;
}

validarCsrf();

$nome = trim($_POST['nome_completo'] ?? '');
$emailReal = trim($_POST['email_real'] ?? '');
$loginInstitucional = strtolower(trim($_POST['login_institucional'] ?? ''));
$perfilUi = $_POST['perfil_acesso'] ?? 'Estudante';

if ($nome === '' || !filter_var($emailReal, FILTER_VALIDATE_EMAIL)) {
    header('Location: utilizadores.php?erro=dados');
    exit;
}

if ($loginInstitucional === '') {
    header('Location: utilizadores.php?erro=dados');
    exit;
}

$perfilBd = perfilParaBd($perfilUi);
$perfilAtual = normalizarPerfilAuth((string) (obterUtilizador()['perfil'] ?? ''));
if ($perfilAtual !== 'administrador' && $perfilBd === 'administrador') {
    header('Location: utilizadores.php?erro=permissao');
    exit;
}

$passwordGerada = gerarPasswordSegura();
$passwordHash = password_hash($passwordGerada, PASSWORD_BCRYPT);
$atribuidoPor = (int) ($_SESSION['utilizador_id'] ?? 1);
if ($atribuidoPor <= 0) {
    $atribuidoPor = 1;
}

try {
    $stmtCheck = $conexao->prepare('SELECT COUNT(*) FROM utilizador WHERE email = ? OR login = ?');
    $stmtCheck->execute([$emailReal, $loginInstitucional]);
    if ((int) $stmtCheck->fetchColumn() > 0) {
        header('Location: utilizadores.php?erro=email');
        exit;
    }

    $conexao->beginTransaction();

    $stmt = $conexao->prepare(
        'INSERT INTO utilizador (nome, email, login, password, perfil, ativo)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([$nome, $emailReal, $loginInstitucional, $passwordHash, $perfilBd]);
    $novoId = (int) $conexao->lastInsertId();

    $permissoes = permissoesPadraoIdsPorPerfil($perfilBd);
    if ($permissoes) {
        $stmtPerm = $conexao->prepare(
            'INSERT IGNORE INTO utilizador_permissao (utilizador_id, permissao_id, atribuido_por)
             VALUES (?, ?, ?)'
        );
        foreach ($permissoes as $permId) {
            $stmtPerm->execute([$novoId, (int) $permId, $atribuidoPor]);
        }
    }

    $conexao->commit();

    $_SESSION['novo_utilizador'] = [
        'nome' => $nome,
        'email' => $emailReal,
        'login' => $loginInstitucional,
        'perfil' => perfilLabelUi($perfilBd),
        'password' => $passwordGerada,
    ];

    header('Location: utilizador-criado.php');
} catch (Throwable $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    error_log('Erro ao criar utilizador: ' . $e->getMessage());
    header('Location: utilizadores.php?erro=geral');
}
exit;
