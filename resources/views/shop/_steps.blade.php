<ol class="mb-6 flex items-center gap-2 text-sm" aria-label="Étapes de la commande">
    @foreach (['Coordonnées', 'Vérification', 'Confirmation'] as $i => $label)
        <li class="flex items-center gap-2 {{ $i + 1 <= $step ? 'font-semibold' : 'text-slate-400' }}" @if ($i + 1 === $step) aria-current="step" @endif>
            <span @class(['flex size-7 items-center justify-center rounded-full text-xs', 'bg-shop-primary text-white' => $i + 1 <= $step, 'bg-slate-200' => $i + 1 > $step])>{{ $i + 1 }}</span>
            <span class="hidden sm:inline">{{ $label }}</span>
        </li>
        @if (! $loop->last)<li class="h-px flex-1 bg-slate-200" aria-hidden="true"></li>@endif
    @endforeach
</ol>
