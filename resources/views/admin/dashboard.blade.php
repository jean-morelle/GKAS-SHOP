@extends('admin._layout', ['title' => 'Tableau de bord'])

@section('content')
    <x-page-header title="Tableau de bord" subtitle="Vue d'ensemble de la plateforme GKAS Shop.">
        @can('create', \App\Models\Store::class)
            <a href="{{ route('admin.stores.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Créer une boutique</a>
        @endcan
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Boutiques" :value="$stats['stores']" icon="store" :href="route('admin.stores.index')" />
        <x-stat label="En période d'essai" :value="$stats['trial']" icon="clock" tone="blue" :href="route('admin.stores.index', ['acces' => 'trial'])" />
        <x-stat label="Abonnements actifs" :value="$stats['subscribed']" icon="check-circle" tone="green" :href="route('admin.stores.index', ['acces' => 'subscribed'])" />
        <x-stat label="Expirées / suspendues" :value="$stats['expired'].' / '.$stats['suspended']" icon="lock" tone="red" :href="route('admin.stores.index', ['acces' => 'expired'])" />
        <x-stat label="Commandes" :value="$stats['orders']" icon="bag" :hint="$stats['demo_orders'] ? '+ '.$stats['demo_orders'].' de démonstration' : null" />
        <x-stat label="Demandes commerciales" :value="$stats['leads']" icon="megaphone" tone="blue" :hint="$stats['new_leads'].' nouvelle(s)'" :href="route('admin.prospects.index')" />
        <x-stat label="Paiements à vérifier" :value="$stats['pending_payments']" icon="banknotes" :tone="$stats['pending_payments'] ? 'amber' : 'gray'" :href="route('admin.payments.index')" />
        <x-stat label="Essais expirant sous 7 j" :value="$expiringSoon->count()" icon="calendar" tone="amber" :href="route('admin.trials.index', ['vue' => 'bientot'])" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Paiements en attente</h2>
                <a href="{{ route('admin.payments.index') }}" class="text-sm text-brand-700 hover:underline">Tout voir</a>
            </div>
            @forelse ($pendingPayments as $p)
                <a href="{{ route('admin.payments.show', $p) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0 hover:bg-slate-50">
                    <span><span class="font-medium text-slate-900">{{ $p->store->name }}</span><span class="block text-xs text-slate-500">{{ $p->plan->name }} · réf. {{ $p->transaction_reference }} · {{ $p->created_at->diffForHumans() }}</span></span>
                    <span class="font-semibold whitespace-nowrap">{{ fcfa($p->amount) }}</span>
                </a>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">Aucun paiement en attente.</p>
            @endforelse
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Essais qui expirent bientôt</h2>
                <a href="{{ route('admin.trials.index', ['vue' => 'bientot']) }}" class="text-sm text-brand-700 hover:underline">Tout voir</a>
            </div>
            @forelse ($expiringSoon as $s)
                <a href="{{ route('admin.stores.show', $s) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0 hover:bg-slate-50">
                    <span class="font-medium text-slate-900">{{ $s->name }}</span>
                    <x-badge :color="$s->daysRemaining() <= 3 ? 'red' : 'amber'">J-{{ $s->daysRemaining() }} · {{ $s->trial_ends_at->format('d/m') }}</x-badge>
                </a>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">Aucun essai n'expire dans les 7 prochains jours.</p>
            @endforelse
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Relances commerciales</h2>
                <a href="{{ route('admin.prospects.index') }}" class="text-sm text-brand-700 hover:underline">Prospects</a>
            </div>
            @forelse ($followUps as $lead)
                <a href="{{ route('admin.prospects.show', $lead) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0 hover:bg-slate-50">
                    <span><span class="font-medium text-slate-900">{{ $lead->business_name ?: $lead->name }}</span><span class="block text-xs text-slate-500">{{ $lead->assignee?->name ?? 'Non affecté' }} · {{ $lead->status->label() }}</span></span>
                    <x-badge :color="$lead->next_follow_up_at->isPast() ? 'red' : 'blue'">{{ $lead->next_follow_up_at->format('d/m') }}</x-badge>
                </a>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">Aucune relance prévue dans les 2 prochains jours.</p>
            @endforelse
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Dernières activités</h2>
                @can('manage-platform')<a href="{{ route('admin.activity.index') }}" class="text-sm text-brand-700 hover:underline">Journal</a>@endcan
            </div>
            @forelse ($activities as $log)
                <div class="border-b border-slate-100 px-5 py-3 text-sm last:border-0">
                    <p class="text-slate-800">{{ $log->description }}</p>
                    <p class="text-xs text-slate-500">{{ $log->user?->name ?? 'Système' }} · {{ $log->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">Aucune activité enregistrée.</p>
            @endforelse
        </div>
    </div>
@endsection
