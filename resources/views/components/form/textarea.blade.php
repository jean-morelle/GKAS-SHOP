@props(['name', 'label' => null, 'value' => null, 'help' => null, 'required' => false, 'rows' => 4])
@php $id = 'f-'.$name; @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-500" aria-hidden="true">*</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->except('class')->merge(['class' => 'form-input'.($errors->has($name) ? ' is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>
    @if ($help)<p class="form-help">{{ $help }}</p>@endif
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
