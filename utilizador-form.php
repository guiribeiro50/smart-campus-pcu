<?php
if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_utilizadores');
    $perfilAtual = normalizarPerfilAuth((string) (obterUtilizador()['perfil'] ?? ''));
    $podeCriarAdmin = $perfilAtual === 'administrador';
}
?>
<div class="modal-overlay" id="modalNovoUtilizador" onclick="fecharModalNovo(event)">
    <div class="modal-container">
        <button type="button" class="modal-close-btn" onclick="alternarModal(false)">
            <i data-lucide="x"></i>
        </button>
        <div class="modal-header">
            <h2 class="modal-title">Novo Utilizador</h2>
            <p class="modal-subtitle">Adicione um novo utilizador ao sistema</p>
        </div>
        <form id="formNovoUtilizador" method="POST" action="utilizador-criar.php">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nome_completo">Nome Completo</label>
                    <input type="text" id="nome_completo" name="nome_completo" placeholder="Nome do utilizador" required>
                </div>
                <div class="form-group">
                    <label for="email_real">Email Real</label>
                    <input type="email" id="email_real" name="email_real" placeholder="nome@gmail.com" required>
                </div>
                <div class="form-group">
                    <label for="login_institucional">Email Institucional (Login)</label>
                    <input type="email" id="login_institucional" name="login_institucional" placeholder="utilizador@upt.pt" required>
                </div>
                <div class="form-group">
                    <label>Perfil de Acesso</label>
                    <div class="custom-select-wrapper" id="wrapper-perfil-novo">
                        <input type="hidden" id="perfil_acesso" name="perfil_acesso" value="Estudante">
                        <div class="custom-select-trigger" onclick="alternarDropdownPerfil(event, 'wrapper-perfil-novo')">
                            <span id="custom-select-text">Estudante</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container">
                            <div class="custom-option selected" data-value="Estudante" onclick="selecionarPerfil(this, 'perfil_acesso', 'custom-select-text', 'wrapper-perfil-novo', 'alerta-admin-novo')">
                                <span>Estudante</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Docente" onclick="selecionarPerfil(this, 'perfil_acesso', 'custom-select-text', 'wrapper-perfil-novo', 'alerta-admin-novo')">
                                <span>Docente</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Staff Administrativo" onclick="selecionarPerfil(this, 'perfil_acesso', 'custom-select-text', 'wrapper-perfil-novo', 'alerta-admin-novo')">
                                <span>Staff Administrativo</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <?php if (($podeCriarAdmin ?? true) === true): ?>
                                <div class="custom-option" data-value="Administrador" onclick="selecionarPerfil(this, 'perfil_acesso', 'custom-select-text', 'wrapper-perfil-novo', 'alerta-admin-novo')">
                                    <span>Administrador</span>
                                    <i data-lucide="check" class="check-icon"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="alerta-admin" id="alerta-admin-novo" style="display:none;">
                    <i data-lucide="shield"></i>
                    <div>
                        <strong>Atenção: Perfil Administrador</strong>
                        <p>Este perfil tem acesso total ao sistema e requer autenticação multifator (MFA).</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="alternarModal(false)">Cancelar</button>
                <button type="submit" class="btn-modal-submit">Criar Utilizador</button>
            </div>
        </form>
    </div>
</div>
