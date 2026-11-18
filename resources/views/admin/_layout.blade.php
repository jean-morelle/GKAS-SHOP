@extends('layouts.app')

@section('nav')
    @php
        $items = [
            ['admin.dashboard', 'Tableau de bord', 'chart', 'admin.dashboard', 'access-admin'],
            ['admin.stores.index', 'Boutiques', 'store', 'admin.stores.*', 'access-admin'],
            ['admin.trials.index', 'Essais gratuits', 'clock', 'admin.trials.*', 'access-admin'],
            ['admin.payments.index', 'Paiements', 'banknotes', 'admin.payments.*', 'access-admin'],
            ['admin.subscriptions.index', 'Abonnements', 'card', 'admin.subscriptions.*', 'access-admin'],
            ['admin.prospects.index', 'Prospects', 'megaphone', 'admin.prospects.*', 'manage-prospects'],
            ['admin.users.index', 'Utilisateurs', 'users', 'admin.users.*', 'manage-platform'],
            ['admin.plans.index', 'Formules', 'tag', 'admin.plans.*', 'manage-platform'],
            ['admin.settings.edit', 'Paramètres', 'cog', 'admin.settings.*', 'manage-platform'],
            ['admin.activity.index', 'Journal d\'activité', 'list', 'admin.activity.*', 'manage-platform'],
        ];
    @endphp
    @foreach ($items as [$route, $label, $icon, $pattern, $ability])
        @can($ability)
            <a href="{{ route($route) }}" @class(['nav-link', 'active' => request()->routeIs($pattern)])>
                <x-icon :name="$icon" /> {{ $label }}
            </a>
        @endcan
    @endforeach
    <div class="my-4 border-t border-white/10"></div>
    <a href="{{ route('home') }}" class="nav-link" target="_blank"><x-icon name="external" /> Site public</a>
@endsection

@section('topbar')
    <p class="truncate text-sm font-medium text-slate-500">Espace GKAS-GROUP</p>
@endsection
