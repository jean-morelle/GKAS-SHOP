@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name));
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $current = $type === 'password' || $type === 'file' ? null : old($errorKey, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-500" aria-hidden="true">*</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if ($current !== null) value="{{ $current }}" @endif
        @required($required)
        @error($errorKey) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-input'.($errors->has($errorKey) ? ' is-invalid' : '')]) }}>
    @if ($help)<p class="form-help">{{ $help }}</p>@endif
    @error($errorKey)<p id="{{ $id }}-error" class="form-error">{{ $message }}</p>@enderror
</div>
