@props([
    'for' => 'password',
    'title' => 'Ketentuan kata sandi',
])

<div
    class="laras-password-rules"
    data-laras-password-requirements
    data-password-source="{{ $for }}"
    aria-live="polite"
>
    <p class="laras-password-rules__title">
        {{ $title }}
    </p>

    <ul class="laras-password-rules__list">
        @foreach ([
            'length' => 'Minimal 8 karakter',
            'uppercase' => 'Mengandung huruf besar',
            'lowercase' => 'Mengandung huruf kecil',
            'number' => 'Mengandung angka',
            'symbol' => 'Mengandung simbol',
        ] as $rule => $label)
            <li
                class="laras-password-rule"
                data-password-rule="{{ $rule }}"
                data-met="false"
            >
                <span
                    class="laras-password-rule__indicator"
                    aria-hidden="true"
                ></span>

                <span>{{ $label }}</span>
            </li>
        @endforeach
    </ul>
</div>
