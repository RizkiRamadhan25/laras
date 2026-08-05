const BROWSER_SELECTOR = '[data-finance-browser]';
const FILTER_FORM_SELECTOR = '[data-finance-filter-form]';
const SEARCH_SELECTOR = '[data-finance-search]';
const SEARCH_DEBOUNCE = 350;

const requestControllers = new Map();
const searchTimers = new WeakMap();

function browserKey(browser) {
    return browser.dataset.financeBrowser ?? '';
}

function currentBrowser(key) {
    return Array.from(
        document.querySelectorAll(BROWSER_SELECTOR)
    ).find((browser) => browserKey(browser) === key) ?? null;
}

function buildFilterUrl(form) {
    const url = new URL(
        form.action,
        window.location.origin
    );

    const data = new FormData(form);

    data.forEach((value, key) => {
        const normalized = String(value).trim();

        if (normalized !== '') {
            url.searchParams.set(key, normalized);
        }
    });

    return url;
}

function shouldIgnoreLink(event, link) {
    return event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || link.target === '_blank'
        || link.hasAttribute('download');
}

function refreshDynamicUi(browser) {
    document.dispatchEvent(
        new CustomEvent('laras:icons-refresh')
    );

    document.dispatchEvent(
        new CustomEvent('laras:forms-refresh', {
            detail: {
                root: browser,
            },
        })
    );
}

function restoreSearchFocus(browser, query) {
    const search = browser.querySelector(SEARCH_SELECTOR);

    if (! (search instanceof HTMLInputElement)) {
        return;
    }

    search.focus({
        preventScroll: true,
    });

    search.value = query;

    try {
        const cursor = query.length;

        search.setSelectionRange(cursor, cursor);
    } catch {
        // Input search pada sebagian browser tidak mendukung
        // pengaturan selection range.
    }
}

async function loadFinanceBrowser(
    key,
    targetUrl,
    {
        historyMode = 'push',
        focusSearch = false,
        searchQuery = '',
    } = {}
) {
    const browser = currentBrowser(key);

    if (! browser) {
        window.location.assign(targetUrl);

        return;
    }

    requestControllers.get(key)?.abort();

    const controller = new AbortController();

    requestControllers.set(key, controller);

    browser.style.minHeight = `${browser.offsetHeight}px`;
    browser.classList.add('is-loading');
    browser.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(targetUrl, {
            method: 'GET',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (! response.ok) {
            throw new Error(
                `Finance browser request failed: ${response.status}`
            );
        }

        const html = await response.text();
        const documentResult = new DOMParser()
            .parseFromString(html, 'text/html');

        const replacement = Array.from(
            documentResult.querySelectorAll(BROWSER_SELECTOR)
        ).find(
            (candidate) => browserKey(candidate) === key
        );

        if (! replacement) {
            throw new Error(
                `Finance browser fragment "${key}" was not found.`
            );
        }

        browser.replaceWith(replacement);

        if (historyMode === 'push') {
            window.history.pushState(
                { larasFinanceBrowser: key },
                '',
                targetUrl
            );
        } else if (historyMode === 'replace') {
            window.history.replaceState(
                { larasFinanceBrowser: key },
                '',
                targetUrl
            );
        }

        refreshDynamicUi(replacement);

        if (focusSearch) {
            restoreSearchFocus(
                replacement,
                searchQuery
            );
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error(error);

        window.LarasToast?.error(
            'Data tidak dapat dimuat. Coba lagi beberapa saat.'
        );

        browser.classList.remove('is-loading');
        browser.removeAttribute('style');
        browser.setAttribute('aria-busy', 'false');
    } finally {
        if (requestControllers.get(key) === controller) {
            requestControllers.delete(key);
        }
    }
}

function submitFinanceFilter(
    form,
    {
        historyMode = 'push',
        focusSearch = false,
    } = {}
) {
    const browser = form.closest(BROWSER_SELECTOR);

    if (! browser) {
        return;
    }

    const key = browserKey(browser);
    const search = form.querySelector(SEARCH_SELECTOR);
    const query = search instanceof HTMLInputElement
        ? search.value
        : '';
    const url = buildFilterUrl(form);

    loadFinanceBrowser(key, url.toString(), {
        historyMode,
        focusSearch,
        searchQuery: query,
    });
}

document.addEventListener('click', (event) => {
    const target = event.target;

    if (! (target instanceof Element)) {
        return;
    }

    const link = target.closest(
        [
            '[data-finance-reset]',
            '[data-finance-pagination] a',
        ].join(', ')
    );

    if (! (link instanceof HTMLAnchorElement)) {
        return;
    }

    const browser = link.closest(BROWSER_SELECTOR);

    if (! browser || shouldIgnoreLink(event, link)) {
        return;
    }

    const url = new URL(
        link.href,
        window.location.origin
    );

    if (url.origin !== window.location.origin) {
        return;
    }

    event.preventDefault();

    loadFinanceBrowser(
        browserKey(browser),
        url.toString()
    );
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (
        ! (form instanceof HTMLFormElement)
        || ! form.matches(FILTER_FORM_SELECTOR)
        || ! form.closest(BROWSER_SELECTOR)
    ) {
        return;
    }

    event.preventDefault();

    submitFinanceFilter(form);
});

document.addEventListener('input', (event) => {
    const search = event.target;

    if (
        ! (search instanceof HTMLInputElement)
        || ! search.matches(SEARCH_SELECTOR)
    ) {
        return;
    }

    const form = search.closest(FILTER_FORM_SELECTOR);

    if (! (form instanceof HTMLFormElement)) {
        return;
    }

    const previousTimer = searchTimers.get(search);

    if (previousTimer) {
        window.clearTimeout(previousTimer);
    }

    const timer = window.setTimeout(() => {
        submitFinanceFilter(form, {
            historyMode: 'replace',
            focusSearch: true,
        });
    }, SEARCH_DEBOUNCE);

    searchTimers.set(search, timer);
});

document.addEventListener('change', (event) => {
    const control = event.target;

    if (
        ! (
            control instanceof HTMLSelectElement
            || control instanceof HTMLInputElement
        )
        || control.matches(SEARCH_SELECTOR)
    ) {
        return;
    }

    const form = control.closest(FILTER_FORM_SELECTOR);

    if (! (form instanceof HTMLFormElement)) {
        return;
    }

    submitFinanceFilter(form, {
        historyMode: 'replace',
    });
});

window.addEventListener('popstate', () => {
    const browser = document.querySelector(BROWSER_SELECTOR);

    if (! browser) {
        return;
    }

    loadFinanceBrowser(
        browserKey(browser),
        window.location.href,
        {
            historyMode: 'none',
        }
    );
});
