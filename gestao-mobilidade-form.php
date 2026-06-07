<?php
if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_espacos');
}
?>
<div class="modal-overlay" id="modalNovoRecurso" onclick="fecharModalNovo(event)">
    <div class="modal-container modal-mobilidade">
        <button type="button" class="modal-close-btn" onclick="fecharModalNovoBtn()">
            <i data-lucide="x"></i>
        </button>
        <div class="modal-header">
            <h2 class="modal-title" id="modal-novo-titulo">Novo Recurso</h2>
            <p class="modal-subtitle" id="modal-novo-subtitulo">Adicione um novo recurso de mobilidade</p>
        </div>
        <form id="formNovoRecurso">
            <input type="hidden" id="novo_tipo" name="tipo" value="estacionamento">
            <div class="modal-body">
                <div class="form-group" id="grupo-codigo-novo">
                    <label for="novo_codigo">Código <span class="obrigatorio">*</span></label>
                    <input type="text" id="novo_codigo" name="codigo" placeholder="Ex: P1-04" required>
                </div>
                <div class="form-group" id="grupo-quantidade-novo" style="display:none;">
                    <label for="novo_quantidade">Quantidade</label>
                    <input type="number" id="novo_quantidade" name="quantidade" min="1" max="50" value="1" placeholder="Ex: 5">
                </div>
                <div class="form-group" id="grupo-estado-novo" style="display:none;">
                    <label>Estado inicial</label>
                    <div class="custom-select-wrapper" id="wrapper-estado-novo">
                        <input type="hidden" id="novo_estado" name="estado" value="disponivel">
                        <div class="custom-select-trigger" onclick="alternarDropdownMobilidade(event, 'wrapper-estado-novo')">
                            <span id="novo-estado-text">Disponível</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container">
                            <div class="custom-option selected" data-value="disponivel" onclick="selecionarEstado(this, 'novo_estado', 'novo-estado-text', 'wrapper-estado-novo')">
                                <span>Disponível</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="manutencao" onclick="selecionarEstado(this, 'novo_estado', 'novo-estado-text', 'wrapper-estado-novo')">
                                <span>Manutenção</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Localidade / Zona</label>
                    <div class="custom-select-wrapper" id="wrapper-localidade-novo">
                        <input type="hidden" id="novo_localidade_id" name="localidade_id" value="">
                        <div class="custom-select-trigger" onclick="alternarDropdownMobilidade(event, 'wrapper-localidade-novo')">
                            <span id="novo-localidade-text">Selecionar localidade</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container" id="novo-localidade-opcoes"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="fecharModalNovoBtn()">Cancelar</button>
                <button type="submit" class="btn-modal-submit" id="btn-criar-recurso">Criar Recurso</button>
            </div>
        </form>
    </div>
</div>
