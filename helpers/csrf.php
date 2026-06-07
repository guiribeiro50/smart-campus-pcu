<?php

if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('validarCsrf')) {
    function validarCsrf(?string $token = null): void
    {
        $token = $token
            ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
            http_response_code(403);

            $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
            $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

            if ($xhr || str_contains($accept, 'application/json')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(
                    ['sucesso' => false, 'mensagem' => 'Pedido inválido.'],
                    JSON_UNESCAPED_UNICODE
                );
            } else {
                echo 'Pedido inválido.';
            }

            exit;
        }
    }
}

if (!function_exists('iniciarSessaoAutenticada')) {
    function iniciarSessaoAutenticada(array $utilizador): void
    {
        session_regenerate_id(true);

        $_SESSION['utilizador_id'] = $utilizador['id'];
        $_SESSION['nome'] = $utilizador['nome'];
        $_SESSION['email'] = $utilizador['email'];
        $_SESSION['perfil'] = $utilizador['perfil'];
    }
}

if (!function_exists('definirCookieMfa')) {
    function definirCookieMfa(string $nome, string $valor, int $expira): void
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        setcookie($nome, $valor, [
            'expires' => $expira,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
