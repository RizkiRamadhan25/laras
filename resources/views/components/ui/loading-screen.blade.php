@props([
    'message' => 'Menyelaraskan ruangmu...',
])

<div
    id="laras-loading-screen"
    data-laras-loading-screen
    class="laras-intro"
    role="status"
    aria-live="polite"
    aria-label="Laras sedang menyiapkan aplikasi"
>
    {{-- Decorative background --}}
    <div
        class="laras-intro__background"
        aria-hidden="true"
    >
        <span class="laras-intro__grid"></span>

        <span
            class="laras-intro__blob laras-intro__blob--one"
        ></span>

        <span
            class="laras-intro__blob laras-intro__blob--two"
        ></span>

        <span
            class="laras-intro__blob laras-intro__blob--three"
        ></span>

        <span
            class="laras-intro__particle laras-intro__particle--one"
        ></span>

        <span
            class="laras-intro__particle laras-intro__particle--two"
        ></span>

        <span
            class="laras-intro__particle laras-intro__particle--three"
        ></span>

        <span
            class="laras-intro__particle laras-intro__particle--four"
        ></span>
    </div>

    <div class="laras-intro__content">
        <div
            class="laras-intro__scene"
            aria-hidden="true"
        >
            <div class="laras-intro__halo"></div>

            <div
                class="laras-intro__orbit laras-intro__orbit--outer"
            ></div>

            <div
                class="laras-intro__orbit laras-intro__orbit--inner"
            ></div>

            <div class="laras-intro__brand-card">
                <span class="laras-intro__brand-glow"></span>
                <span class="laras-intro__brand-shine"></span>

                <img
                    src="{{ asset('images/branding/laras-logo.png') }}"
                    alt=""
                    class="laras-intro__logo"
                    width="320"
                    height="144"
                    loading="eager"
                    fetchpriority="high"
                    decoding="sync"
                    draggable="false"
                >
            </div>
        </div>

        <div class="laras-intro__copy">
            <p class="laras-intro__message">
                {{ $message }}
            </p>

            <div
                class="laras-intro__progress"
                aria-hidden="true"
            >
                <span class="laras-intro__progress-bar"></span>
                <span class="laras-intro__progress-shimmer"></span>
            </div>

            <p class="laras-intro__hint">
                Menyiapkan pengalaman personalmu
            </p>
        </div>
    </div>
</div>
