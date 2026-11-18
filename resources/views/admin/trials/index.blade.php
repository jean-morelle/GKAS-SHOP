@extends('admin._layout', ['title' => 'Essais gratuits'])

@section('content')
    <x-page-header title="Essais gratuits" :subtitle="'Durée par défaut : '.\App\Models\Setting::trialDays().' jours calendaires.'" />

    <div class="mb-4 flex gap-2 overflow-x-auto">
        @foreach (['en-cours' => 'En cours', 'bientot' => 'Expirent sous 7 jours', 'expires' => 'Expirés', 'historique' => 'Historique'] as $key => $label)
            <a href="{{ route('admin.trials.index', ['vue' => $key]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => $tab === $key, 'bg-white text-slate-600 ring-1 ring-slate-200' => $tab !== $key])>
                {{ $label }} @isset($counts[$key])<span class="ml-1 opacity-75">({{ $counts[$key] }})</span>@endisset
            </a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        @if ($histories)
            @forelse ($histories as $h)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3 text-sm last:border-0">
                    <div>
                        <a href="{{ route('admin.stores.show', $h->store) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $h->store->name }}</a>
                        · {{ $h->actionLabel() }} @if ($h->days)({{ $h->days }} j)@endif
                        <p class="text-xs text-slate-500">{{ $h->performer?->name ?? 'Système' }} @if ($h->reason)· « {{ $h->reason }} »@endif</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ $h->created_at->format('d/m/Y H:i') }} @if ($h->new_ends_at)→ fin {{ $h->new_ends_at->format('d/m/Y') }}@endif</span>
                </div>
            @empty
                <x-empty icon="clock" title="Aucun historique" />
            @endforelse
            {{ $histories->links() }}
        @elseif ($stores->isEmpty())
            <x-empty icon="clock" title="Aucune boutique dans cette catégorie" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Boutique</th><th>Vendeur</th><th>Début</th><th>Fin d'essai</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stores as $store)
                            <tr>
                                <td><a href="{{ route('admin.stores.show', $store) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $store->name }}</a></td>
                                <td>{{ $store->owner?->name }}<span class="block text-xs text-slate-500">{{ $store->owner?->phone }}</span></td>
                                <td class="whitespace-nowrap">{{ $store->trial_starts_at?->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap">{{ $store->trial_ends_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if ($store->isOnTrial())
                                        <x-badge :color="$store->daysRemaining() <= 3 ? 'red' : ($store->daysRemaining() <= 7 ? 'amber' : 'blue')">{{ $store->daysRemaining() }} j restants</x-badge>
                                    @else
                                        <x-badge color="amber">Expiré {{ $store->trial_ends_at?->diffForHumans() }}</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $stores->links() }}
        @endif
    </div>
@endsection
