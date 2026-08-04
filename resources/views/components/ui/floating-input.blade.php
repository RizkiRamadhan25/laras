@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'autocomplete' => null,
    'tone' => 'default',
    'density' => 'default',
    'suffix' => null,
])

@php
    $id ??= str_replace(['[', ']', '.'], '_', $name);
    $error ??= $errors->first($name);
    $hintId = $hint ? $id.'-hint' : null;
    $errorId = $error ? $id.'-error' : null;
    $describedBy = collect([$hintId, $errorId])
        ->filter()
        ->implode(' ');

    $alwaysFloating = in_array(
        $type,
        [
            'date',
            'datetime-local',
            'time',
            'month',
            'week',
        ],
        true
    );
@endphp

<div
    @class([
        'laras-field',
        'laras-field--compact' => $density === 'compact',
        'laras-field--suffix' => filled($suffix),
        'laras-field--always-floating' => $alwaysFloating,
    ])
    data-laras-field
    data-filled="{{ filled(old($name, $value)) ? 'true' : 'false' }}"
    data-invalid="{{ $error ? 'true' : 'false' }}"
    data-tone="{{ $tone }}"
    data-density="{{ $density }}"
>
    <div class="laras-field__control">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder=" "
            data-laras-field-control
            @if ($autocomplete)
                autocomplete="{{ $autocomplete }}"
            @endif
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

        @if (filled($suffix))
            <span
                class="laras-field__suffix"
                aria-hidden="true"
            >
                {{ $suffix }}
            </span>
        @endif

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
