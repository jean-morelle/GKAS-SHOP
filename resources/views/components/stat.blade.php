@props(['label', 'value', 'icon' => 'chart', 'tone' => 'brand', 'href' => null, 'hint' => null])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'blue' => 'bg-sky-50 text-sky-600',
        'gray' => 'bg-slate-100 text-slate-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif class="card card-body flex items-start gap-4 {{ $href ? 'transition hover:border-brand-300 hover:shadow' : '' }}">
    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <span class="min-w-0">
        <span class="block text-sm text-slate-500">{{ $label }}</span>
        <span class="mt-0.5 block text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</span>
        @if ($hint)<span class="block text-xs text-slate-500">{{ $hint }}</span>@endif
    </span>
</{{ $tag }}>
