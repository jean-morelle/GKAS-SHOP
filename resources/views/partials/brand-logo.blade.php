@if (! empty($platform['platform_logo']))
    <img src="{{ Storage::disk('public')->url($platform['platform_logo']) }}" alt="{{ $platform['platform_name'] }}" class="h-9 w-auto">
@else
    <span class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-brand-800 text-white shadow-sm">
        <x-icon name="bag" class="size-5" />
    </span>
    <span class="text-lg font-extrabold tracking-tight {{ ($dark ?? false) ? 'text-white' : 'text-slate-900' }}">
        GKAS<span class="text-accent-500"> Shop</span>
    </span>
@endif
