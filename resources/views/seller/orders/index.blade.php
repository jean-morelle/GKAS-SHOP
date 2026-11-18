@extends('seller._layout', ['title' => 'Commandes'])

@section('content')
    <x-page-header title="Commandes" subtitle="Toutes les commandes reçues par votre boutique.">
        <a href="{{ route('vendor.export', [$store, 'commandes']) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exporter</a>
    </x-page-header>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @php
            $tabs = ['' => 'Toutes', 'a-traiter' => 'À traiter', 'en-cours' => 'En cours'];
        @endphp
        @foreach ($tabs as $key => $label)
            <a href="{{ route('vendor.orders.index', array_filter([$store->slug, 'groupe' => $key ?: null])) }}"
               @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => $group === $key && ! $status, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => ! ($group === $key && ! $status)])>{{ $label }}</a>
        @endforeach
        @foreach ([\App\Enums\OrderStatus::Delivered, \App\Enums\OrderStatus::Cancelled] as $s)
            <a href="{{ route('vendor.orders.index', [$store, 'statut' => $s->value]) }}"
               @class(['rounded-full px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'bg-brand-600 text-white' => $status === $s, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $status !== $s])>{{ $s->label() }}</a>
        @endforeach
    </div>

    <form class="card mb-4 flex gap-2 p-4" role="search">
        @if ($group)<input type="hidden" name="groupe" value="{{ $group }}">@endif
        <input type="search" name="q" value="{{ $search }}" placeholder="Référence, nom ou téléphone du client…" class="form-input" aria-label="Rechercher">
        <select name="statut" class="form-input max-w-56" aria-label="Statut">
            <option value="">Tous les statuts</option>
            @foreach (\App\Enums\OrderStatus::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach
        </select>
        <button class="btn btn-secondary px-3" aria-label="Rechercher"><x-icon name="search" class="size-4" /></button>
    </form>

    <div class="card overflow-hidden">
        @if ($orders->isEmpty())
            <x-empty icon="bag" title="Aucune commande" text="Les commandes de vos clients apparaîtront ici. Partagez le lien de votre boutique !" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Commande</th><th>Client</th><th class="max-md:hidden">Quartier</th><th>Total</th><th>Statut</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('vendor.orders.show', [$store, $order]) }}'">
                                <td>
                                    <a href="{{ route('vendor.orders.show', [$store, $order]) }}" class="font-mono text-sm font-semibold text-brand-700 hover:underline">{{ $order->reference }}</a>
                                    <span class="block text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items_count }} article(s)</span>
                                </td>
                                <td>
                                    <span class="block font-medium text-slate-900">{{ $order->customer_name }}</span>
                                    <span class="text-xs text-slate-500">{{ $order->customer_phone }}</span>
                                    @if ($order->is_demo)<x-badge color="purple">Démo</x-badge>@endif
                                </td>
                                <td class="max-md:hidden">{{ $order->customer_area }}</td>
                                <td class="font-semibold whitespace-nowrap">{{ fcfa($order->total) }} @if ($order->paid_at)<x-badge color="green">Payée</x-badge>@endif</td>
                                <td><x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        @endif
    </div>
@endsection
