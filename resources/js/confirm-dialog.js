let confirmDialog = null;
let confirmTitle = null;
let confirmMessage = null;
let confirmCancel = null;
let confirmAccept = null;
let pendingForm = null;
let pendingTrigger = null;
const approvedForms = new WeakSet();
const CONFIRM_DIALOG_ID = 'sipakarbun-confirm-dialog';

function findConfirmDialogs() {
    return Array.from(new Set([
        // The previous implementation used a native dialog without the
        // current class/data marker. Include native dialogs so cancelling
        // the current prompt cannot reveal that legacy prompt underneath.
        ...document.querySelectorAll('dialog'),
        ...document.querySelectorAll(`#${CONFIRM_DIALOG_ID}`),
        ...document.querySelectorAll('.ui-confirm-dialog'),
        ...document.querySelectorAll('[data-sipakarbun-confirm-dialog]'),
    ]));
}

function removeDuplicateDialogs(keep = null) {
    findConfirmDialogs()
        .filter((element) => element !== keep)
        .forEach((element) => element.remove());
}

function bindDialog(dialog) {
    confirmDialog = dialog;
    confirmTitle = dialog.querySelector('.ui-confirm-dialog__title');
    confirmMessage = dialog.querySelector('.ui-confirm-dialog__message');
    confirmCancel = dialog.querySelector('.ui-confirm-dialog__cancel');
    confirmAccept = dialog.querySelector('.ui-confirm-dialog__accept');
    const closeButton = dialog.querySelector('.ui-confirm-dialog__close');

    if (!(confirmTitle && confirmMessage && confirmCancel && confirmAccept && closeButton)) {
        dialog.remove();
        confirmDialog = null;
        return null;
    }

    // A second bundle may discover the existing root. Its existing handlers
    // remain authoritative; do not bind another set of listeners to it.
    if (dialog.dataset.sipakarbunBound === 'true') {
        return dialog;
    }

    dialog.dataset.sipakarbunBound = 'true';

    const resetDialogState = () => {
        confirmTitle.textContent = '';
        confirmMessage.textContent = '';
        confirmAccept.textContent = 'Lanjutkan';
        confirmAccept.disabled = false;
        confirmAccept.classList.remove('ui-confirm-dialog__accept--danger');
    };

    const close = ({ cancelled = false, restoreFocus = true } = {}) => {
        const trigger = pendingTrigger;
        const form = pendingForm;

        pendingForm = null;
        pendingTrigger = null;
        dialog.close();
        removeDuplicateDialogs(dialog);
        resetDialogState();

        if (cancelled) {
            window.dispatchEvent(new CustomEvent('sipakarbun:navigation-cancelled', {
                detail: { form },
            }));
        }

        if (restoreFocus && trigger?.isConnected && typeof trigger.focus === 'function') {
            trigger.focus();
        }
    };

    const cancel = (event) => {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        close({ cancelled: true });
    };

    confirmCancel.addEventListener('click', cancel);
    closeButton.addEventListener('click', cancel);
    dialog.addEventListener('cancel', cancel);
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            cancel(event);
        }
    });
    confirmAccept.addEventListener('click', () => {
        const form = pendingForm;

        if (!form) {
            close({ cancelled: true });
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        confirmAccept.disabled = true;
        confirmAccept.textContent = 'Memproses...';
        approvedForms.add(form);
        close({ restoreFocus: false });
        form.requestSubmit();
    });

    return dialog;
}

function ensureDialog() {
    const existingDialogs = findConfirmDialogs();

    if (confirmDialog?.isConnected) {
        // Keep the bound root and remove stale roots from an older bundle or
        // hot replacement. This also removes the legacy Batal/Lanjutkan
        // dialog that could remain stacked behind the current Hapus dialog.
        removeDuplicateDialogs(confirmDialog);

        return confirmDialog;
    }

    confirmDialog = null;
    const existingDialog = existingDialogs.find((element) => element instanceof HTMLDialogElement);

    // Remove legacy duplicate roots, keeping the first valid global dialog.
    removeDuplicateDialogs(existingDialog);

    if (existingDialog) {
        existingDialog.id = CONFIRM_DIALOG_ID;
        existingDialog.classList.add('ui-confirm-dialog');
        existingDialog.dataset.sipakarbunConfirmDialog = 'true';
        return bindDialog(existingDialog);
    }

    const dialog = document.createElement('dialog');
    dialog.id = CONFIRM_DIALOG_ID;
    dialog.className = 'ui-confirm-dialog';
    dialog.dataset.sipakarbunConfirmDialog = 'true';
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'ui-confirm-title');
    dialog.setAttribute('aria-describedby', 'ui-confirm-message');
    dialog.innerHTML = `
        <div class="ui-confirm-dialog__surface">
            <div class="ui-confirm-dialog__header">
                <span class="ui-confirm-dialog__icon" aria-hidden="true">!</span>
                <div>
                    <h2 id="ui-confirm-title" class="ui-confirm-dialog__title"></h2>
                    <p id="ui-confirm-message" class="ui-confirm-dialog__message"></p>
                </div>
                <button type="button" class="ui-confirm-dialog__close" aria-label="Tutup">&times;</button>
            </div>
            <div class="ui-confirm-dialog__actions">
                <button type="button" class="ui-confirm-dialog__cancel">Batal</button>
                <button type="button" class="ui-confirm-dialog__accept">Lanjutkan</button>
            </div>
        </div>
    `;
    document.body.append(dialog);

    return bindDialog(dialog);
}

function showConfirmation(form) {
    const dialog = ensureDialog();

    if (!dialog) {
        return false;
    }

    if (typeof dialog.showModal !== 'function') {
        return window.confirm(form.dataset.confirmMessage || 'Lanjutkan tindakan ini?');
    }

    pendingForm = form;
    pendingTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    confirmTitle.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
    confirmMessage.textContent = form.dataset.confirmMessage || 'Lanjutkan tindakan ini?';
    confirmAccept.textContent = form.dataset.confirmAction || 'Lanjutkan';
    confirmAccept.classList.toggle('ui-confirm-dialog__accept--danger', form.dataset.confirmTone === 'danger');
    confirmAccept.disabled = false;
    dialog.showModal();
    confirmAccept.focus();

    return false;
}

if (!window.__sipakarbunConfirmDialogInitialized) {
    window.__sipakarbunConfirmDialogInitialized = true;

    // Capture the submit before any legacy bubble listener can open a second
    // confirmation prompt. The same handler also owns the approved resubmit.
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-message]')) {
            return;
        }

        if (approvedForms.has(form)) {
            approvedForms.delete(form);
            event.stopImmediatePropagation();
            window.dispatchEvent(new CustomEvent('sipakarbun:navigation-start', {
                detail: { form },
            }));
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        const dialog = ensureDialog();

        if (typeof dialog?.showModal !== 'function') {
            if (!form.checkValidity() || !window.confirm(form.dataset.confirmMessage || 'Lanjutkan tindakan ini?')) {
                return;
            }

            window.dispatchEvent(new CustomEvent('sipakarbun:navigation-start', {
                detail: { form },
            }));
            HTMLFormElement.prototype.submit.call(form);
            return;
        }

        showConfirmation(form);
    }, true);
}
