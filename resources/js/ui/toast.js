const DEFAULT_TITLES = {
    success: 'Berhasil',
    warning: 'Perlu perhatian',
    error: 'Terjadi kesalahan',
    info: 'Informasi',
};

const DEFAULT_DURATIONS = {
    success: 5000,
    info: 6000,
    warning: 7000,
    error: 8000,
};

const TOAST_STYLES = {
    success: {
        shell: 'border-emerald-200 bg-white',
        icon: 'bg-emerald-100 text-emerald-700',
        progress: 'bg-emerald-500',
        iconName: 'circle-check',
    },
    warning: {
        shell: 'border-amber-200 bg-white',
        icon: 'bg-amber-100 text-amber-700',
        progress: 'bg-amber-500',
        iconName: 'triangle-alert',
    },
    error: {
        shell: 'border-rose-200 bg-white',
        icon: 'bg-rose-100 text-rose-700',
        progress: 'bg-rose-500',
        iconName: 'circle-alert',
    },
    info: {
        shell: 'border-blue-200 bg-white',
        icon: 'bg-blue-100 text-blue-700',
        progress: 'bg-blue-500',
        iconName: 'bell-ring',
    },
};

const normalizeType = (type) => {
    return Object.hasOwn(TOAST_STYLES, type)
        ? type
        : 'info';
};

const parseDuration = (duration, type) => {
    const parsedDuration = Number(duration);

    if (
        Number.isFinite(parsedDuration)
        && parsedDuration >= 1000
    ) {
        return parsedDuration;
    }

    return DEFAULT_DURATIONS[type];
};

const dispatchIconRefresh = () => {
    document.dispatchEvent(
        new CustomEvent('laras:icons-refresh')
    );
};

const createToastController = (element, duration) => {
    const progress = element.querySelector(
        '[data-laras-toast-progress]'
    );

    let timerId = null;
    let remaining = duration;
    let startedAt = null;
    let closed = false;
    let dragging = false;
    let pointerStartX = 0;
    let pointerDeltaX = 0;

    const clearTimer = () => {
        if (timerId !== null) {
            window.clearTimeout(timerId);
            timerId = null;
        }
    };

    const close = (direction = 1) => {
        if (closed) {
            return;
        }

        closed = true;
        clearTimer();

        element.style.transform = `translateX(${direction * 120}%)`;
        element.style.opacity = '0';
        element.style.marginBlock = '0';

        window.setTimeout(() => {
            element.remove();
        }, 220);
    };

    const resume = () => {
        if (closed || timerId !== null || remaining <= 0) {
            return;
        }

        startedAt = performance.now();
        timerId = window.setTimeout(close, remaining);

        if (progress) {
            progress.style.animationPlayState = 'running';
        }
    };

    const pause = () => {
        if (closed) {
            return;
        }

        if (timerId !== null && startedAt !== null) {
            remaining = Math.max(
                0,
                remaining - (performance.now() - startedAt)
            );
        }

        clearTimer();
        startedAt = null;

        if (progress) {
            progress.style.animationPlayState = 'paused';
        }
    };

    element
        .querySelector('[data-laras-toast-close]')
        ?.addEventListener('click', () => close());

    element.addEventListener('mouseenter', pause);
    element.addEventListener('mouseleave', resume);
    element.addEventListener('focusin', pause);
    element.addEventListener('focusout', resume);

    element.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        if (
            event.target.closest('[data-laras-toast-close]')
        ) {
            return;
        }

        dragging = true;
        pointerStartX = event.clientX;
        pointerDeltaX = 0;
        pause();
        element.setPointerCapture(event.pointerId);
        element.style.transition = 'none';
    });

    element.addEventListener('pointermove', (event) => {
        if (! dragging) {
            return;
        }

        pointerDeltaX = event.clientX - pointerStartX;
        const opacity = Math.max(
            0.35,
            1 - Math.abs(pointerDeltaX) / 240
        );

        element.style.transform = `translateX(${pointerDeltaX}px)`;
        element.style.opacity = String(opacity);
    });

    const finishSwipe = () => {
        if (! dragging) {
            return;
        }

        dragging = false;
        element.style.transition = '';

        if (Math.abs(pointerDeltaX) >= 80) {
            close(pointerDeltaX < 0 ? -1 : 1);

            return;
        }

        element.style.transform = 'translateX(0)';
        element.style.opacity = '1';
        resume();
    };

    element.addEventListener('pointerup', finishSwipe);
    element.addEventListener('pointercancel', finishSwipe);

    if (progress) {
        progress.style.animationDuration = `${duration}ms`;
    }

    window.requestAnimationFrame(() => {
        element.style.transform = 'translateX(0)';
        element.style.opacity = '1';
    });

    resume();

    return {
        close,
    };
};

