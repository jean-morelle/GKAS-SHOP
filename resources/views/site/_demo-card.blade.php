<a href="{{ $demo->publicUrl() }}" class="group card flex items-center gap-5 overflow-hidden p-5 transition hover:border-brand-300 hover:shadow-md" style="--shop-primary: {{ $demo->primary_color }}">
    @if ($demo->logoUrl())
        <img src="{{ $demo->logoUrl() }}" alt="" class="size-16 shrink-0 rounded-xl object-cover">
    @else
        <span class="flex size-16 shrink-0 items-center justify-center rounded-xl text-xl font-bold text-white" style="background: {{ $demo->primary_color }}">{{ $demo->initials() }}</span>
    @endif
    <span class="min-w-0 flex-1">
        <span class="flex items-center gap-2"><span class="font-semibold text-slate-900">{{ $demo->name }}</span><x-badge color="purple">Démo</x-badge></span>
        <span class="mt-1 line-clamp-2 block text-sm text-slate-600">{{ $demo->description }}</span>
        <span class="mt-1 block text-xs text-slate-400">{{ $demo->products_count }} produits · {{ str_replace(['http://', 'https://'], '', $demo->publicUrl()) }}</span>
    </span>
    <x-icon name="arrow-right" class="size-5 text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-600" />
</a>
