import {
    loadActivityBrowser,
} from './activity-browser';

const ACTION_FORM_SELECTOR =
    '[data-activity-action-form]';

const ACTION_BUTTON_SELECTOR =
    '[data-activity-action-button]';

const ACTION_LABEL_SELECTOR =
    '[data-activity-action-label]';

const CARD_SELECTOR =
    '[data-activity-card]';

const busyStates = new WeakMap();

function actionButtonFor(form, submitter) {
    if (
        submitter instanceof HTMLButtonElement
        && submitter.matches(
            ACTION_BUTTON_SELECTOR
        )
    ) {
        return submitter;
    }

    return form.querySelector(
        ACTION_BUTTON_SELECTOR
    );
}

function actionButtonsIn(form) {
    const card = form.closest(CARD_SELECTOR);
    const root = card ?? form;

    return Array.from(
        root.querySelectorAll(
            ACTION_BUTTON_SELECTOR
        )
    ).filter(
        (button) =>
            button instanceof HTMLButtonElement
    );
}

function setBusyState(
    form,
    submitter,
    busy
) {
    if (busy) {
        if (busyStates.has(form)) {
            return;
        }

        const card = form.closest(CARD_SELECTOR);

        const actionButton = actionButtonFor(
            form,
            submitter
        );

        const actionLabel = form.querySelector(
            ACTION_LABEL_SELECTOR
        );

        const buttons = actionButtonsIn(form);

        busyStates.set(form, {
            card,

            cardHadOpacity:
                card?.classList.contains(
                    'opacity-70'
                ) ?? false,

            cardHadPointerLock:
                card?.classList.contains(
                    'pointer-events-none'
                ) ?? false,

            actionButton,

            actionButtonAriaLabel:
                actionButton?.getAttribute(
                    'aria-label'
                ) ?? null,

            actionLabel,

            actionLabelText:
                actionLabel?.textContent ?? null,

            buttons: buttons.map((button) => ({
                button,
                disabled: button.disabled,

                ariaDisabled:
                    button.getAttribute(
                        'aria-disabled'
                    ),
            })),
        });

        form.dataset.activitySubmitting =
            'true';

        form.setAttribute(
            'aria-busy',
            'true'
        );

        card?.classList.add(
            'opacity-70',
            'pointer-events-none'
        );

        buttons.forEach((button) => {
            button.disabled = true;

            button.setAttribute(
                'aria-disabled',
                'true'
            );
        });

        const busyLabel =
            form.dataset.activityBusyLabel
            ?? 'Memproses...';

        if (actionLabel) {
            actionLabel.textContent = busyLabel;
        } else {
            actionButton?.setAttribute(
                'aria-label',
                busyLabel
            );
        }

        return;
    }

    const state = busyStates.get(form);

    if (! state) {
        return;
    }

    delete form.dataset.activitySubmitting;

    form.removeAttribute('aria-busy');

    if (
        state.card
        && ! state.cardHadOpacity
    ) {
        state.card.classList.remove(
            'opacity-70'
        );
    }

    if (
        state.card
        && ! state.cardHadPointerLock
    ) {
        state.card.classList.remove(
            'pointer-events-none'
        );
    }

    state.buttons.forEach((item) => {
        item.button.disabled = item.disabled;

        if (item.ariaDisabled === null) {
            item.button.removeAttribute(
                'aria-disabled'
            );
        } else {
            item.button.setAttribute(
                'aria-disabled',
                item.ariaDisabled
            );
        }
    });

    if (
        state.actionLabel
        && state.actionLabelText !== null
    ) {
        state.actionLabel.textContent =
            state.actionLabelText;
    }

    if (state.actionButton) {
        if (
            state.actionButtonAriaLabel
            === null
        ) {
            state.actionButton.removeAttribute(
                'aria-label'
            );
        } else {
            state.actionButton.setAttribute(
                'aria-label',
                state.actionButtonAriaLabel
            );
        }
    }

    busyStates.delete(form);
}

async function responsePayload(response) {
    const contentType =
        response.headers.get(
            'content-type'
        ) ?? '';

    if (
        ! contentType.includes(
            'application/json'
        )
    ) {
        return {};
    }

    try {
        return await response.json();
    } catch {
        return {};
    }
}

function responseErrorMessage(
    response,
    payload
) {
    if (
        typeof payload.message === 'string'
        && payload.message.trim() !== ''
    ) {
        return payload.message;
    }

    if (response.status === 419) {
        return [
            'Sesi keamanan telah berakhir.',
            'Muat ulang halaman lalu coba lagi.',
        ].join(' ');
    }

    if (response.status === 404) {
        return [
            'Aktivitas tidak ditemukan',
            'atau tidak dapat diakses.',
        ].join(' ');
    }

    if (response.status === 422) {
        return [
            'Aksi tidak dapat dilakukan',
            'pada kondisi aktivitas saat ini.',
        ].join(' ');
    }

    return [
        'Aksi aktivitas gagal diproses.',
        'Coba lagi beberapa saat.',
    ].join(' ');
}

async function submitActivityAction(
    form,
    submitter
) {
    if (
        form.dataset.activitySubmitting
        === 'true'
    ) {
        return;
    }

    setBusyState(
        form,
        submitter,
        true
    );

    try {
        const response = await fetch(
            form.action,
            {
                method: (
                    form.getAttribute('method')
                    ?? 'POST'
                ).toUpperCase(),

                headers: {
                    Accept: 'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest',
                },

                credentials: 'same-origin',

                body: new FormData(form),
            }
        );

        const payload = await responsePayload(
            response
        );

        if (! response.ok) {
            throw new Error(
                responseErrorMessage(
                    response,
                    payload
                )
            );
        }

        const refreshed =
            await loadActivityBrowser(
                window.location.href,
                {
                    historyMode: 'none',
                    showErrorToast: false,
                }
            );

        if (! refreshed) {
            window.LarasToast?.warning(
                [
                    payload.message
                        ?? 'Aktivitas berhasil diperbarui.',

                    'Namun tampilan belum dapat',
                    'disegarkan. Muat ulang halaman.',
                ].join(' '),
                {
                    duration: 8000,
                }
            );

            return;
        }

        window.LarasToast?.success(
            payload.message
                ?? 'Aktivitas berhasil diperbarui.',
            {
                duration: 3200,
            }
        );
    } catch (error) {
        console.error(
            'Activity action failed.',
            error
        );

        const message =
            error instanceof Error
            && error.message.trim() !== ''
                ? error.message
                : 'Aksi aktivitas gagal diproses.';

        window.LarasToast?.error(
            message,
            {
                duration: 8000,
            }
        );
    } finally {
        setBusyState(
            form,
            submitter,
            false
        );
    }
}

document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;

        if (
            ! (
                form
                instanceof HTMLFormElement
            )
            || ! form.matches(
                ACTION_FORM_SELECTOR
            )
        ) {
            return;
        }

        if (event.defaultPrevented) {
            return;
        }

        if (
            form.dataset.activitySubmitting
            === 'true'
        ) {
            event.preventDefault();

            return;
        }

        /*
         * Pada submit pertama, form yang memakai
         * data-confirm harus ditangani lebih dahulu
         * oleh confirm-dialog.js.
         *
         * Setelah pengguna mengonfirmasi, dialog
         * mengirim ulang form dengan
         * data-confirm-bypass="true".
         */
        if (
            form.matches('[data-confirm]')
            && form.dataset.confirmBypass
                !== 'true'
        ) {
            return;
        }

        event.preventDefault();

        void submitActivityAction(
            form,
            event.submitter
        );
    }
);
