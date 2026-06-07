<?php
if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_espacos');
}
?>
<div class="modal-overlay" id="modalEditarRecurso" onclick="fecharModalEditar(event)">
    <div class="modal-container modal-mobilidade">
        <button type="button" class="modal-close-btn" onclick="fecharModalEditarBtn()">
            <i data-lucide="x"></i>
        </button>
        <div class="modal-header">
            <h2 class="modal-title" id="modal-editar-titulo">Editar Recurso</h2>
            <p class="modal-subtitle">Atualize as informações do recurso</p>
        </div>
        <form id="formEditarRecurso">
            <input type="hidden" id="editar_id" name="id">
            <input type="hidden" id="editar_tipo" name="tipo">
            <div class="modal-body">
                <div class="form-group">
                    <label for="editar_codigo">Código</label>
                    <input type="text" id="editar_codigo" name="codigo" required>
                </div>
                <div class="form-group" id="grupo-estado-editar" style="display:none;">
                    <label>Estado</label>
                    <div class="custom-select-wrapper" id="wrapper-estado-editar">
                        <input type="hidden" id="editar_estado" name="estado" value="disponivel">
                        <div class="custom-select-trigger" onclick="alternarDropdownMobilidade(event, 'wrapper-estado-editar')">
                            <span id="editar-estado-text">Disponível</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container">
                            <div class="custom-option selected" data-value="disponivel" onclick="selecionarEstado(this, 'editar_estado', 'editar-estado-text', 'wrapper-estado-editar')">
                                <span>Disponível</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="em_uso" onclick="selecionarEstado(this, 'editar_estado', 'editar-estado-text', 'wrapper-estado-editar')">
                                <span>Em uso</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                            <div class="custom-option" data-value="manutencao" onclick="selecionarEstado(this, 'editar_estado', 'editar-estado-text', 'wrapper-estado-editar')">
                                <span>Manutenção</span>
                                <i data-lucide="check" class="check-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Localidade / Zona</label>
                    <div class="custom-select-wrapper" id="wrapper-localidade-editar">
                        <input type="hidden" id="editar_localidade_id" name="localidade_id" value="">
                        <div class="custom-select-trigger" onclick="alternarDropdownMobilidade(event, 'wrapper-localidade-editar')">
                            <span id="editar-localidade-text">Selecionar localidade</span>
                            <i data-lucide="chevron-down" class="arrow-icon"></i>
                        </div>
                        <div class="custom-options-container" id="editar-localidade-opcoes"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="fecharModalEditarBtn()">Cancelar</button>
                <button type="submit" class="btn-modal-submit">Guardar Alterações</button>
            </div>
        </form>
    </div>
</div>
