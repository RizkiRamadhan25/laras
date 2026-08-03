@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
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
    class="laras-field laras-field--textarea"
    data-laras-field
    data-filled="{{ filled(old($name, $value)) ? 'true' : 'false' }}"
    data-invalid="{{ $error ? 'true' : 'false' }}"
    data-tone="{{ $tone }}"
>
    <div class="laras-field__control">
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder=" "
            data-laras-field-control
            @if ($required)
                required
            @endif
            @if ($describedBy !== '')
                aria-describedby="{{ $describedBy }}"
            @endif
            aria-invalid="{{ $error ? 'true' : 'false' }}"
            {{ $attributes->class('laras-field__textarea') }}
        >{{ old($name, $value) }}</textarea>

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
