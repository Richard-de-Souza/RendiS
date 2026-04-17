<div class="modal-overlay" id="global-modal" onclick="closeModal(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-icon" id="modal-icon-container">
            <i class='bx' id="modal-icon"></i>
        </div>
        <h3 class="modal-title" id="modal-title">Título</h3>
        <p class="modal-message" id="modal-message">Mensagem do modal</p>
        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;" id="modal-actions">
            <!-- Buttons injected by JS -->
        </div>
    </div>
</div>

<script>
    const globalModal = document.getElementById('global-modal');
    const modalIconContainer = document.getElementById('modal-icon-container');
    const modalIcon = document.getElementById('modal-icon');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalActions = document.getElementById('modal-actions');

    window.openModal = function({ type = 'info', title, message, showCancel = false, confirmText = 'OK', cancelText = 'Cancelar', onConfirm = null }) {
        // Setup texts
        modalTitle.textContent = title;
        modalMessage.innerHTML = message;
        
        // Setup icons and colors
        modalIconContainer.className = `modal-icon ${type}`;
        if (type === 'success') modalIcon.className = 'bx bx-check';
        else if (type === 'error') modalIcon.className = 'bx bx-x';
        else modalIcon.className = 'bx bx-info-circle';
        
        // Callbacks
        window.currentModalConfirm = () => {
            closeModal();
            if(onConfirm) onConfirm();
        };

        // Actions UI
        let buttonsHtml = '';
        if (showCancel) {
            buttonsHtml += `<button class="btn btn-outline" style="flex:1" onclick="closeModal()">${cancelText}</button>`;
        }
        
        let btnClass = type === 'error' ? 'background-color: var(--danger-color); color: white;' : 'background-color: var(--primary-color); color: white;';
        if (type === 'success') btnClass = 'background-color: var(--success-color); color: white;';
        
        buttonsHtml += `<button class="btn" style="flex:1; border:none; ${btnClass}" onclick="window.currentModalConfirm()">${confirmText}</button>`;
        
        modalActions.innerHTML = buttonsHtml;
        
        globalModal.classList.add('active');
    };

    window.closeModal = function(e) {
        if(e && e.target !== globalModal) return; 
        globalModal.classList.remove('active');
    };
</script>
