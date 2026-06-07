<?php

if (!function_exists('carregarEnv')) {
    function carregarEnv(string $caminho): void
    {
        if (!is_readable($caminho)) {
            return;
        }

        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhas === false) {
            return;
        }

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || $linha[0] === '#') {
                continue;
            }
            if (!str_contains($linha, '=')) {
                continue;
            }

            [$chave, $valor] = explode('=', $linha, 2);
            $chave = trim($chave);
            $valor = trim($valor, " \t\"'");

            if ($chave === '' || getenv($chave) !== false) {
                continue;
            }

            putenv("{$chave}={$valor}");
            $_ENV[$chave] = $valor;
        }
    }
}

if (!function_exists('env')) {
    function env(string $chave, ?string $padrao = null): ?string
    {
        $valor = $_ENV[$chave] ?? getenv($chave);

        if ($valor === false || $valor === '') {
            return $padrao;
        }

        return $valor;
    }
}
