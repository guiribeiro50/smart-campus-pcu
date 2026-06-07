<?php
require_once dirname(__DIR__) . '/config.php';

$pdo = $conexao;

if (!function_exists('obterLigacaoBD')) {
    function obterLigacaoBD(): PDO
    {
        global $conexao;
        return $conexao;
    }
}