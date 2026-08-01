const ignoredFields = new Set([
    '_token',
    '_method',
]);

const formSignature = (form) => {
    const formData = new FormData(form);

    const entries = Array.from(
        formData.entries(),
    )
        .filter(([name, value]) => {
            if (ignoredFields.has(name)) {
                return false;
            }

            /*
             * File tidak dimasukkan ke signature.
             * Upload foto menggunakan form terpisah
             * dan langsung dikirim saat file dipilih.
             */
            return !(value instanceof File);
        })
        .map(([name, value]) => [
            name,
            String(value),
        ])
        .sort(([firstName], [secondName]) =>
            firstName.localeCompare(secondName),
        );

    return JSON.stringify(entries);
};

const setDirtyState = (
    form,
    isDirty,
) => {
    form.dataset.dirty = isDirty
        ? 'true'
        : 'false';

    const indicator = form.querySelector(
        '[data-unsaved-indicator]',
    );

    if (!indicator) {
        return;
    }

    indicator.classList.toggle(
        'hidden',
        !isDirty,
    );

    indicator.setAttribute(
        'aria-hidden',
        isDirty ? 'false' : 'true',
    );
};

const initializeTrackedForms = () => {
    const forms = Array.from(
        document.querySelectorAll(
            '[data-track-unsaved]',
        ),
    );

    forms.forEach((form) => {
        const initialSignature =
            formSignature(form);

        const updateDirtyState = () => {
            setDirtyState(
                form,
                formSignature(form)
                    !== initialSignature,
            );
        };

        form.dataset.submitting = 'false';

        form.addEventListener(
            'input',
            updateDirtyState,
        );

        form.addEventListener(
            'change',
            updateDirtyState,
        );

        form.addEventListener(
            'submit',
            () => {
                form.dataset.submitting =
                    'true';

                setDirtyState(
                    form,
                    false,
                );
            },
        );
    });

    window.addEventListener(
        'beforeunload',
        (event) => {
            const hasUnsavedChanges =
                forms.some(
                    (form) =>
                        form.dataset.dirty
                            === 'true'
                        && form.dataset.submitting
                            !== 'true',
                );

            if (!hasUnsavedChanges) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        },
    );
};

const activeClasses = [
    'bg-laras-50',
    'text-laras-800',
];

const inactiveClasses = [
    'text-slate-500',
];

const setActiveNavigation = (
    links,
    activeId,
) => {
    links.forEach((link) => {
        const isActive =
            link.getAttribute('href')
            === `#${activeId}`;

        activeClasses.forEach(
            (className) => {
                link.classList.toggle(
                    className,
                    isActive,
                );
            },
        );

        inactiveClasses.forEach(
            (className) => {
                link.classList.toggle(
                    className,
                    !isActive,
                );
            },
        );

        if (isActive) {
            link.setAttribute(
                'aria-current',
                'true',
            );

            return;
        }

        link.removeAttribute(
            'aria-current',
        );
    });
};

const initializeSettingsNavigation = () => {
    const links = Array.from(
        document.querySelectorAll(
            '[data-settings-nav-link]',
        ),
    );

    const sections = Array.from(
        document.querySelectorAll(
            '[data-settings-section]',
        ),
    );

    if (
        links.length === 0
        || sections.length === 0
    ) {
        return;
    }

    links.forEach((link) => {
        link.addEventListener(
            'click',
            () => {
                const targetId = link
                    .getAttribute('href')
                    ?.replace('#', '');

                if (targetId) {
                    setActiveNavigation(
                        links,
                        targetId,
                    );
                }
            },
        );
    });

    if (
        !('IntersectionObserver' in window)
    ) {
        return;
    }

    const observer =
        new IntersectionObserver(
            (entries) => {
                const visibleEntries =
                    entries
                        .filter(
                            (entry) =>
                                entry.isIntersecting,
                        )
                        .sort(
                            (
                                first,
                                second,
                            ) =>
                                second
                                    .intersectionRatio
                                - first
                                    .intersectionRatio,
                        );

                const activeEntry =
                    visibleEntries[0];

                if (!activeEntry) {
                    return;
                }

                setActiveNavigation(
                    links,
                    activeEntry.target.id,
                );
            },
            {
                rootMargin:
                    '-20% 0px -65% 0px',

                threshold: [
                    0,
                    0.1,
                    0.25,
                    0.5,
                ],
            },
        );

    sections.forEach((section) => {
        observer.observe(section);
    });

    const hash = window.location.hash
        .replace('#', '');

    if (
        hash
        && sections.some(
            (section) =>
                section.id === hash,
        )
    ) {
        setActiveNavigation(
            links,
            hash,
        );
    }
};

const initializeSettings = () => {
    initializeTrackedForms();
    initializeSettingsNavigation();
};

if (
    document.readyState === 'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initializeSettings,
    );
} else {
    initializeSettings();
}
