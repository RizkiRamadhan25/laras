@props([
    'user' => null,
    'size' => 'md',
    'rounded' => 'xl',
])

@php
    $resolvedUser = $user ?? auth()->user();

    $sizeClass = match ($size) {
        'xs' => 'size-8 text-xs',
        'sm' => 'size-9 text-sm',
        'lg' => 'size-12 text-base',
        'xl' => 'size-20 text-xl',
        default => 'size-10 text-sm',
    };

    $roundedClass = match ($rounded) {
        'full' => 'rounded-full',
        '2xl' => 'rounded-2xl',
        default => 'rounded-xl',
    };

    $photoUrl = $resolvedUser?->profilePhotoUrl();

    $initials = collect(
        preg_split(
            '/\s+/',
            trim((string) ($resolvedUser?->name ?? ''))
        ) ?: []
    )
        ->filter()
        ->take(2)
        ->map(
            fn (string $part): string => mb_strtoupper(
                mb_substr($part, 0, 1)
            )
        )
        ->join('');

    if ($initials === '') {
        $initials = 'L';
    }
@endphp

<span
    {{ $attributes->class([
        'relative inline-flex shrink-0 overflow-hidden border border-slate-200 bg-laras-950 font-semibold text-white',
        $sizeClass,
        $roundedClass,
    ]) }}
    data-laras-avatar
    data-laras-avatar-user="{{ $resolvedUser?->getKey() }}"
>
    @if ($photoUrl !== null)
        <img
            src="{{ $photoUrl }}"
            alt="Foto profil {{ $resolvedUser?->name ?? 'pengguna' }}"
            class="absolute inset-0 size-full object-cover"
            loading="eager"
            decoding="async"
            data-laras-avatar-image
        >
    @else
        <span
            class="flex size-full items-center justify-center"
            aria-hidden="true"
            data-laras-avatar-fallback
        >
            {{ $initials }}
        </span>

        <span class="sr-only">
            Avatar {{ $resolvedUser?->name ?? 'pengguna' }}
        </span>
    @endif
</span>
