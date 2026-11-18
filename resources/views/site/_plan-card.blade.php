<div @class(['relative flex flex-col rounded-2xl border bg-white p-6 shadow-sm', 'border-brand-500 ring-2 ring-brand-500' => $highlight ?? false, 'border-slate-200' => ! ($highlight ?? false)])>
    @if ($highlight ?? false)
        <span class="absolute -top-3 left-6 rounded-full bg-brand-600 px-3 py-0.5 text-xs font-semibold text-white">Le plus choisi</span>
    @endif
    <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
    @if ($plan->description)<p class="mt-1 text-sm text-slate-500">{{ $plan->description }}</p>@endif
    <p class="mt-5"><span class="text-3xl font-extrabold text-slate-900">{{ fcfa($plan->price) }}</span> <span class="text-sm text-slate-500">/ {{ $plan->durationLabel() }}</span></p>
    @if ($plan->features)
        <ul class="mt-6 flex-1 space-y-2.5 text-sm text-slate-700">
            @foreach ($plan->features as $feature)
                <li class="flex gap-2"><x-icon name="check" class="size-5 shrink-0 text-emerald-500" /> {{ $feature }}</li>
            @endforeach
        </ul>
    @endif
    <a href="{{ route('site.trial') }}" @class(['btn mt-6 w-full', 'btn-primary' => $highlight ?? false, 'btn-secondary' => ! ($highlight ?? false)])>Commencer l'essai gratuit</a>
</div>
