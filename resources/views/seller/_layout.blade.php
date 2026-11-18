@extends('layouts.app')

@php
    $me = auth()->user();
    $myRole = $me->storeRole($store);
@endphp

@section('nav')
    @php
        // [route, libellé, icône, motif actif, droit requis]
        $items = [
            ['vendor.dashboard', 'Tableau de bord', 'home', 'vendor.dashboard', null],
            ['vendor.orders.index', 'Commandes', 'bag', 'vendor.orders.*', 'orders'],
            ['vendor.products.index', 'Produits', 'cube', 'vendor.products.*', 'catalog'],
            ['vendor.categories.index', 'Catégories', 'tag', 'vendor.categories.*', 'catalog'],
            ['vendor.delivery.index', 'Livraison', 'truck', 'vendor.delivery.*', 'settings'],
            ['vendor.settings.edit', 'Ma boutique', 'store', 'vendor.settings.*', 'settings'],
            ['vendor.appearance.edit', 'Apparence', 'brush', 'vendor.appearance.*', 'settings'],
            ['vendor.team.index', 'Équipe', 'users', 'vendor.team.*', 'team'],
            ['vendor.subscription.show', 'Abonnement', 'card', 'vendor.subscription.*', 'billing'],
        ];
    @endphp
    <div class="mb-4 rounded-lg bg-white/10 px-3 py-3">
        <p class="text-[11px] font-semibold tracking-wider text-brand-200 uppercase">{{ $myRole?->label() ?? 'Ma boutique' }}</p>
        <p class="truncate font-semibold text-white">{{ $store->name }}</p>
        <a href="{{ $store->platformUrl() }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs text-brand-200 hover:text-white">
            Voir la boutique <x-icon name="external" class="size-3.5" />
        </a>
    </div>
    @foreach ($items as [$route, $label, $icon, $pattern, $ability])
        @continue($ability && ! $me->canInStore($store, $ability))
        <a href="{{ route($route, $store) }}" @class(['nav-link', 'active' => request()->routeIs($pattern)])>
            <x-icon :name="$icon" /> {{ $label }}
            @if ($route === 'vendor.orders.index' && ($pending = $store->orders()->whereIn('status', \App\Enums\OrderStatus::toProcess())->count()))
                <span class="ml-auto rounded-full bg-accent-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $pending }}</span>
            @endif
        </a>
    @endforeach
@endsection

@section('topbar')
    @php $state = $store->accessState(); @endphp
    <div class="flex items-center gap-2 text-sm">
        <x-badge :color="$state->color()">{{ $state->label() }}</x-badge>
        @if ($state->isOperational() && $me->canInStore($store, 'billing'))
            <span class="hidden text-slate-500 sm:inline">{{ $store->daysRemaining() }} jour(s) restant(s)</span>
        @endif
    </div>
@endsection

@section('alerts')
    @php
        $state = $store->accessState();
        $canBill = $me->canInStore($store, 'billing');
    @endphp
    @if (! $state->isOperational())
        <div class="mb-6 flex flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3 text-amber-900">
                <x-icon name="lock" class="mt-0.5 size-5 shrink-0" />
                <div class="text-sm">
                    <p class="font-semibold">Accès limité — boutique {{ mb_strtolower($state->label()) }}</p>
                    <p>
                        @if ($state === \App\Enums\AccessState::Suspended)
                            Votre boutique a été suspendue par GKAS-GROUP{{ $store->suspension_reason ? ' : '.$store->suspension_reason : '' }}.
                        @elseif ($canBill)
                            Votre boutique n'accepte plus de commandes. Vos données sont conservées ; vous pouvez les consulter et les exporter.
                        @else
                            La boutique n'accepte plus de commandes. Le propriétaire doit renouveler l'abonnement.
                        @endif
                    </p>
                </div>
            </div>
            @if ($state === \App\Enums\AccessState::Expired && $canBill)
                <a href="{{ route('vendor.subscription.show', $store) }}" class="btn btn-accent shrink-0">Renouveler mon abonnement</a>
            @endif
        </div>
    @elseif ($canBill && $store->daysRemaining() <= 7)
        <div class="mb-6 flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-center gap-2"><x-icon name="clock" class="size-5" />
                {{ $state === \App\Enums\AccessState::Trial ? 'Votre essai gratuit' : 'Votre abonnement' }} se termine dans <strong>{{ $store->daysRemaining() }} jour(s)</strong>.
            </p>
            <a href="{{ route('vendor.subscription.show', $store) }}" class="btn btn-primary btn-sm shrink-0">Choisir une formule</a>
        </div>
    @endif
@endsection
