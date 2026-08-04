const LIST_SELECTOR = '[data-account-order-list]';
const CARD_SELECTOR = '[data-account-order-card]';
const FORM_SELECTOR = '[data-account-move-form]';
const ANIMATION_DURATION = 320;

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
    list.setAttribute('aria-busy', busy ? 'true' : 'false');
    list.classList.toggle('is-busy', busy);

    list.querySelectorAll(`${FORM_SELECTOR} button`).forEach(
        (button) => {
            if (button instanceof HTMLButtonElement) {
                button.disabled = busy || button.disabled;
            }
        }
    );

    if (! busy) {
        updateMoveButtons(list);
    }
}

function moveCard(list, card, direction) {
    const sibling = direction === 'up'
        ? card.previousElementSibling
        : card.nextElementSibling;

    if (! sibling) {
        return false;
    }

    const before = capturePositions(list);

    if (direction === 'up') {
        list.insertBefore(card, sibling);
    } else {
        list.insertBefore(sibling, card);
    }

    animateFromPositions(list, before);
    updateMoveButtons(list);

    return true;
}

async function submitMove(form) {
    const list = form.closest(LIST_SELECTOR);
    const card = form.closest(CARD_SELECTOR);

    if (! list || ! card) {
        form.submit();

        return;
    }

    const direction = form.dataset.direction;

    if (! ['up', 'down'].includes(direction)) {
        form.submit();

        return;
    }

    const originalNext = card.nextElementSibling;
    const moved = moveCard(list, card, direction);

    if (! moved) {
        return;
    }

    setBusy(list, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: new FormData(form),
        });

        if (! response.ok) {
            throw new Error(
                `Account move request failed: ${response.status}`
            );
        }

        const payload = await response.json();

        window.LarasToast?.success(
            payload.message ?? 'Urutan rekening diperbarui.',
            {
                duration: 2600,
            }
        );
    } catch (error) {
        console.error(error);

        const before = capturePositions(list);

        if (originalNext?.isConnected) {
            list.insertBefore(card, originalNext);
        } else {
            list.append(card);
        }

        animateFromPositions(list, before);

        window.LarasToast?.error(
            'Urutan rekening tidak dapat diperbarui.'
        );
    } finally {
        setBusy(list, false);
    }
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (! (form instanceof HTMLFormElement)) {
        return;
    }

    if (! form.matches(FORM_SELECTOR)) {
        return;
    }

    event.preventDefault();

    submitMove(form);
});
