@extends('admin._layout', ['title' => 'Paiements'])

@section('content')
    <x-page-header title="Paiements" subtitle="Paiements d'abonnement déclarés par les vendeurs ou enregistrés par GKAS-GROUP." />

    <div class="mb-4 flex gap-2 overflow-x-auto">
        @foreach (\App\Enums\PaymentStatus::cases() as $s)
            <a href="{{ route('admin.payments.index', ['statut' => $s->value]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => $status === $s, 'bg-white text-slate-600 ring-1 ring-slate-200' => $status !== $s])>
                {{ $s->label() }} <span class="opacity-75">({{ $counts[$s->value] ?? 0 }})</span>
            </a>
        @endforeach
        <a href="{{ route('admin.payments.index', ['statut' => 'tous']) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => ! $status, 'bg-white text-slate-600 ring-1 ring-slate-200' => $status])>Tous</a>
    </div>

    <div class="card overflow-hidden">
        @if ($payments->isEmpty())
            <x-empty icon="banknotes" title="Aucun paiement" text="Les paiements déclarés par les vendeurs apparaîtront ici pour vérification." />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Boutique</th><th>Formule</th><th>Montant</th><th>Moyen / référence</th><th>Statut</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payments as $p)
                            <tr>
                                <td class="whitespace-nowrap">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                                <td><a href="{{ route('admin.payments.show', $p) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $p->store->name }}</a><span class="block text-xs text-slate-500">par {{ $p->submitter?->name ?? '—' }}</span></td>
                                <td>{{ $p->plan->name }}</td>
                                <td class="font-semibold whitespace-nowrap">{{ fcfa($p->amount) }}</td>
                                <td>{{ $p->method->label() }}<span class="block font-mono text-xs text-slate-500">{{ $p->transaction_reference }}</span></td>
                                <td><a href="{{ route('admin.payments.show', $p) }}"><x-badge :color="$p->status->color()">{{ $p->status->label() }}</x-badge></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        @endif
    </div>
@endsection
