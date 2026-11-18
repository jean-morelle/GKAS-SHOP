@extends('seller._layout', ['title' => 'Commande '.$order->reference])

@section('content')
    <x-page-header :title="'Commande '.$order->reference" :subtitle="'Reçue le '.$order->created_at->format('d/m/Y à H:i').($order->channel === 'whatsapp' ? ' · envoyée aussi via WhatsApp' : '')" :back="route('vendor.orders.index', $store)">
        <x-badge :color="$order->status->color()" class="px-3 py-1 text-sm">{{ $order->status->label() }}</x-badge>
        @if ($order->paid_at)<x-badge color="green" class="px-3 py-1 text-sm">Payée</x-badge>@endif
    </x-page-header>

    @if ($order->is_demo)
        <p class="mb-4 rounded-lg bg-purple-50 px-4 py-2 text-sm text-purple-800">Commande de démonstration (données fictives créées par GKAS-GROUP).</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Articles commandés</h2>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Produit</th><th class="text-center">Qté</th><th class="text-right">Prix unitaire</th><th class="text-right">Total</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-right whitespace-nowrap">{{ fcfa($item->unit_price) }}</td>
                                    <td class="text-right font-semibold whitespace-nowrap">{{ fcfa($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if ($order->delivery_zone_name)
                                <tr class="border-t border-slate-200"><td colspan="3" class="px-4 pt-3">Sous-total</td><td class="px-4 pt-3 text-right whitespace-nowrap">{{ fcfa($order->subtotal) }}</td></tr>
                                <tr><td colspan="3" class="px-4 py-1">Livraison — {{ $order->delivery_zone_name }}</td><td class="px-4 py-1 text-right whitespace-nowrap">{{ bccomp((string) $order->delivery_fee, '0', 2) > 0 ? fcfa($order->delivery_fee) : 'Gratuit' }}</td></tr>
                            @endif
                            <tr class="border-t-2 border-slate-200"><td colspan="3" class="px-4 py-3 font-bold">Total</td><td class="px-4 py-3 text-right text-lg font-extrabold whitespace-nowrap">{{ fcfa($order->total) }}</td></tr></tfoot>
                    </table>
                </div>
                <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Prix et noms enregistrés au moment de la commande.</p>
            </div>

            <div class="card card-body">
                <h2 class="font-semibold text-slate-900">Historique</h2>
                <ol class="mt-4 space-y-4">
                    @foreach ($order->statusHistories as $h)
                        <li class="flex gap-3">
                            <span class="mt-1.5 size-2.5 shrink-0 rounded-full bg-brand-500"></span>
                            <div class="text-sm">
                                <p><span class="font-medium text-slate-900">{{ $h->to_status->label() }}</span> <span class="text-slate-500">· {{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->user?->name ?? 'Client' }}</span></p>
                                @if ($h->note)<p class="text-slate-600">{{ $h->note }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="font-semibold text-slate-900">Client</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-slate-500">Nom</dt><dd class="font-medium text-slate-900">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-slate-500">Téléphone</dt><dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $order->customer_phone) }}" class="font-medium text-brand-700">{{ $order->customer_phone }}</a></dd></div>
                    @if ($order->delivery_zone_name)<div><dt class="text-slate-500">Zone de livraison</dt><dd class="font-medium">{{ $order->delivery_zone_name }}</dd></div>@endif
                    <div><dt class="text-slate-500">Commune / quartier</dt><dd>{{ $order->customer_area }}</dd></div>
                    <div><dt class="text-slate-500">Adresse / instructions</dt><dd class="whitespace-pre-line">{{ $order->delivery_address }}</dd></div>
                    @if ($order->customer_notes)<div><dt class="text-slate-500">Notes du client</dt><dd class="rounded bg-amber-50 p-2 whitespace-pre-line">{{ $order->customer_notes }}</dd></div>@endif
                </dl>
                @if ($customerWhatsapp)
                    <a href="{{ $customerWhatsapp }}" target="_blank" rel="noopener" class="btn mt-4 w-full bg-emerald-500 text-white hover:bg-emerald-600"><x-icon name="whatsapp" class="size-4" /> Écrire au client</a>
                @endif
            </div>

            @if ($transitions && $store->isOperational())
                <form method="POST" action="{{ route('vendor.orders.status', [$store, $order]) }}" class="card card-body space-y-3">
                    @csrf
                    <h2 class="font-semibold text-slate-900">Changer le statut</h2>
                    <div class="space-y-2">
                        @foreach ($transitions as $t)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50 has-checked:border-brand-500 has-checked:bg-brand-50">
                                <input type="radio" name="status" value="{{ $t->value }}" required class="text-brand-600">
                                <x-badge :color="$t->color()">{{ $t->label() }}</x-badge>
                            </label>
                        @endforeach
                    </div>
                    <x-form.input name="note" label="Note (facultatif)" placeholder="ex. Livraison prévue demain matin" />
                    <button class="btn btn-primary w-full">Mettre à jour</button>
                </form>
            @endif

            @if ($store->isOperational())
                <div class="card card-body space-y-4">
                    <form method="POST" action="{{ route('vendor.orders.paid', [$store, $order]) }}">
                        @csrf
                        <p class="text-sm text-slate-600">Paiement : <strong>{{ $order->paid_at ? 'reçu le '.$order->paid_at->format('d/m/Y') : 'non reçu' }}</strong></p>
                        @if ($order->status !== \App\Enums\OrderStatus::Cancelled)
                            <button class="btn btn-secondary btn-sm mt-2">{{ $order->paid_at ? 'Annuler le paiement' : 'Marquer comme payée' }}</button>
                        @endif
                    </form>
                    <form method="POST" action="{{ route('vendor.orders.notes', [$store, $order]) }}" class="space-y-2 border-t border-slate-100 pt-4">
                        @csrf @method('PUT')
                        <x-form.textarea name="vendor_notes" label="Notes internes" :value="$order->vendor_notes" rows="3" help="Visibles uniquement par vous." />
                        <button class="btn btn-secondary btn-sm">Enregistrer la note</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
