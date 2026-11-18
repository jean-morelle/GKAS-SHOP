@extends('admin._layout', ['title' => $store->name])

@section('content')
    @php $state = $store->accessState(); @endphp
    <x-page-header :title="$store->name" :back="route('admin.stores.index')">
        <a href="{{ $store->publicUrl() }}" target="_blank" class="btn btn-secondary"><x-icon name="eye" class="size-4" /> Voir la boutique</a>
        @can('update', $store)<a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Modifier</a>@endcan
        @can('recordPayment', $store)<a href="{{ route('admin.stores.payments.create', $store) }}" class="btn btn-primary"><x-icon name="banknotes" class="size-4" /> Enregistrer un paiement</a>@endcan
    </x-page-header>

    <div class="mb-6 flex flex-wrap items-center gap-2 text-sm">
        <x-badge :color="$state->color()" class="px-3 py-1 text-sm">{{ $state->label() }}</x-badge>
        <x-badge :color="$store->status->color()">Statut administratif : {{ $store->status->label() }}</x-badge>
        @if ($store->is_demo)<x-badge color="purple">Démonstration</x-badge>@endif
        @unless ($store->is_published)<x-badge color="gray">Non publiée</x-badge>@endunless
        <span class="font-mono text-slate-500">{{ $store->publicUrl() }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Colonne principale --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat label="Produits" :value="$productCount" icon="cube" />
                <x-stat label="Commandes" :value="$orderCount" icon="bag" />
                <x-stat :label="$state === \App\Enums\AccessState::Subscribed ? 'Fin d\'abonnement' : 'Fin d\'essai'" :value="$store->accessEndsAt()?->format('d/m/Y') ?? '—'" icon="calendar" :hint="$state->isOperational() ? $store->daysRemaining().' jour(s) restant(s)' : null" />
            </div>

            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Abonnements et paiements</h2>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Date</th><th>Formule</th><th>Montant</th><th>Référence</th><th>Statut</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($payments as $p)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $p->created_at->format('d/m/Y') }}</td>
                                    <td>{{ $p->plan->name }}</td>
                                    <td class="whitespace-nowrap">{{ fcfa($p->amount) }}</td>
                                    <td class="font-mono text-xs">{{ $p->transaction_reference }}</td>
                                    <td><a href="{{ route('admin.payments.show', $p) }}"><x-badge :color="$p->status->color()">{{ $p->status->label() }}</x-badge></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-slate-500">Aucun paiement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($subscriptions->isNotEmpty())
                    <div class="border-t border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-700">Périodes d'abonnement</h3>
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($subscriptions as $sub)
                                <li class="flex justify-between gap-2"><span>{{ $sub->plan_name }} · {{ $sub->starts_at->format('d/m/Y') }} → {{ $sub->ends_at->format('d/m/Y') }}</span><span class="text-slate-500">{{ $sub->statusLabel() }} · validé par {{ $sub->creator?->name ?? '—' }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Dernières commandes</h2>
                @forelse ($orders as $order)
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0">
                        <span><span class="font-mono font-medium">{{ $order->reference }}</span> · {{ $order->customer_name }} @if ($order->is_demo)<x-badge color="purple">Démo</x-badge>@endif <span class="block text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</span></span>
                        <span class="flex items-center gap-2"><span class="font-semibold">{{ fcfa($order->total) }}</span><x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge></span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Aucune commande.</p>
                @endforelse
            </div>

            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Produits récents</h2>
                @forelse ($products as $product)
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0">
                        <span>{{ $product->name }} <span class="text-xs text-slate-500">· {{ $product->category?->name ?? 'Sans catégorie' }}</span></span>
                        <span class="flex items-center gap-2"><span>{{ fcfa($product->effectivePrice()) }}</span><x-badge :color="$product->is_active ? 'green' : 'gray'">{{ $product->is_active ? 'Visible' : 'Masqué' }}</x-badge></span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Aucun produit.</p>
                @endforelse
            </div>

            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Historique de l'essai</h2>
                @foreach ($trialHistories as $h)
                    <div class="border-b border-slate-100 px-5 py-3 text-sm last:border-0">
                        <p><span class="font-medium">{{ $h->actionLabel() }}</span> @if ($h->days)· {{ $h->days }} jour(s)@endif
                            @if ($h->new_ends_at)→ fin le {{ $h->new_ends_at->format('d/m/Y H:i') }}@endif</p>
                        <p class="text-xs text-slate-500">{{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->performer?->name ?? 'Système' }} @if ($h->reason)· « {{ $h->reason }} »@endif</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="space-y-6">
            <div class="card card-body text-sm">
                <h2 class="font-semibold text-slate-900">Propriétaire</h2>
                @if ($store->owner)
                    <p class="mt-2 font-medium">{{ $store->owner->name }}</p>
                    <p class="text-slate-500">{{ $store->owner->email }} @if ($store->owner->phone)· {{ $store->owner->phone }}@endif</p>
                    @can('manage-platform')<a href="{{ route('admin.users.show', $store->owner) }}" class="mt-2 inline-block text-brand-700 hover:underline">Voir le compte</a>@endcan
                @else
                    <p class="mt-2 text-slate-500">Aucun propriétaire.</p>
                @endif
                @can('changeStatus', $store)
                    <form method="POST" action="{{ route('admin.stores.owner', $store) }}" class="mt-4 flex gap-2 border-t border-slate-100 pt-4" data-confirm="Changer le propriétaire de la boutique ?">
                        @csrf
                        <select name="owner_id" class="form-input" aria-label="Nouveau propriétaire" required>
                            <option value="">Changer de propriétaire…</option>
                            @foreach ($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }} — {{ $v->email }}</option>@endforeach
                        </select>
                        <button class="btn btn-secondary btn-sm">OK</button>
                    </form>
                @endcan
                <dl class="mt-4 space-y-1 border-t border-slate-100 pt-4 text-slate-600">
                    <div>WhatsApp : {{ $store->whatsapp ?: '—' }}</div>
                    <div>Téléphone : {{ $store->phone ?: '—' }}</div>
                    <div>Créée le {{ $store->created_at->format('d/m/Y') }}</div>
                </dl>
            </div>

            <div class="card card-body text-sm">
                <h2 class="font-semibold text-slate-900">Équipe ({{ $store->users->count() }})</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($store->users as $member)
                        <li class="flex items-center justify-between gap-2">
                            <span class="min-w-0 truncate">{{ $member->name }} <span class="text-xs text-slate-500">{{ $member->email }}</span></span>
                            <x-badge :color="$member->pivot->role === 'owner' ? 'indigo' : 'gray'">{{ \App\Enums\StoreRole::tryFrom($member->pivot->role)?->label() }}</x-badge>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-slate-500">Les gérants et employés sont ajoutés par le propriétaire depuis son espace (menu Équipe).</p>
            </div>

            <div class="card card-body text-sm">
                <h2 class="font-semibold text-slate-900">Livraison</h2>
                @forelse ($store->deliveryZones as $zone)
                    <p class="mt-2 flex justify-between gap-2 {{ $zone->is_active ? '' : 'text-slate-400 line-through' }}"><span>{{ $zone->name }}</span><span>{{ fcfa($zone->fee) }}</span></p>
                @empty
                    <p class="mt-2 text-slate-500">Aucune zone : pas de frais calculés automatiquement.</p>
                @endforelse
                @if ($store->free_delivery_threshold !== null)<p class="mt-2 text-xs text-emerald-700">Offerte dès {{ fcfa($store->free_delivery_threshold) }}</p>@endif
            </div>

            <div class="card card-body space-y-3 text-sm">
                <h2 class="font-semibold text-slate-900">Nom de domaine personnalisé</h2>
                @if ($store->custom_domain)
                    <p><a href="{{ $store->customDomainUrl() }}" target="_blank" rel="noopener" class="font-mono text-brand-700 hover:underline">{{ $store->customDomainUrl() }}</a></p>
                @endif
                @can('changeStatus', $store)
                    <form method="POST" action="{{ route('admin.stores.domain', $store) }}" class="space-y-2">
                        @csrf @method('PUT')
                        <input name="custom_domain" value="{{ old('custom_domain', $store->custom_domain) }}" placeholder="ex. bellafashion.com" class="form-input font-mono {{ $errors->has('custom_domain') ? 'is-invalid' : '' }}" aria-label="Domaine">
                        @error('custom_domain')<p class="form-error">{{ $message }}</p>@enderror
                        <button class="btn btn-secondary btn-sm w-full">{{ $store->custom_domain ? 'Modifier / retirer (laisser vide)' : 'Attribuer le domaine' }}</button>
                    </form>
                    <details class="text-xs text-slate-500">
                        <summary class="cursor-pointer font-medium text-slate-700">Configuration DNS et serveur</summary>
                        <ol class="mt-2 list-inside list-decimal space-y-1">
                            <li>Chez le registraire du domaine : enregistrement <strong>A</strong> vers l'adresse IP du serveur GKAS Shop (et <strong>CNAME</strong> www → domaine si besoin).</li>
                            <li>Sur le serveur : ajouter le domaine à l'hôte virtuel Apache (ServerAlias) et installer un certificat HTTPS.</li>
                            <li>Attribuer ici le domaine exact saisi par les clients (avec ou sans www).</li>
                        </ol>
                    </details>
                @endcan
            </div>

            @can('extendTrial', $store)
                <form method="POST" action="{{ route('admin.stores.trial.extend', $store) }}" class="card card-body space-y-3">
                    @csrf
                    <h2 class="font-semibold text-slate-900">Prolonger l'essai</h2>
                    <p class="text-xs text-slate-500">Opération exceptionnelle, enregistrée avec votre nom et le motif. Fin actuelle : {{ $store->trial_ends_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    <x-form.input name="days" type="number" label="Nombre de jours" min="1" max="90" required />
                    <x-form.textarea name="reason" label="Motif" rows="2" required />
                    <button class="btn btn-secondary w-full" @disabled($store->hasActiveSubscription())>Prolonger l'essai</button>
                </form>
            @endcan

            @can('changeStatus', $store)
                <div class="card card-body space-y-3">
                    <h2 class="font-semibold text-slate-900">Statut administratif</h2>
                    @if ($store->suspension_reason)<p class="rounded bg-red-50 p-2 text-sm text-red-800">Motif : {{ $store->suspension_reason }}</p>@endif
                    @foreach ($statuses as $status)
                        @continue($status === $store->status)
                        <form method="POST" action="{{ route('admin.stores.status', $store) }}" class="space-y-2" data-confirm="Passer la boutique au statut « {{ $status->label() }} » ?">
                            @csrf
                            <input type="hidden" name="status" value="{{ $status->value }}">
                            @if ($status !== \App\Enums\StoreStatus::Active)
                                <input name="reason" required maxlength="500" class="form-input" placeholder="Motif ({{ mb_strtolower($status->label()) }})" aria-label="Motif">
                            @endif
                            <button @class(['btn w-full', 'btn-primary' => $status === \App\Enums\StoreStatus::Active, 'btn-danger' => $status === \App\Enums\StoreStatus::Suspended, 'btn-secondary' => $status === \App\Enums\StoreStatus::Archived])>
                                {{ ['active' => 'Réactiver', 'suspended' => 'Suspendre', 'archived' => 'Archiver'][$status->value] }}
                            </button>
                        </form>
                    @endforeach
                    <p class="text-xs text-slate-500">Un paiement confirmé ne réactive jamais une boutique suspendue : la réactivation est une décision administrative.</p>
                </div>
            @endcan
        </div>
    </div>
@endsection
