const detailLabels = {
    read: 'Sudah dibaca',
    unread: 'Belum dibaca',
    opened: 'Dibuka',
    followed_up: 'Ditindaklanjuti',
    dismissed: 'Diingatkan nanti',
    irrelevant: 'Tidak relevan',
};

const csrfToken = () => {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
};

const firstValidationMessage = (payload) => {
    const errors = payload?.errors;

    if (! errors || typeof errors !== 'object') {
        return payload?.message
            ?? 'Permintaan tidak dapat diproses.';
    }

    const firstError = Object.values(errors)
        .flat()
        .find((message) => typeof message === 'string');

    return firstError
        ?? payload?.message
        ?? 'Permintaan tidak dapat diproses.';
};

const createHiddenInput = (name, value) => {
    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = name;
    input.value = value;

    return input;
};

const createDialogController = (dialog) => {
    if (! dialog) {
        return null;
    }

    const form = dialog.querySelector(
        '[data-deletion-submit-form]'
    );

    const title = dialog.querySelector(
        '[data-deletion-dialog-title]'
    );

    const description = dialog.querySelector(
        '[data-deletion-dialog-description]'
    );

    const loading = dialog.querySelector(
        '[data-deletion-dialog-loading]'
    );

    const preview = dialog.querySelector(
        '[data-deletion-dialog-preview]'
    );

    const message = dialog.querySelector(
        '[data-deletion-dialog-message]'
    );

    const details = dialog.querySelector(
        '[data-deletion-dialog-details]'
    );

    const error = dialog.querySelector(
        '[data-deletion-dialog-error]'
    );

    const confirmButton = dialog.querySelector(
        '[data-deletion-dialog-confirm]'
    );

    const confirmLabel = dialog.querySelector(
        '[data-deletion-dialog-confirm-label]'
    );

    const scopeInput = dialog.querySelector(
        '[data-deletion-form-scope]'
    );

    const olderThanDaysInput = dialog.querySelector(
        '[data-deletion-form-older-than-days]'
    );

    const identifierContainer = dialog.querySelector(
        '[data-deletion-form-identifiers]'
    );

    const closeButtons = dialog.querySelectorAll(
        '[data-deletion-dialog-close], [data-deletion-dialog-cancel]'
    );

    let isSubmitting = false;

    const setBusy = (busy) => {
        loading?.classList.toggle('hidden', ! busy);

        if (confirmButton) {
            confirmButton.disabled = busy;
        }
    };

    const reset = () => {
        isSubmitting = false;
        form?.removeAttribute('action');
        form?.removeAttribute('aria-busy');

        if (title) {
            title.textContent = 'Hapus data secara permanen?';
        }

        if (description) {
            description.textContent =
                'Data yang sudah dihapus tidak dapat dipulihkan kembali.';
        }

        if (message) {
            message.textContent = '';
        }

        if (details) {
            details.replaceChildren();
        }

        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }

        preview?.classList.add('hidden');
        loading?.classList.remove('hidden');

        if (confirmButton) {
            confirmButton.disabled = true;
        }

        if (confirmLabel) {
            confirmLabel.textContent = 'Hapus permanen';
        }

        if (scopeInput) {
            scopeInput.value = '';
        }

        if (olderThanDaysInput) {
            olderThanDaysInput.value = '';
            olderThanDaysInput.disabled = true;
        }

        identifierContainer?.replaceChildren();
    };

    const close = () => {
        if (! isSubmitting) {
            dialog.close();
        }
    };

    closeButtons.forEach((button) => {
        button.addEventListener('click', close);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    dialog.addEventListener('cancel', (event) => {
        if (isSubmitting) {
            event.preventDefault();
        }
    });

    dialog.addEventListener('close', reset);

    form?.addEventListener('submit', () => {
        isSubmitting = true;
        form.setAttribute('aria-busy', 'true');

        if (confirmButton) {
            confirmButton.disabled = true;
        }

        if (confirmLabel) {
            confirmLabel.textContent = 'Menghapus...';
        }
    });

    const fillDetails = (items) => {
        if (! details) {
            return;
        }

        details.replaceChildren();

        Object.entries(items ?? {})
            .filter(([, count]) => Number(count) > 0)
            .forEach(([key, count]) => {
                const row = document.createElement('div');
                const term = document.createElement('dt');
                const value = document.createElement('dd');

                row.className =
                    'flex items-center justify-between gap-4';
                term.className = 'text-rose-700';
                value.className =
                    'font-semibold tabular-nums text-rose-950';

                term.textContent = detailLabels[key]
                    ?? key.replaceAll('_', ' ');
                value.textContent = String(count);

                row.append(term, value);
                details.append(row);
            });
    };

    const open = async ({
        previewUrl,
        purgeUrl,
        titleText,
        descriptionText,
        confirmText,
        scope,
        idField,
        identifiers,
        olderThanDays,
    }) => {
        reset();

        if (title) {
            title.textContent = titleText;
        }

        if (description) {
            description.textContent = descriptionText;
        }

        if (confirmLabel) {
            confirmLabel.textContent = confirmText;
        }

        if (form) {
            form.action = purgeUrl;
        }

        if (scopeInput) {
            scopeInput.value = scope;
        }

        if (
            olderThanDaysInput
            && Number.isInteger(olderThanDays)
        ) {
            olderThanDaysInput.disabled = false;
            olderThanDaysInput.value = String(olderThanDays);
        }

        identifiers.forEach((identifier) => {
            identifierContainer?.append(
                createHiddenInput(
                    `${idField}[]`,
                    identifier
                )
            );
        });

        if (! dialog.open) {
            dialog.showModal();
        }

        setBusy(true);

        const payload = {
            scope,
        };

        if (identifiers.length > 0) {
            payload[idField] = identifiers;
        }

        if (Number.isInteger(olderThanDays)) {
            payload.older_than_days = olderThanDays;
        }

        try {
            const response = await fetch(previewUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken() ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const responsePayload = await response.json()
                .catch(() => ({}));

            if (! response.ok) {
                throw new Error(
                    firstValidationMessage(responsePayload)
                );
            }

            const previewData = responsePayload.data;

            if (! previewData) {
                throw new Error(
                    'Preview penghapusan tidak tersedia.'
                );
            }

            if (message) {
                message.textContent = previewData.message;
            }

            fillDetails(previewData.details);
            loading?.classList.add('hidden');
            preview?.classList.remove('hidden');

            if (confirmButton) {
                confirmButton.disabled = Boolean(
                    previewData.is_empty
                );
            }

            if (
                previewData.is_empty
                && description
            ) {
                description.textContent =
                    'Tidak ada data yang cocok dengan pilihan ini.';
            }
        } catch (exception) {
            loading?.classList.add('hidden');

            if (error) {
                error.textContent = exception instanceof Error
                    ? exception.message
                    : 'Preview penghapusan gagal dimuat.';
                error.classList.remove('hidden');
            }
        }
    };

    return {
        open,
    };
};

const initializeManager = (manager) => {
    const dialogId = manager.dataset.dialogId;
    const dialog = dialogId
        ? document.getElementById(dialogId)
        : null;

    const dialogController = createDialogController(dialog);

    if (! dialogController) {
        return;
    }

    const previewUrl = manager.dataset.previewUrl;
    const purgeUrl = manager.dataset.purgeUrl;
    const idField = manager.dataset.idField;
    const resourceLabel = manager.dataset.resourceLabel
        ?? 'data';

    if (! previewUrl || ! purgeUrl || ! idField) {
        return;
    }

    const checkboxes = Array.from(
        manager.querySelectorAll('[data-deletion-checkbox]')
    );

    const selectPage = manager.querySelector(
        '[data-deletion-select-page]'
    );

    const selectionSummary = manager.querySelector(
        '[data-deletion-selection-summary]'
    );

    const selectedButtons = manager.querySelectorAll(
        '[data-deletion-selected-button]'
    );

    const selectedIdentifiers = () => {
        return checkboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.value);
    };

    const updateSelection = () => {
        const selectedCount = selectedIdentifiers().length;
        const allSelected = (
            checkboxes.length > 0
            && selectedCount === checkboxes.length
        );

        if (selectPage) {
            selectPage.checked = allSelected;
            selectPage.indeterminate = (
                selectedCount > 0
                && ! allSelected
            );
        }

        if (selectionSummary) {
            selectionSummary.textContent = selectedCount === 0
                ? 'Belum ada data dipilih'
                : `${selectedCount} ${resourceLabel} dipilih`;
        }

        selectedButtons.forEach((button) => {
            button.disabled = selectedCount === 0;
        });
    };

    selectPage?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectPage.checked;
        });

        updateSelection();
    });

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelection);
    });

    manager.addEventListener('click', (event) => {
        const trigger = event.target.closest(
            '[data-deletion-trigger]'
        );

        if (! trigger || ! manager.contains(trigger)) {
            return;
        }

        event.preventDefault();

        const scope = trigger.dataset.scope;
        const singleIdentifier = trigger.dataset.identifier;
        const identifiers = singleIdentifier
            ? [singleIdentifier]
            : selectedIdentifiers();

        if (
            scope === 'selected'
            && identifiers.length === 0
        ) {
            updateSelection();

            return;
        }

        const parsedDays = Number.parseInt(
            trigger.dataset.olderThanDays ?? '',
            10
        );

        dialogController.open({
            previewUrl,
            purgeUrl: trigger.dataset.purgeUrl
                ?? purgeUrl,
            titleText: trigger.dataset.title
                ?? `Hapus ${resourceLabel}?`,
            descriptionText: trigger.dataset.description
                ?? 'Periksa jumlah data sebelum melanjutkan.',
            confirmText: trigger.dataset.confirmLabel
                ?? 'Hapus permanen',
            scope,
            idField,
            identifiers,
            olderThanDays: Number.isNaN(parsedDays)
                ? null
                : parsedDays,
        });
    });

    updateSelection();
};

const initializeDataDeletion = () => {
    document
        .querySelectorAll('[data-deletion-manager]')
        .forEach(initializeManager);
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeDataDeletion
    );
} else {
    initializeDataDeletion();
}
