const DEBOUNCE_DELAY = 275;
const MINIMUM_QUERY_LENGTH = 2;

const isEditableTarget = (target) => {
    if (! (target instanceof HTMLElement)) {
        return false;
    }

    return target.matches(
        'input, textarea, select, [contenteditable="true"]'
    );
};

const createTextElement = (
    tagName,
    className,
    text
) => {
    const element = document.createElement(tagName);

    element.className = className;
    element.textContent = text ?? '';

    return element;
};

const initializeGlobalSearch = (root) => {
    const endpoint = root.dataset.searchEndpoint;
    const openButtons = root.querySelectorAll(
        '[data-global-search-open]'
    );
    const dialog = root.querySelector(
        '[data-global-search-dialog]'
    );
    const closeButton = root.querySelector(
        '[data-global-search-close]'
    );
    const input = root.querySelector(
        '[data-global-search-input]'
    );
    const initialPanel = root.querySelector(
        '[data-global-search-initial]'
    );
    const loadingPanel = root.querySelector(
        '[data-global-search-loading]'
    );
    const resultsPanel = root.querySelector(
        '[data-global-search-results]'
    );
    const groupsContainer = root.querySelector(
        '[data-global-search-groups]'
    );
    const emptyPanel = root.querySelector(
        '[data-global-search-empty]'
    );
    const errorPanel = root.querySelector(
        '[data-global-search-error]'
    );

    if (
        ! endpoint
        || ! dialog
        || ! input
        || ! groupsContainer
    ) {
        return;
    }

    const cache = new Map();
    let debounceTimer = null;
    let requestController = null;
    let resultLinks = [];
    let activeIndex = -1;

    const hideAllPanels = () => {
        [
            initialPanel,
            loadingPanel,
            resultsPanel,
            emptyPanel,
            errorPanel,
        ].forEach((panel) => {
            panel?.classList.add('hidden');
        });
    };

    const showPanel = (panel) => {
        hideAllPanels();
        panel?.classList.remove('hidden');
    };

    const refreshIcons = () => {
        document.dispatchEvent(
            new CustomEvent('laras:icons-refresh')
        );
    };

    const setActiveResult = (index) => {
        resultLinks.forEach((link) => {
            link.classList.remove(
                'border-laras-200',
                'bg-laras-50'
            );

            link.setAttribute('aria-selected', 'false');
        });

        if (resultLinks.length === 0) {
            activeIndex = -1;
            return;
        }

        activeIndex = (
            index + resultLinks.length
        ) % resultLinks.length;

        const activeLink = resultLinks[activeIndex];

        activeLink.classList.add(
            'border-laras-200',
            'bg-laras-50'
        );

        activeLink.setAttribute('aria-selected', 'true');

        activeLink.scrollIntoView({
            block: 'nearest',
        });
    };

    const createResultLink = (item) => {
        const link = document.createElement('a');

        link.href = item.url;
        link.className = [
            'group flex items-center gap-3 rounded-2xl',
            'border border-transparent px-3 py-3',
            'transition hover:border-laras-200',
            'hover:bg-laras-50 focus:outline-none',
            'focus:ring-4 focus:ring-laras-100',
        ].join(' ');

        link.dataset.globalSearchResult = item.id;
        link.setAttribute('role', 'option');
        link.setAttribute('aria-selected', 'false');

        const iconShell = document.createElement('span');

        iconShell.className = [
            'flex size-10 shrink-0 items-center',
            'justify-center rounded-xl bg-slate-100',
            'text-slate-600 transition',
            'group-hover:bg-white group-hover:text-laras-700',
        ].join(' ');

        const icon = document.createElement('i');

        icon.dataset.lucide = item.icon || 'search';
        icon.className = 'size-4';
        iconShell.append(icon);

        const copy = document.createElement('span');
        copy.className = 'min-w-0 flex-1';

        copy.append(
            createTextElement(
                'span',
                'block truncate text-sm font-semibold text-slate-900',
                item.title
            )
        );

        copy.append(
            createTextElement(
                'span',
                'mt-0.5 block truncate text-xs text-slate-400',
                item.description
            )
        );

        const meta = createTextElement(
            'span',
            'hidden shrink-0 text-right text-[11px] font-medium text-slate-400 sm:block',
            item.meta
        );

        link.append(iconShell, copy, meta);

        link.addEventListener('mouseenter', () => {
            const index = resultLinks.indexOf(link);

            if (index >= 0) {
                setActiveResult(index);
            }
        });

        return link;
    };

    const renderResults = (payload) => {
        groupsContainer.replaceChildren();
        resultLinks = [];
        activeIndex = -1;

        if (! payload.groups || payload.total === 0) {
            showPanel(emptyPanel);
            refreshIcons();
            return;
        }

        payload.groups.forEach((group) => {
            const section = document.createElement('section');
            section.className = 'space-y-1';

            const heading = createTextElement(
                'p',
                'px-2 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400',
                group.label
            );

            const list = document.createElement('div');
            list.setAttribute('role', 'listbox');

            group.items.forEach((item) => {
                const link = createResultLink(item);
                resultLinks.push(link);
                list.append(link);
            });

            section.append(heading, list);
            groupsContainer.append(section);
        });

        showPanel(resultsPanel);
        setActiveResult(0);
        refreshIcons();
    };

    const fetchResults = async (query) => {
        if (cache.has(query)) {
            renderResults(cache.get(query));
            return;
        }

        requestController?.abort();
        requestController = new AbortController();

        showPanel(loadingPanel);

        try {
            const url = new URL(
                endpoint,
                window.location.origin
            );

            url.searchParams.set('q', query);

            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });

            if (! response.ok) {
                throw new Error(
                    `Global search failed with ${response.status}`
                );
            }

            const payload = await response.json();
            cache.set(query, payload);
            renderResults(payload);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(
                'Pencarian global Laras gagal.',
                error
            );

            showPanel(errorPanel);
            refreshIcons();
        }
    };

    const handleQuery = () => {
        const query = input.value
            .trim()
            .replace(/\s+/g, ' ');

        window.clearTimeout(debounceTimer);
        requestController?.abort();

        if (query.length < MINIMUM_QUERY_LENGTH) {
            groupsContainer.replaceChildren();
            resultLinks = [];
            activeIndex = -1;
            showPanel(initialPanel);
            return;
        }

        debounceTimer = window.setTimeout(() => {
            fetchResults(query);
        }, DEBOUNCE_DELAY);
    };

    const open = () => {
        if (! dialog.open) {
            dialog.showModal();
        }

        window.requestAnimationFrame(() => {
            input.focus();
            input.select();
        });
    };

    const close = () => {
        window.clearTimeout(debounceTimer);
        requestController?.abort();

        if (dialog.open) {
            dialog.close();
        }

        input.value = '';
        groupsContainer.replaceChildren();
        resultLinks = [];
        activeIndex = -1;
        showPanel(initialPanel);
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', open);
    });

    closeButton?.addEventListener('click', close);
    input.addEventListener('input', handleQuery);

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveResult(activeIndex + 1);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveResult(activeIndex - 1);
            return;
        }

        if (
            event.key === 'Enter'
            && activeIndex >= 0
        ) {
            event.preventDefault();
            resultLinks[activeIndex]?.click();
        }
    });

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        const commandShortcut = (
            event.ctrlKey || event.metaKey
        ) && event.key.toLowerCase() === 'k';

        const slashShortcut = event.key === '/'
            && ! event.ctrlKey
            && ! event.metaKey
            && ! event.altKey
            && ! isEditableTarget(event.target);

        if (commandShortcut || slashShortcut) {
            event.preventDefault();
            open();
        }
    });
};

const initializeGlobalSearches = () => {
    document
        .querySelectorAll('[data-laras-global-search]')
        .forEach(initializeGlobalSearch);
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeGlobalSearches,
        {
            once: true,
        }
    );
} else {
    initializeGlobalSearches();
}
