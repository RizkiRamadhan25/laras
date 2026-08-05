const LIST_SELECTOR = '[data-account-order-list]';
const CARD_SELECTOR = '[data-account-order-card]';
const FORM_SELECTOR = '[data-account-move-form]';
const ANIMATION_DURATION = 320;

const REDUCED_MOTION = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
);

const busyStates = new WeakMap();
const animationTimers = new WeakMap();

let activeMoveForm = null;
let lockNoticeShown = false;

function cardsIn(list) {
    return Array.from(
        list.querySelectorAll(`:scope > ${CARD_SELECTOR}`)
    );
}

function capturePositions(list) {
    return new Map(
        cardsIn(list).map((card) => [
            card,
            card.getBoundingClientRect(),
        ])
    );
}

function clearMotionStyles(list) {
    const activeTimer =
        animationTimers.get(list);

    if (activeTimer !== undefined) {
        window.clearTimeout(activeTimer);
        animationTimers.delete(list);
    }

    cardsIn(list).forEach((card) => {
        card.style.removeProperty('transition');
        card.style.removeProperty('transform');
    });
}

function animateFromPositions(list, before) {
    clearMotionStyles(list);

    if (REDUCED_MOTION.matches) {
        return;
    }

    const cards = cardsIn(list);

    cards.forEach((card) => {
        const previous = before.get(card);
        const current = card.getBoundingClientRect();

        if (! previous) {
            return;
        }

        const deltaY = previous.top - current.top;

        if (Math.abs(deltaY) < 1) {
            return;
        }

        card.style.transition = 'none';
        card.style.transform = `translateY(${deltaY}px)`;
    });

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            if (REDUCED_MOTION.matches) {
                clearMotionStyles(list);

                return;
            }

            cards.forEach((card) => {
                card.style.transition = [
                    'transform',
                    `${ANIMATION_DURATION}ms`,
                    'cubic-bezier(0.22, 1, 0.36, 1)',
                ].join(' ');

                card.style.transform = 'translateY(0)';
            });
        });
    });

    const timer = window.setTimeout(() => {
        clearMotionStyles(list);
    }, ANIMATION_DURATION + 60);

    animationTimers.set(
        list,
        timer
    );
}

function updateMoveButtons(list) {
    const cards = cardsIn(list);

    cards.forEach((card, index) => {
        const up = card.querySelector(
            `${FORM_SELECTOR}[data-direction="up"] button`
        );

        const down = card.querySelector(
            `${FORM_SELECTOR}[data-direction="down"] button`
        );

        if (up instanceof HTMLButtonElement) {
            const disabled = index === 0;

            up.disabled = disabled;
            up.setAttribute(
                'aria-disabled',
                disabled ? 'true' : 'false'
            );
        }

        if (down instanceof HTMLButtonElement) {
            const disabled =
                index === cards.length - 1;

            down.disabled = disabled;
            down.setAttribute(
                'aria-disabled',
                disabled ? 'true' : 'false'
            );
        }
    });
}

function moveButtonsIn(list) {
    return Array.from(
        list.querySelectorAll(
            `${FORM_SELECTOR} button`
        )
    ).filter(
        (button) =>
            button instanceof HTMLButtonElement
    );
}

function setBusy(list, busy) {
    if (busy) {
        if (busyStates.has(list)) {
            return;
        }

        busyStates.set(list, {
            hadPointerLock:
                list.classList.contains(
                    'pointer-events-none'
                ),

            hadOpacity:
                list.classList.contains(
                    'opacity-75'
                ),

            hadSelectLock:
                list.classList.contains(
                    'select-none'
                ),
        });

        list.dataset.accountOrderingBusy =
            'true';

        list.setAttribute(
            'aria-busy',
            'true'
        );

        list.classList.add(
            'pointer-events-none',
            'opacity-75',
            'select-none'
        );

        moveButtonsIn(list).forEach(
            (button) => {
                button.disabled = true;

                button.setAttribute(
                    'aria-disabled',
                    'true'
                );
            }
        );

        return;
    }

    const state = busyStates.get(list);

    delete list.dataset.accountOrderingBusy;

    list.setAttribute(
        'aria-busy',
        'false'
    );

    if (
        state
        && ! state.hadPointerLock
    ) {
        list.classList.remove(
            'pointer-events-none'
        );
    }

    if (
        state
        && ! state.hadOpacity
    ) {
        list.classList.remove(
            'opacity-75'
        );
    }

    if (
        state
        && ! state.hadSelectLock
    ) {
        list.classList.remove(
            'select-none'
        );
    }

    busyStates.delete(list);

    updateMoveButtons(list);
}

