<?php
require_once __DIR__ . '/middleware/auth.php';

verificarAutenticacao();

$perfil = normalizarPerfilAuth((string) (obterUtilizador()['perfil'] ?? ''));
$destino = in_array($perfil, ['administrador', 'funcionario_administrativo'], true)
    ? 'dashboard-admin.php'
    : 'dashboard-utilizador.php';

header('Location: ' . $destino);
exit;