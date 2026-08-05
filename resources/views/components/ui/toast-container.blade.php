@php
    $initialToasts = collect([
        [
            'type' => 'success',
            'message' => session('success')
                ?? session('status'),
        ],
        [
            'type' => 'warning',
            'message' => session('warning'),
        ],
        [
            'type' => 'error',
            'message' => session('error'),
        ],
        [
            'type' => 'info',
            'message' => session('info'),
        ],
    ])
        ->filter(
            static fn (array $toast): bool => filled(
                $toast['message']
            )
        )
        ->values();

    $sessionToasts = collect(session('toasts', []))
        ->filter(
            static fn (mixed $toast): bool => is_array($toast)
                && filled($toast['message'] ?? null)
        )
        ->map(
            static fn (array $toast): array => [
                'type' => $toast['type'] ?? 'info',
                'message' => $toast['message'],
                'title' => $toast['title'] ?? null,
                'duration' => $toast['duration'] ?? null,
            ]
        );

    $initialToasts = $initialToasts
        ->concat($sessionToasts)
        ->values();
@endphp

<div
    id="laras-toast-region"
    data-laras-toast-region
    class="pointer-events-none fixed inset-x-4 bottom-4 z-[90] flex flex-col-reverse gap-3 sm:inset-x-auto sm:bottom-auto sm:right-5 sm:top-24 sm:w-[min(24rem,calc(100vw-2.5rem))] sm:flex-col"
    aria-label="Notifikasi aplikasi"
    aria-live="polite"
    aria-relevant="additions removals"
></div>

<script
    id="laras-initial-toasts"
    type="application/json"
>{!! $initialToasts->toJson(
    JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) !!}</script>
