@extends('seller._layout', ['title' => 'Tableau de bord'])

@section('content')
    @php $state = $store->accessState(); @endphp
    <x-page-header :title="'Bonjour '.explode(' ', auth()->user()->name)[0].' 👋'" subtitle="Voici l'essentiel de votre boutique aujourd'hui.">
        @can('manageCatalog', $store)
            <a href="{{ route('vendor.products.create', $store) }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Ajouter un produit</a>
        @endcan
    </x-page-header>

    {{-- Partage de la boutique --}}
    <div class="card mb-6 overflow-hidden">
        <div class="flex flex-col gap-4 bg-gradient-to-r from-brand-600 to-brand-800 p-5 text-white sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm text-brand-100">Le lien de votre boutique à partager sur vos réseaux</p>
                <p class="mt-1 truncate font-mono text-lg font-semibold">{{ $store->publicUrl() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn bg-white text-brand-700 hover:bg-brand-50" data-copy="{{ $store->publicUrl() }}"><x-icon name="link" class="size-4" /> <span data-copy-label>Copier le lien</span></button>
                <a href="https://wa.me/?text={{ rawurlencode('Découvrez ma boutique '.$store->name.' : '.$store->publicUrl()) }}" target="_blank" rel="noopener" class="btn bg-emerald-500 text-white hover:bg-emerald-600"><x-icon name="whatsapp" class="size-4" /> WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($store->publicUrl()) }}" target="_blank" rel="noopener" class="btn bg-white/15 text-white hover:bg-white/25"><x-icon name="facebook" class="size-4" /> Facebook</a>
                <button type="button" class="btn bg-white/15 text-white hover:bg-white/25 sm:hidden" data-share="{{ $store->publicUrl() }}" data-share-title="{{ $store->name }}"><x-icon name="share" class="size-4" /> Partager</button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Commandes à traiter" :value="$stats['to_process']" icon="bell" tone="amber" :href="route('vendor.orders.index', [$store, 'groupe' => 'a-traiter'])" />
        <x-stat label="Commandes en cours" :value="$stats['in_progress']" icon="truck" tone="blue" :href="route('vendor.orders.index', [$store, 'groupe' => 'en-cours'])" hint="Confirmées, non livrées" />
        <x-stat label="Commandes livrées" :value="$stats['delivered']" icon="check-circle" tone="green" :href="route('vendor.orders.index', [$store, 'statut' => 'delivered'])" />
        <x-stat label="Commandes reçues" :value="$stats['orders']" icon="bag" :href="route('vendor.orders.index', $store)" hint="Depuis l'ouverture" />
        @can('viewStats', $store)
        <x-stat label="Ventes du mois" :value="fcfa($stats['sales_month'])" icon="banknotes" tone="green" hint="Commandes livrées ou payées" />
        <x-stat label="Ventes totales" :value="fcfa($stats['sales_total'])" icon="chart" tone="green" hint="Commandes livrées ou payées" />
        <x-stat label="Produits" :value="$stats['products']" icon="cube" :href="route('vendor.products.index', $store)" />
        <x-stat label="Produits disponibles" :value="$stats['available']" icon="tag" tone="blue" hint="Actifs et en stock" />
        @endcan
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div @class(['card', 'lg:col-span-2' => auth()->user()->can('manageBilling', $store), 'lg:col-span-3' => ! auth()->user()->can('manageBilling', $store)])>
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Dernières commandes</h2>
                <a href="{{ route('vendor.orders.index', $store) }}" class="text-sm font-medium text-brand-700 hover:underline">Tout voir</a>
            </div>
            @forelse ($latestOrders as $order)
                <a href="{{ route('vendor.orders.show', [$store, $order]) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50">
                    <span class="min-w-0">
                        <span class="block font-medium text-slate-900">{{ $order->customer_name }} @if ($order->is_demo)<x-badge color="purple">Démo</x-badge>@endif</span>
                        <span class="block text-xs text-slate-500">{{ $order->reference }} · {{ $order->created_at->diffForHumans() }}</span>
                    </span>
                    <span class="flex shrink-0 flex-col items-end gap-1">
                        <span class="text-sm font-semibold whitespace-nowrap">{{ fcfa($order->total) }}</span>
                        <x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge>
                    </span>
                </a>
            @empty
                <x-empty icon="bag" title="Pas encore de commande" text="Partagez le lien de votre boutique sur vos réseaux pour recevoir vos premières commandes." />
            @endforelse
        </div>

        @can('manageBilling', $store)
        <div class="card card-body">
            <h2 class="font-semibold text-slate-900">Mon abonnement</h2>
            <div class="mt-4 flex items-center gap-2"><x-badge :color="$state->color()">{{ $state->label() }}</x-badge></div>
            @if ($state->isOperational())
                <p class="mt-4 text-4xl font-extrabold text-slate-900">{{ $store->daysRemaining() }}</p>
                <p class="text-sm text-slate-500">jour(s) restant(s) — jusqu'au {{ $store->accessEndsAt()->format('d/m/Y') }}</p>
                @php
                    $start = $state === \App\Enums\AccessState::Trial ? $store->trial_starts_at : ($store->subscriptions()->latest('ends_at')->first()?->starts_at ?? $store->trial_starts_at);
                    $total = max(1, $start ? $start->diffInDays($store->accessEndsAt()) : 30);
                    $pct = min(100, max(3, (int) round(100 * $store->daysRemaining() / $total)));
                @endphp
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $pct < 25 ? 'bg-amber-500' : 'bg-brand-600' }}" style="width: {{ $pct }}%"></div></div>
            @else
                <p class="mt-3 text-sm text-slate-600">Votre boutique n'accepte plus de commandes.</p>
            @endif
            @if ($pendingPayment)
                <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Paiement de {{ fcfa($pendingPayment->amount) }} en cours de vérification.</p>
            @endif
            <a href="{{ route('vendor.subscription.show', $store) }}" class="btn btn-secondary mt-5 w-full">Gérer mon abonnement</a>
        </div>
        @endcan
    </div>
@endsection
