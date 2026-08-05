const STORAGE_KEY = 'laras:intro-shown:v1';
const MINIMUM_VISIBLE_TIME = 900;
const MAXIMUM_VISIBLE_TIME = 3000;
const EXIT_ANIMATION_TIME = 500;

function getLoadingScreen() {
    return document.querySelector('[data-laras-loading-screen]');
}

function hasIntroBeenShown() {
    try {
        return sessionStorage.getItem(STORAGE_KEY) === 'true';
    } catch {
        return false;
    }
}

function rememberIntroWasShown() {
    try {
        sessionStorage.setItem(STORAGE_KEY, 'true');
    } catch {
        // Aplikasi tetap berjalan jika sessionStorage tidak tersedia.
    }
}

function removeLoadingScreen(element) {
    if (!element || !element.isConnected) {
        return;
    }

    element.classList.add('is-leaving');
    element.setAttribute('aria-hidden', 'true');

    window.setTimeout(() => {
        element.remove();
        document.documentElement.classList.remove('laras-intro-active');
        document.body.classList.remove('laras-intro-active');
    }, EXIT_ANIMATION_TIME);
}

function initializeLoadingScreen() {
    const loadingScreen = getLoadingScreen();

    if (!loadingScreen) {
        return;
    }

    /*
     * Loader hanya tampil satu kali untuk setiap tab browser.
     */
    if (hasIntroBeenShown()) {
        loadingScreen.remove();

        document.documentElement.classList.remove('laras-intro-active');
        document.body.classList.remove('laras-intro-active');

        return;
    }

    document.documentElement.classList.add('laras-intro-active');
    document.body.classList.add('laras-intro-active');

    const startedAt = performance.now();
    let hasClosed = false;

    const close = () => {
        if (hasClosed) {
            return;
        }

        hasClosed = true;
        rememberIntroWasShown();

        const elapsedTime = performance.now() - startedAt;
        const remainingTime = Math.max(
            0,
            MINIMUM_VISIBLE_TIME - elapsedTime,
        );

        window.setTimeout(() => {
            removeLoadingScreen(loadingScreen);
        }, remainingTime);
    };

    /*
     * Jika seluruh resource halaman sudah selesai ketika script dijalankan,
     * tutup langsung setelah durasi minimum.
     */
    if (document.readyState === 'complete') {
        close();
    } else {
        window.addEventListener('load', close, {
            once: true,
        });
    }

    /*
     * Fallback: loader tetap ditutup meskipun event load tidak terpanggil
     * atau ada resource yang terlalu lama merespons.
     */
    window.setTimeout(close, MAXIMUM_VISIBLE_TIME);
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeLoadingScreen,
        {
            once: true,
        },
    );
} else {
    initializeLoadingScreen();
}
