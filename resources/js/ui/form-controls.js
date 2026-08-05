const FIELD_SELECTOR = '[data-laras-field]';
const CONTROL_SELECTOR = '[data-laras-field-control]';
const PASSWORD_SELECTOR = '[data-laras-password-field]';
const PASSWORD_REQUIREMENTS_SELECTOR =
    '[data-laras-password-requirements]';

function isControlFilled(control) {
    if (control instanceof HTMLSelectElement) {
        return control.value !== '';
    }

    return String(control.value ?? '').trim() !== '';
}

function syncFieldState(field) {
    const control = field.querySelector(CONTROL_SELECTOR);

    if (! control) {
        return;
    }

    field.dataset.filled = isControlFilled(control)
        ? 'true'
        : 'false';
}

function initializeField(field) {
    if (field.dataset.larasFieldReady === 'true') {
        syncFieldState(field);

        return;
    }

    const control = field.querySelector(CONTROL_SELECTOR);

    if (! control) {
        return;
    }

    const sync = () => syncFieldState(field);

    control.addEventListener('input', sync);
    control.addEventListener('change', sync);
    control.addEventListener('blur', sync);
    control.addEventListener('animationstart', sync);

    field.dataset.larasFieldReady = 'true';

    sync();

    window.setTimeout(sync, 0);
    window.setTimeout(sync, 250);
}

function setPasswordVisibility(field, visible) {
    const input = field.querySelector(
        'input[data-laras-field-control]'
    );

    const button = field.querySelector(
        '[data-laras-password-toggle]'
    );

    if (! input || ! button) {
        return;
    }

    input.type = visible ? 'text' : 'password';
    field.dataset.passwordVisible = visible
        ? 'true'
        : 'false';

    const label = visible
        ? 'Sembunyikan kata sandi'
        : 'Tampilkan kata sandi';

    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    button.setAttribute(
        'aria-pressed',
        visible ? 'true' : 'false'
    );

    input.focus({
        preventScroll: true,
    });

    const cursorPosition = input.value.length;

    try {
        input.setSelectionRange(
            cursorPosition,
            cursorPosition
        );
    } catch {
        // Beberapa browser tidak mendukung selection range
        // pada tipe input tertentu.
    }
}

function initializePasswordField(field) {
    if (field.dataset.larasPasswordReady === 'true') {
        return;
    }

    const button = field.querySelector(
        '[data-laras-password-toggle]'
    );

    if (! button) {
        return;
    }

    button.addEventListener('click', () => {
        const visible =
            field.dataset.passwordVisible === 'true';

        setPasswordVisibility(field, ! visible);
    });

    field.dataset.larasPasswordReady = 'true';
}

function passwordRuleState(value) {
    return {
        length: value.length >= 8,
        uppercase: /\p{Lu}/u.test(value),
        lowercase: /\p{Ll}/u.test(value),
        number: /\p{N}/u.test(value),
        symbol: /[\p{P}\p{S}]/u.test(value),
    };
}

function syncPasswordRequirements(requirements) {
    const source = requirements.dataset.passwordSource;

    if (! source) {
        return;
    }

    const input = document.getElementById(source)
        ?? document.querySelector(
            `[name="${CSS.escape(source)}"]`
        );

    if (! input) {
        return;
    }

    const rules = passwordRuleState(
        String(input.value ?? '')
    );

    requirements
        .querySelectorAll('[data-password-rule]')
        .forEach((item) => {
            const rule = item.dataset.passwordRule;
            const met = rules[rule] === true;

            item.dataset.met = met ? 'true' : 'false';
        });
}

function initializePasswordRequirements(requirements) {
    if (
        requirements.dataset
            .larasPasswordRequirementsReady === 'true'
    ) {
        syncPasswordRequirements(requirements);

        return;
    }

    const source = requirements.dataset.passwordSource;

    if (! source) {
        return;
    }

    const input = document.getElementById(source)
        ?? document.querySelector(
            `[name="${CSS.escape(source)}"]`
        );

    if (! input) {
        return;
    }

    const sync = () => {
        syncPasswordRequirements(requirements);
    };

    input.addEventListener('input', sync);
    input.addEventListener('change', sync);
    input.addEventListener('animationstart', sync);

    requirements.dataset
        .larasPasswordRequirementsReady = 'true';

    sync();
}

function initializeModernForms(root = document) {
    root.querySelectorAll(FIELD_SELECTOR).forEach(
        initializeField
    );

    root.querySelectorAll(PASSWORD_SELECTOR).forEach(
        initializePasswordField
    );

    root
        .querySelectorAll(PASSWORD_REQUIREMENTS_SELECTOR)
        .forEach(initializePasswordRequirements);
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        () => initializeModernForms(),
        {
            once: true,
        }
    );
} else {
    initializeModernForms();
}

document.addEventListener(
    'laras:forms-refresh',
    (event) => {
        const root = event.detail?.root;

        initializeModernForms(
            root instanceof Element
                ? root
                : document
        );
    }
);

window.LarasForms = {
    refresh(root = document) {
        initializeModernForms(root);
    },
};
