@props([
    'name',
    'label',
    'id' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'autocomplete' => 'current-password',
    'tone' => 'default',
])

@php
    $id ??= str_replace(['[', ']', '.'], '_', $name);
    $error ??= $errors->first($name);
    $hintId = $hint ? $id.'-hint' : null;
    $errorId = $error ? $id.'-error' : null;
    $describedBy = collect([$hintId, $errorId])
        ->filter()
        ->implode(' ');
@endphp

<div
    class="laras-field laras-field--password"
    data-laras-field
    data-laras-password-field
    data-filled="false"
    data-invalid="{{ $error ? 'true' : 'false' }}"
    data-password-visible="false"
    data-tone="{{ $tone }}"
>
    <div class="laras-field__control">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            placeholder=" "
            data-laras-field-control
            autocomplete="{{ $autocomplete }}"
            @if ($required)
                required
            @endif
            @if ($describedBy !== '')
                aria-describedby="{{ $describedBy }}"
            @endif
            aria-invalid="{{ $error ? 'true' : 'false' }}"
            {{ $attributes->class('laras-field__input') }}
        >

        <label
            for="{{ $id }}"
            class="laras-field__label"
        >
            {{ $label }}

            @if ($required)
                <span
                    class="laras-field__required"
                    aria-hidden="true"
                >*</span>
            @endif
        </label>

        <button
            type="button"
            class="laras-field__password-toggle"
            data-laras-password-toggle
            aria-label="Tampilkan kata sandi"
            aria-pressed="false"
            title="Tampilkan kata sandi"
        >
            <svg
                data-laras-password-hidden-icon
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path d="M2.06 12.35a1 1 0 0 1 0-.7C3.73 7.55 7.3 5 12 5c4.7 0 8.27 2.55 9.94 6.65a1 1 0 0 1 0 .7C20.27 16.45 16.7 19 12 19c-4.7 0-8.27-2.55-9.94-6.65Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>

            <svg
                data-laras-password-visible-icon
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path d="m2 2 20 20"></path>
                <path d="M6.71 6.71C4.9 7.88 3.48 9.57 2.61 11.65a1 1 0 0 0 0 .7C4.27 16.45 7.84 19 12 19c1.39 0 2.65-.22 3.78-.62"></path>
                <path d="M10.73 5.08C11.14 5.03 11.56 5 12 5c4.7 0 8.27 2.55 9.94 6.65a1 1 0 0 1 0 .7 11.1 11.1 0 0 1-2.23 3.36"></path>
                <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path>
            </svg>
        </button>

        <span
            class="laras-field__focus-line"
            aria-hidden="true"
        ></span>
    </div>

    @if ($error)
        <p
            id="{{ $errorId }}"
            class="laras-field__message laras-field__message--error"
            role="alert"
        >
            <span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p
            id="{{ $hintId }}"
            class="laras-field__message laras-field__message--hint"
        >
            {{ $hint }}
        </p>
    @endif
</div>
