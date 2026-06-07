<?php
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/permissoes-utilizador.php';

if (!function_exists('normalizarPerfilAuth')) {
    function normalizarPerfilAuth(string $perfil): string
    {
        $perfil = strtolower(trim($perfil));
        return $perfil === 'staff' ? 'funcionario_administrativo' : $perfil;
    }
}

if (!function_exists('obterUtilizador')) {
    function obterUtilizador(): array
    {
        global $pdo;

        $sessao = [
            'id' => (int) ($_SESSION['utilizador_id'] ?? 0),
            'nome' => (string) ($_SESSION['nome'] ?? 'Administrador Sistema'),
            'email' => (string) ($_SESSION['email'] ?? 'admin@upt.pt'),
            'perfil' => normalizarPerfilAuth((string) ($_SESSION['perfil'] ?? 'administrador')),
        ];

        if ($sessao['id'] <= 0) {
            return $sessao;
        }

        try {
            $stmt = $pdo->prepare('SELECT id, nome, email, perfil FROM utilizador WHERE id = ? AND ativo = 1 LIMIT 1');
            $stmt->execute([$sessao['id']]);
            $utilizador = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilizador) {
                $utilizador['perfil'] = normalizarPerfilAuth((string) $utilizador['perfil']);
                return $utilizador;
            }
        } catch (Throwable $erro) {
        }

        return $sessao;
    }
}

if (!function_exists('verificarAutenticacao')) {
    function verificarAutenticacao(): void
    {
        if (empty($_SESSION['utilizador_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('verificarPerfil')) {
    function verificarPerfil(array $perfisPermitidos): void
    {
        verificarAutenticacao();

        $permitidos = array_map('normalizarPerfilAuth', $perfisPermitidos);
        $perfil = normalizarPerfilAuth((string) (obterUtilizador()['perfil'] ?? ''));

        if (!in_array($perfil, $permitidos, true)) {
            header('Location: dashboard.php');
            exit;
        }
    }
}

if (!function_exists('negarAcessoAuth')) {
    function negarAcessoAuth(string $mensagem = 'Acesso negado.'): void
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($xhr || str_contains($accept, 'application/json')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(403);
        header('Location: dashboard.php');
        exit;
    }
}

if (!function_exists('verificarPermissao')) {
    function verificarPermissao(string|array $permissoesPermitidas): void
    {
        verificarAutenticacao();

        $perfil = normalizarPerfilAuth((string) (obterUtilizador()['perfil'] ?? ''));
        if ($perfil === 'administrador' || $perfil === 'funcionario_administrativo') {
            return;
        }

        $permissoesPermitidas = (array) $permissoesPermitidas;
        foreach ($permissoesPermitidas as $permissao) {
            if (utilizadorTemPermissao((string) $permissao, null, $perfil)) {
                return;
            }
        }

        negarAcessoAuth();
    }
}
