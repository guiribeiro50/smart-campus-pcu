<?php
if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_utilizadores');
}
?>
<div class="modal-overlay modal-permissoes-overlay" id="modalPermissoes" onclick="fecharModalPermissoes(event)">
    <div class="modal-container modal-permissoes">
        <div class="modal-header-permissoes">
            <h2 class="modal-title-permissoes" id="permissoes-titulo">Permissões</h2>
            <button type="button" class="modal-close-btn modal-close-light" onclick="fecharModalPermissoesBtn()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="permissoes-body">
            <div class="permissoes-user-card">
                <div class="permissoes-avatar" id="permissoes-avatar">AS</div>
                <div class="permissoes-user-info">
                    <div class="permissoes-user-name" id="permissoes-nome"></div>
                    <div class="permissoes-user-email" id="permissoes-email"></div>
                    <span class="badge" id="permissoes-badge"></span>
                </div>
            </div>
            <div class="permissoes-info-box" id="permissoes-info-perfil"></div>
            <div class="permissoes-section">
                <h3>Permissões ativas</h3>
                <div class="permissoes-tags" id="permissoes-ativas"></div>
                <p class="permissoes-vazio" id="permissoes-vazio" style="display:none;">Nenhuma permissão adicional atribuída.</p>
            </div>
            <div class="permissoes-section">
                <h3>Adicionar permissão</h3>
                <div class="permissoes-add-row">
                    <div class="custom-select-wrapper permissoes-select" id="wrapper-permissao-add">
                        <input type="hidden" id="permissao_add_id" value="">
                        <div class="custom-select-trigger" onclick="alternarDropdownPerfil(event, 'wrapper-permissao-add')">
                            <span id="permissao-add-text">Selecionar permissão</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container" id="permissoes-opcoes"></div>
                    </div>
                    <button type="button" class="btn-permissao-add" onclick="adicionarPermissao()">Adicionar</button>
                </div>
            </div>
            <div class="permissoes-section permissoes-historico">
                <button type="button" class="permissoes-historico-toggle" onclick="toggleHistorico()">
                    <span>Histórico de alterações</span>
                    <i data-lucide="chevron-down" id="historico-chevron"></i>
                </button>
                <div class="permissoes-historico-lista" id="permissoes-historico" style="display:none;"></div>
            </div>
        </div>
        <div class="permissoes-footer">
            <button type="button" class="btn-permissoes-guardar" onclick="fecharModalPermissoesBtn()">Guardar</button>
        </div>
        <input type="hidden" id="permissoes_utilizador_id">
    </div>
</div>
