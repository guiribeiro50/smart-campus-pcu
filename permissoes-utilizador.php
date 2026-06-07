<?php
require_once __DIR__ . '/config/database.php';

function permissoesPadraoPorPerfil(string $perfilBd): array
{
    $perfilBd = $perfilBd === 'staff' ? 'funcionario_administrativo' : strtolower(trim($perfilBd));

    $mapa = [
        'estudante' => ['reservar_salas', 'acesso_mobilidade'],
        'docente' => ['reservar_salas', 'acesso_mobilidade'],
        'funcionario_administrativo' => [
            'reservar_salas', 'reservar_laboratorios', 'reservar_equipamentos',
            'acesso_mobilidade', 'acesso_iot', 'exportar_relatorios',
            'acesso_lss', 'gestao_espacos', 'gestao_utilizadores',
        ],
        'administrador' => [
            'reservar_salas', 'reservar_laboratorios', 'reservar_equipamentos',
            'acesso_mobilidade', 'acesso_iot', 'exportar_relatorios',
            'acesso_lss', 'gestao_espacos', 'gestao_utilizadores',
        ],
    ];

    return $mapa[$perfilBd] ?? [];
}

function obterPermissoesUtilizador(?int $utilizadorId = null, ?string $perfil = null): array
{
    if ($utilizadorId === null) {
        $utilizadorId = (int) ($_SESSION['utilizador_id'] ?? 0);
    }

    if ($perfil === null) {
        $perfil = strtolower((string) ($_SESSION['perfil'] ?? 'estudante'));
        $perfil = $perfil === 'staff' ? 'funcionario_administrativo' : $perfil;
    } else {
        $perfil = strtolower(trim($perfil));
        $perfil = $perfil === 'staff' ? 'funcionario_administrativo' : $perfil;
    }

    if ($perfil === 'funcionario_administrativo' || $perfil === 'administrador') {
        return permissoesPadraoPorPerfil($perfil);
    }

    if ($utilizadorId <= 0) {
        return permissoesPadraoPorPerfil($perfil);
    }

    try {
        $pdo = obterLigacaoBD();
        $stmt = $pdo->prepare(
            "SELECT p.nome
             FROM utilizador_permissao up
             INNER JOIN permissao p ON p.id = up.permissao_id
             WHERE up.utilizador_id = ?"
        );
        $stmt->execute([$utilizadorId]);
        $permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_unique(array_map('strval', $permissoes)));
    } catch (Throwable $erro) {
    }

    return permissoesPadraoPorPerfil($perfil);
}

function utilizadorTemPermissao(string $permissao, ?int $utilizadorId = null, ?string $perfil = null): bool
{
    return in_array($permissao, obterPermissoesUtilizador($utilizadorId, $perfil), true);
}
