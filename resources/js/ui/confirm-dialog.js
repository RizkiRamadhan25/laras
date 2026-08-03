const TONE_STYLES = {
    danger: {
        icon: 'bg-rose-100 text-rose-700',
        button: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
    },
    warning: {
        icon: 'bg-amber-100 text-amber-700',
        button: 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500',
    },
    primary: {
        icon: 'bg-laras-100 text-laras-700',
        button: 'bg-laras-700 hover:bg-laras-800 focus:ring-laras-500',
    },
};

const normalizeTone = (tone) => {
    return Object.hasOwn(TONE_STYLES, tone)
        ? tone
        : 'danger';
};

const createConfirmController = (dialog) => {
    if (! dialog) {
        return null;
    }

    const title = dialog.querySelector(
        '[data-laras-confirm-title]'
    );
    const message = dialog.querySelector(
        '[data-laras-confirm-message]'
    );
    const iconShell = dialog.querySelector(
        '[data-laras-confirm-icon-shell]'
    );
    const confirmButton = dialog.querySelector(
        '[data-laras-confirm-accept]'
    );
    const confirmLabel = dialog.querySelector(
        '[data-laras-confirm-label]'
    );
    const closeButtons = dialog.querySelectorAll(
        '[data-laras-confirm-close], [data-laras-confirm-cancel]'
    );

    let resolver = null;
    let previousFocus = null;

    const finish = (confirmed) => {
        if (! resolver) {
            return;
        }

        const resolve = resolver;
        resolver = null;

        if (dialog.open) {
            dialog.close();
        }

        resolve(confirmed);

        window.requestAnimationFrame(() => {
            previousFocus?.focus?.();
        });
    };

    const applyTone = (tone) => {
        const normalizedTone = normalizeTone(tone);
        const style = TONE_STYLES[normalizedTone];

        dialog.dataset.tone = normalizedTone;

        if (iconShell) {
            iconShell.className = [
                'flex size-12 shrink-0 items-center justify-center rounded-2xl',
                style.icon,
            ].join(' ');
        }

        if (confirmButton) {
            confirmButton.className = [
                'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                style.button,
            ].join(' ');
        }
    };

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => finish(false));
    });

    confirmButton?.addEventListener('click', () => {
        finish(true);
    });

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        finish(false);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            finish(false);
        }
    });

    const open = ({
        titleText,
        messageText,
        confirmText,
        tone,
        trigger,
    }) => {
        if (resolver) {
            return Promise.resolve(false);
        }

        previousFocus = trigger instanceof HTMLElement
            ? trigger
            : document.activeElement;

        if (title) {
            title.textContent = titleText;
        }

        if (message) {
            message.textContent = messageText;
        }

        if (confirmLabel) {
            confirmLabel.textContent = confirmText;
        }

        applyTone(tone);

        return new Promise((resolve) => {
            resolver = resolve;
            dialog.showModal();

            window.requestAnimationFrame(() => {
                confirmButton?.focus();
            });
        });
    };

    return {
        open,
    };
};

const setSubmittingState = (form, submitter) => {
    form.setAttribute('aria-busy', 'true');

    if (!(submitter instanceof HTMLButtonElement)) {
        return;
    }

    submitter.disabled = true;

    const busyLabel = form.dataset.confirmBusyLabel;

    if (! busyLabel) {
        return;
    }

    const label = submitter.querySelector(
        '[data-submit-label]'
    );

    if (label) {
        label.textContent = busyLabel;
    } else {
        submitter.setAttribute('aria-label', busyLabel);
    }
};

const initializeConfirmDialog = () => {
    const dialog = document.querySelector(
        '[data-laras-confirm-dialog]'
    );
    const controller = createConfirmController(dialog);

    if (! controller) {
        return;
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (! form.matches('[data-confirm]')) {
            return;
        }

        const submitter = event.submitter;

        if (form.dataset.confirmBypass === 'true') {
            delete form.dataset.confirmBypass;
            setSubmittingState(form, submitter);

            return;
        }

        event.preventDefault();

        const confirmed = await controller.open({
            titleText: form.dataset.confirmTitle
                ?? 'Konfirmasi tindakan',
            messageText: form.dataset.confirmMessage
                ?? 'Pastikan kamu ingin melanjutkan tindakan ini.',
            confirmText: form.dataset.confirmLabel
                ?? 'Lanjutkan',
            tone: form.dataset.confirmTone
                ?? 'danger',
            trigger: submitter,
        });

        if (! confirmed) {
            return;
        }

        form.dataset.confirmBypass = 'true';

        if (
            submitter instanceof HTMLButtonElement
            || submitter instanceof HTMLInputElement
        ) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeConfirmDialog
    );
} else {
    initializeConfirmDialog();
}