function accountCards(list) {
    return cardsIn(list).filter(
        (card) =>
            card instanceof HTMLElement
    );
}

function currentAccountOrder(list) {
    return accountCards(list)
        .map(
            (card) =>
                card.dataset.accountId
        )
        .filter(
            (accountId) =>
                typeof accountId === 'string'
                && accountId !== ''
        );
}

function domOrderIsValid(list) {
    const cards = accountCards(list);
    const orderedAccountIds =
        currentAccountOrder(list);

    if (
        cards.length === 0
        || orderedAccountIds.length
            !== cards.length
    ) {
        return false;
    }

    return new Set(
        orderedAccountIds
    ).size === orderedAccountIds.length;
}

function normalizedAccountOrder(
    orderedAccountIds
) {
    if (! Array.isArray(orderedAccountIds)) {
        return null;
    }

    const normalized =
        orderedAccountIds.map(
            (accountId) =>
                String(accountId)
        );

    if (
        normalized.some(
            (accountId) =>
                accountId === ''
        )
    ) {
        return null;
    }

    if (
        new Set(normalized).size
        !== normalized.length
    ) {
        return null;
    }

    return normalized;
}

function serverOrderIsValid(
    list,
    orderedAccountIds
) {
    const serverOrder =
        normalizedAccountOrder(
            orderedAccountIds
        );

    if (! serverOrder) {
        return false;
    }

    const currentOrder =
        currentAccountOrder(list);

    if (
        serverOrder.length
        !== currentOrder.length
    ) {
        return false;
    }

    const currentIds =
        new Set(currentOrder);

    return serverOrder.every(
        (accountId) =>
            currentIds.has(accountId)
    );
}

function accountCardById(
    list,
    accountId
) {
    if (
        typeof accountId !== 'string'
        || accountId === ''
    ) {
        return null;
    }

    return accountCards(list).find(
        (card) =>
            card.dataset.accountId
                === accountId
    ) ?? null;
}

function shouldRestoreKeyboardFocus(
    submitter
) {
    if (! (submitter instanceof HTMLElement)) {
        return false;
    }

    if (document.activeElement === submitter) {
        return true;
    }

    try {
        return submitter.matches(
            ':focus-visible'
        );
    } catch {
        return false;
    }
}

function restoreMoveFocus(
    list,
    accountId,
    direction
) {
    const card = accountCardById(
        list,
        accountId
    );

    if (! card) {
        return;
    }

    const directionButton =
        card.querySelector(
            [
                FORM_SELECTOR,
                `[data-direction="${direction}"]`,
                'button',
            ].join(' ')
        );

    const target =
        directionButton
            instanceof HTMLButtonElement
        && ! directionButton.disabled
            ? directionButton
            : card;

    if (
        target === card
        && ! card.hasAttribute('tabindex')
    ) {
        card.setAttribute(
            'tabindex',
            '-1'
        );
    }

    window.requestAnimationFrame(() => {
        target.focus({
            preventScroll: true,
        });
    });
}

function moveCard(
    list,
    card,
    direction
) {
    const cards = cardsIn(list);
    const currentIndex =
        cards.indexOf(card);

    if (currentIndex < 0) {
        return false;
    }

    const targetIndex =
        direction === 'up'
            ? currentIndex - 1
            : currentIndex + 1;

    if (
        targetIndex < 0
        || targetIndex >= cards.length
    ) {
        updateMoveButtons(list);

        return false;
    }

    const targetCard =
        cards[targetIndex];

    const before = capturePositions(list);

    if (direction === 'up') {
        list.insertBefore(
            card,
            targetCard
        );
    } else {
        list.insertBefore(
            targetCard,
            card
        );
    }

    animateFromPositions(
        list,
        before
    );

    updateMoveButtons(list);

    return true;
}

function applyAccountOrder(
    list,
    orderedAccountIds
) {
    const cards = accountCards(list);

    const cardsById = new Map(
        cards.map(
            (card) => [
                card.dataset.accountId,
                card,
            ]
        )
    );

    const before = new Map(
        cards.map(
            (card) => [
                card,
                card.getBoundingClientRect(),
            ]
        )
    );

    orderedAccountIds.forEach(
        (accountId) => {
            const card =
                cardsById.get(
                    String(accountId)
                );

            if (card) {
                list.append(card);
            }
        }
    );

    animateFromPositions(
        list,
        before
    );

    updateMoveButtons(list);
}

function reconcileServerOrder(
    list,
    orderedAccountIds
) {
    if (
        ! serverOrderIsValid(
            list,
            orderedAccountIds
        )
    ) {
        return false;
    }

    const normalized =
        normalizedAccountOrder(
            orderedAccountIds
        );

    if (! normalized) {
        return false;
    }

    applyAccountOrder(
        list,
        normalized
    );

    return true;
}

