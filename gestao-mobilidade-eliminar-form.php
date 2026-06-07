<?php
if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === basename(__FILE__)) {
    require_once __DIR__ . '/middleware/auth.php';
    verificarPermissao('gestao_espacos');
}
?>
<div class="modal-overlay" id="modalEliminarRecurso" onclick="fecharModalEliminar(event)">
    <div class="modal-container modal-mobilidade">
        <button type="button" class="modal-close-btn" onclick="fecharModalEliminarBtn()">
            <i data-lucide="x"></i>
        </button>
        <div class="modal-header">
            <h2 class="modal-title">Eliminar Recurso</h2>
            <p class="modal-subtitle" id="eliminar-mensagem">Tem a certeza que deseja eliminar este recurso?</p>
        </div>
        <div class="modal-body modal-body-eliminar">
            <div class="eliminar-aviso">
                <i data-lucide="alert-triangle"></i>
                <p id="eliminar-aviso-texto">Esta ação irá remover o recurso do sistema. Esta operação não pode ser revertida facilmente.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="fecharModalEliminarBtn()">Cancelar</button>
            <button type="button" class="btn-modal-submit btn-modal-danger" id="btn-confirmar-eliminar">Eliminar Recurso</button>
        </div>
        <input type="hidden" id="eliminar_id">
        <input type="hidden" id="eliminar_tipo">
    </div>
</div>