const showToast = ({
    type = 'info',
    title = null,
    message,
    duration = null,
} = {}) => {
    const region = document.querySelector(
        '[data-laras-toast-region]'
    );

    if (! region || typeof message !== 'string' || ! message.trim()) {
        return null;
    }

    const normalizedType = normalizeType(type);
    const style = TOAST_STYLES[normalizedType];
    const normalizedDuration = parseDuration(
        duration,
        normalizedType
    );

    const toast = document.createElement('article');
    const role = normalizedType === 'error'
        ? 'alert'
        : 'status';

    toast.setAttribute('role', role);
    toast.setAttribute('tabindex', '0');
    toast.className = [
        'pointer-events-auto relative overflow-hidden rounded-2xl border p-4 pr-12 shadow-2xl',
        'opacity-0 transition duration-200 ease-out',
        'will-change-transform touch-pan-y',
        style.shell,
    ].join(' ');

    toast.style.transform = 'translateX(110%)';

    const content = document.createElement('div');
    content.className = 'flex items-start gap-3';

    const iconShell = document.createElement('span');
    iconShell.className = [
        'flex size-10 shrink-0 items-center justify-center rounded-xl',
        style.icon,
    ].join(' ');

    const icon = document.createElement('i');
    icon.dataset.lucide = style.iconName;
    icon.className = 'size-5';
    iconShell.append(icon);

    const text = document.createElement('div');
    text.className = 'min-w-0 flex-1 pt-0.5';

    const heading = document.createElement('p');
    heading.className = 'text-sm font-semibold text-slate-950';
    heading.textContent = title?.trim()
        || DEFAULT_TITLES[normalizedType];

    const paragraph = document.createElement('p');
    paragraph.className = 'mt-1 text-sm leading-6 text-slate-600';
    paragraph.textContent = message.trim();

    text.append(heading, paragraph);
    content.append(iconShell, text);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.dataset.larasToastClose = '';
    closeButton.className = 'absolute right-2.5 top-2.5 inline-flex size-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-laras-500 focus:ring-offset-2';
    closeButton.setAttribute('aria-label', 'Tutup notifikasi');

    const closeIcon = document.createElement('i');
    closeIcon.dataset.lucide = 'x';
    closeIcon.className = 'size-4';
    closeButton.append(closeIcon);

    const progress = document.createElement('span');
    progress.dataset.larasToastProgress = '';
    progress.className = [
        'absolute inset-x-0 bottom-0 h-1 origin-left',
        style.progress,
    ].join(' ');

    toast.append(content, closeButton, progress);
    region.append(toast);

    dispatchIconRefresh();

    return createToastController(
        toast,
        normalizedDuration
    );
};

const readInitialToasts = () => {
    const payload = document.getElementById(
        'laras-initial-toasts'
    );

    if (! payload) {
        return [];
    }

    try {
        const parsed = JSON.parse(payload.textContent);

        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        console.error(
            'Payload notifikasi awal Laras tidak valid.',
            error
        );

        return [];
    }
};

const initializeToasts = () => {
    const api = {
        show: showToast,
        success: (message, options = {}) => showToast({
            ...options,
            type: 'success',
            message,
        }),
        warning: (message, options = {}) => showToast({
            ...options,
            type: 'warning',
            message,
        }),
        error: (message, options = {}) => showToast({
            ...options,
            type: 'error',
            message,
        }),
        info: (message, options = {}) => showToast({
            ...options,
            type: 'info',
            message,
        }),
    };

    window.LarasToast = api;

    document.addEventListener('laras:toast', (event) => {
        api.show(event.detail ?? {});
    });

    readInitialToasts().forEach((toast) => {
        api.show(toast);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeToasts
    );
} else {
    initializeToasts();
}
