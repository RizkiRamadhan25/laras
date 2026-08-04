const BROWSER_SELECTOR = '[data-activity-browser]';
const FILTER_FORM_SELECTOR = '[data-activity-filter-form]';
const SEARCH_SELECTOR = '[data-activity-search]';
const SEARCH_DEBOUNCE = 350;

let requestController = null;
let searchTimer = null;

function currentBrowser() {
    return document.querySelector(BROWSER_SELECTOR);
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
    const search = browser.querySelector(
        SEARCH_SELECTOR
    );

    if (! search) {
        return;
    }

    search.focus({
        preventScroll: true,
    });

    search.value = query;

    try {
        const cursor = query.length;

        search.setSelectionRange(
            cursor,
            cursor
        );
    } catch {
        // Search input pada sebagian browser tidak mendukung
        // pengaturan selection range.
    }
}

export async function loadActivityBrowser(
    targetUrl,
    {
        historyMode = 'push',
        focusSearch = false,
        searchQuery = '',
    } = {}
) {
    const browser = currentBrowser();

    if (! browser) {
        window.location.assign(targetUrl);

        return;
    }

    requestController?.abort();

    const controller = new AbortController();

    requestController = controller;

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
                `Activity browser request failed: ${response.status}`
            );
        }

        const html = await response.text();
        const documentResult = new DOMParser()
            .parseFromString(html, 'text/html');

        const replacement = documentResult
            .querySelector(BROWSER_SELECTOR);

        if (! replacement) {
            throw new Error(
                'Activity browser fragment was not found.'
            );
        }

        browser.replaceWith(replacement);

        if (historyMode === 'push') {
            window.history.pushState(
                { larasActivityBrowser: true },
                '',
                targetUrl
            );
        } else if (historyMode === 'replace') {
            window.history.replaceState(
                { larasActivityBrowser: true },
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
            'Daftar aktivitas tidak dapat dimuat. Coba lagi beberapa saat.'
        );

        browser.classList.remove('is-loading');
        browser.removeAttribute('style');
        browser.setAttribute('aria-busy', 'false');
    } finally {
        if (requestController === controller) {
            requestController = null;
        }
    }
}

function submitActivityFilter(
    form,
    {
        historyMode = 'push',
        focusSearch = false,
    } = {}
) {
    const search = form.querySelector(
        SEARCH_SELECTOR
    );

    const query = search?.value ?? '';
    const url = buildFilterUrl(form);

    loadActivityBrowser(url.toString(), {
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
            '[data-activity-tab]',
            '[data-activity-reset]',
            '[data-activity-pagination] a',
        ].join(', ')
    );

    if (
        ! (link instanceof HTMLAnchorElement)
        || ! link.closest(BROWSER_SELECTOR)
        || shouldIgnoreLink(event, link)
    ) {
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

    loadActivityBrowser(url.toString());
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

    submitActivityFilter(form);
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

    window.clearTimeout(searchTimer);

    searchTimer = window.setTimeout(() => {
        submitActivityFilter(form, {
            historyMode: 'replace',
            focusSearch: true,
        });
    }, SEARCH_DEBOUNCE);
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

    submitActivityFilter(form, {
        historyMode: 'replace',
    });
});

window.addEventListener('popstate', () => {
    if (! currentBrowser()) {
        return;
    }

    loadActivityBrowser(window.location.href, {
        historyMode: 'none',
    });
});