function rollbackAccountOrder(
    list,
    previousOrder
) {
    if (
        ! serverOrderIsValid(
            list,
            previousOrder
        )
    ) {
        return;
    }

    applyAccountOrder(
        list,
        previousOrder
    );
}

async function submitMove(
    form,
    submitter
) {
    if (activeMoveForm !== null) {
        return;
    }

    const list = form.closest(
        LIST_SELECTOR
    );

    const card = form.closest(
        CARD_SELECTOR
    );

    if (
        ! (list instanceof HTMLElement)
        || ! (card instanceof HTMLElement)
    ) {
        form.submit();

        return;
    }

    const direction =
        form.dataset.direction;

    if (
        ! ['up', 'down'].includes(
            direction
        )
    ) {
        form.submit();

        return;
    }

    if (! domOrderIsValid(list)) {
        form.submit();

        return;
    }

    const accountId =
        card.dataset.accountId ?? '';

    if (accountId === '') {
        form.submit();

        return;
    }

    const restoreKeyboardFocus =
        shouldRestoreKeyboardFocus(
            submitter
        );

    const previousOrder =
        currentAccountOrder(list);

    activeMoveForm = form;

    const moved = moveCard(
        list,
        card,
        direction
    );

    if (! moved) {
        activeMoveForm = null;

        return;
    }

    setBusy(
        list,
        true
    );

    try {
        const response = await fetch(
            form.action,
            {
                method: 'POST',

                headers: {
                    Accept:
                        'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest',
                },

                credentials:
                    'same-origin',

                body: new FormData(
                    form
                ),
            }
        );

        const payload = await response
            .json()
            .catch(() => ({}));

        if (! response.ok) {
            throw new Error(
                typeof payload.message === 'string'
                && payload.message.trim() !== ''
                    ? payload.message
                    : [
                        'Urutan rekening gagal',
                        'diperbarui.',
                    ].join(' ')
            );
        }

        const reconciled =
            reconcileServerOrder(
                list,
                payload.ordered_account_ids
            );

        if (! reconciled) {
            throw new Error(
                [
                    'Respons urutan rekening',
                    'dari server tidak valid.',
                ].join(' ')
            );
        }

        window.LarasToast?.success(
            payload.message
                ?? 'Urutan rekening diperbarui.',
            {
                duration: 2600,
            }
        );
    } catch (error) {
        rollbackAccountOrder(
            list,
            previousOrder
        );

        console.error(
            'Account ordering failed.',
            error
        );

        const message =
            error instanceof Error
            && error.message.trim() !== ''
                ? error.message
                : [
                    'Urutan rekening tidak',
                    'dapat diperbarui.',
                ].join(' ');

        window.LarasToast?.error(
            message,
            {
                duration: 7000,
            }
        );
    } finally {
        setBusy(
            list,
            false
        );

        if (activeMoveForm === form) {
            activeMoveForm = null;
        }

        lockNoticeShown = false;

        if (restoreKeyboardFocus) {
            restoreMoveFocus(
                list,
                accountId,
                direction
            );
        }
    }
}

function initializeAccountOrdering() {
    document
        .querySelectorAll(
            LIST_SELECTOR
        )
        .forEach((list) => {
            if (! (list instanceof HTMLElement)) {
                return;
            }

            clearMotionStyles(list);

            list.setAttribute(
                'aria-busy',
                'false'
            );

            updateMoveButtons(list);
        });
}

function handleReducedMotionChange(
    event
) {
    if (! event.matches) {
        return;
    }

    document
        .querySelectorAll(
            LIST_SELECTOR
        )
        .forEach((list) => {
            if (list instanceof HTMLElement) {
                clearMotionStyles(list);
            }
        });
}

if (
    typeof REDUCED_MOTION.addEventListener
        === 'function'
) {
    REDUCED_MOTION.addEventListener(
        'change',
        handleReducedMotionChange
    );
} else {
    REDUCED_MOTION.addListener(
        handleReducedMotionChange
    );
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
                FORM_SELECTOR
            )
        ) {
            return;
        }

        if (activeMoveForm !== null) {
            event.preventDefault();

            if (! lockNoticeShown) {
                lockNoticeShown = true;

                window.LarasToast?.info(
                    [
                        'Tunggu pengurutan rekening',
                        'sebelumnya selesai.',
                    ].join(' '),
                    {
                        duration: 2500,
                    }
                );
            }

            return;
        }

        event.preventDefault();

        void submitMove(
            form,
            event.submitter
        );
    }
);

window.addEventListener(
    'pageshow',
    initializeAccountOrdering
);

initializeAccountOrdering();
