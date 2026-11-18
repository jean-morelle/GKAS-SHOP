@props(['name', 'label', 'checked' => false, 'help' => null])
<div {{ $attributes->only('class') }}>
    <label class="flex cursor-pointer items-start gap-3">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
            class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
        <span>
            <span class="text-sm font-medium text-slate-800">{{ $label }}</span>
            @if ($help)<span class="block text-xs text-slate-500">{{ $help }}</span>@endif
        </span>
    </label>
</div>
