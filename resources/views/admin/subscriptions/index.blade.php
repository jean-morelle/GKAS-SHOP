@extends('admin._layout', ['title' => 'Abonnements'])

@section('content')
    <x-page-header title="Abonnements" />

    <div class="mb-4 flex gap-2">
        @foreach (['actifs' => 'Abonnements actifs', 'historique' => 'Historique complet'] as $key => $label)
            <a href="{{ route('admin.subscriptions.index', ['vue' => $key]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => $view === $key, 'bg-white text-slate-600 ring-1 ring-slate-200' => $view !== $key])>{{ $label }}</a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        @if ($stores)
            @if ($stores->isEmpty())
                <x-empty icon="card" title="Aucun abonnement actif" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Boutique</th><th>Vendeur</th><th>Activé le</th><th>Fin</th><th></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($stores as $store)
                                <tr>
                                    <td><a href="{{ route('admin.stores.show', $store) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $store->name }}</a></td>
                                    <td>{{ $store->owner?->name }}</td>
                                    <td>{{ $store->subscription_activated_at?->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap">{{ $store->subscription_ends_at->format('d/m/Y') }}</td>
                                    <td><x-badge :color="$store->daysRemaining() <= 7 ? 'amber' : 'green'">{{ $store->daysRemaining() }} j restants</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $stores->links() }}
            @endif
        @else
            @if ($subscriptions->isEmpty())
                <x-empty icon="card" title="Aucun abonnement enregistré" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Boutique</th><th>Formule</th><th>Prix</th><th>Période</th><th>Paiement</th><th>Validé par</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($subscriptions as $sub)
                                <tr>
                                    <td><a href="{{ route('admin.stores.show', $sub->store) }}" class="font-medium hover:text-brand-700">{{ $sub->store->name }}</a></td>
                                    <td>{{ $sub->plan_name }}</td>
                                    <td class="whitespace-nowrap">{{ fcfa($sub->price) }}</td>
                                    <td class="whitespace-nowrap">{{ $sub->starts_at->format('d/m/Y') }} → {{ $sub->ends_at->format('d/m/Y') }} <x-badge :color="$sub->isCurrent() ? 'green' : 'gray'">{{ $sub->statusLabel() }}</x-badge></td>
                                    <td>@if ($sub->payment)<a href="{{ route('admin.payments.show', $sub->payment) }}" class="font-mono text-xs text-brand-700">#{{ $sub->payment->id }}</a>@endif</td>
                                    <td>{{ $sub->creator?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $subscriptions->links() }}
            @endif
        @endif
    </div>
@endsection
