<?php
if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_utilizadores');
    $perfilAtual = normalizarPerfilAuth((string) (obterUtilizador()['perfil'] ?? ''));
    $podeCriarAdmin = $perfilAtual === 'administrador';
}
?>
<div class="modal-overlay" id="modalEditarUtilizador" onclick="fecharModalEditar(event)">
    <div class="modal-container">
        <button type="button" class="modal-close-btn" onclick="fecharModalEditarBtn()">
            <i data-lucide="x"></i>
        </button>
        <div class="modal-header">
            <h2 class="modal-title">Editar Utilizador</h2>
            <p class="modal-subtitle">Atualize as informações do utilizador</p>
        </div>
        <form id="formEditarUtilizador">
            <input type="hidden" id="editar_id" name="id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="editar_nome">Nome Completo</label>
                    <input type="text" id="editar_nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="editar_email">Email Real</label>
                    <input type="email" id="editar_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="editar_login">Email Institucional (Login)</label>
                    <input type="email" id="editar_login" name="login" required>
                </div>
                <div class="form-group">
                    <label>Perfil de Acesso</label>
                    <div class="custom-select-wrapper" id="wrapper-perfil-editar">
                        <input type="hidden" id="editar_perfil" name="perfil" value="Estudante">
                        <div class="custom-select-trigger" onclick="alternarDropdownPerfil(event, 'wrapper-perfil-editar')">
                            <span id="editar-perfil-text">Estudante</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container">
                            <div class="custom-option" data-value="Estudante" onclick="selecionarPerfil(this, 'editar_perfil', 'editar-perfil-text', 'wrapper-perfil-editar', 'alerta-admin-editar')">
                                <span>Estudante</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Docente" onclick="selecionarPerfil(this, 'editar_perfil', 'editar-perfil-text', 'wrapper-perfil-editar', 'alerta-admin-editar')">
                                <span>Docente</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="Staff Administrativo" onclick="selecionarPerfil(this, 'editar_perfil', 'editar-perfil-text', 'wrapper-perfil-editar', 'alerta-admin-editar')">
                                <span>Staff Administrativo</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <?php if (($podeCriarAdmin ?? true) === true): ?>
                                <div class="custom-option" data-value="Administrador" onclick="selecionarPerfil(this, 'editar_perfil', 'editar-perfil-text', 'wrapper-perfil-editar', 'alerta-admin-editar')">
                                    <span>Administrador</span>
                                    <i data-lucide="check" class="check-icon"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="alerta-admin" id="alerta-admin-editar" style="display:none;">
                    <i data-lucide="shield"></i>
                    <div>
                        <strong>Atenção: Perfil Administrador</strong>
                        <p>Este perfil tem acesso total ao sistema e requer autenticação multifator (MFA).</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="fecharModalEditarBtn()">Cancelar</button>
                <button type="submit" class="btn-modal-submit">Atualizar Utilizador</button>
            </div>
        </form>
    </div>
</div>
