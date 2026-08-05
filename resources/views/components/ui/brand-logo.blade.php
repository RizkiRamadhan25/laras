@props([
    'compact' => false,
    'showSubtitle' => true,
])

@php
    $logoClasses = $compact
        ? 'h-8 w-auto max-w-[112px] object-contain object-left'
        : 'h-10 w-auto max-w-[160px] object-contain object-left';
@endphp

<a
    href="{{ route('dashboard') }}"
    {{ $attributes->class([
        'group inline-flex min-w-0 items-center',
        'gap-3' => ! $compact,
    ]) }}
    aria-label="Buka Dashboard Laras"
>
    <img
        src="{{ asset('images/branding/laras-logo.png') }}?v=2"
        alt="Laras"
        class="{{ $logoClasses }} transition duration-200 group-hover:scale-[1.02]"
        width="320"
        height="144"
        loading="eager"
        decoding="async"
        draggable="false"
    >

    @if ($showSubtitle && ! $compact)
        <span class="sr-only">
            Personal management
        </span>
    @endif
</a>
