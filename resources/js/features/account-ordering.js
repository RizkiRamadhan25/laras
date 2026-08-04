const LIST_SELECTOR = '[data-account-order-list]';
const CARD_SELECTOR = '[data-account-order-card]';
const FORM_SELECTOR = '[data-account-move-form]';
const ANIMATION_DURATION = 320;

const REDUCED_MOTION = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
);

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

function animateFromPositions(list, before) {
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

    window.setTimeout(() => {
        cards.forEach((card) => {
            card.style.removeProperty('transition');
            card.style.removeProperty('transform');
        });
    }, ANIMATION_DURATION + 60);
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
            up.disabled = index === 0;
        }

        if (down instanceof HTMLButtonElement) {
            down.disabled = index === cards.length - 1;
        }
    });
}

function setBusy(list, busy) {
    list.setAttribute(
        'aria-busy',
        busy ? 'true' : 'false'
    );

    list.classList.toggle(
        'is-busy',
        busy
    );

    list
        .querySelectorAll(`${FORM_SELECTOR} button`)
        .forEach((button) => {
            if (button instanceof HTMLButtonElement) {
                button.disabled = busy || button.disabled;
            }
        });

    if (! busy) {
        updateMoveButtons(list);
    }
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

function moveCard(
    list,
    card,
    direction
) {
    const sibling = direction === 'up'
        ? card.previousElementSibling
        : card.nextElementSibling;

    if (! sibling) {
        return false;
    }

    const before = capturePositions(list);

    if (direction === 'up') {
        list.insertBefore(
            card,
            sibling
        );
    } else {
        list.insertBefore(
            sibling,
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

async function submitMove(form) {
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

    const previousOrder =
        currentAccountOrder(list);

    const moved = moveCard(
        list,
        card,
        direction
    );

    if (! moved) {
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
                FORM_SELECTOR
            )
        ) {
            return;
        }

        event.preventDefault();

        void submitMove(form);
    }
);
