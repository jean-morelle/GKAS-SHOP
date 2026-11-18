@extends('seller._layout', ['title' => 'Abonnement'])

@section('content')
    @php $state = $store->accessState(); @endphp
    <x-page-header title="Abonnement" subtitle="Votre essai, votre formule et vos paiements." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body">
            <p class="text-sm text-slate-500">État actuel</p>
            <p class="mt-2"><x-badge :color="$state->color()" class="px-3 py-1 text-sm">{{ $state->label() }}</x-badge></p>
            <dl class="mt-5 space-y-3 text-sm">
                @if ($store->trial_ends_at)
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Essai gratuit</dt><dd class="text-right">{{ $store->trial_starts_at?->format('d/m/Y') }} → {{ $store->trial_ends_at->format('d/m/Y') }}</dd></div>
                @endif
                @if ($store->subscription_ends_at)
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Abonnement jusqu'au</dt><dd class="font-semibold">{{ $store->subscription_ends_at->format('d/m/Y') }}</dd></div>
                @endif
                @if ($state->isOperational())
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Jours restants</dt><dd class="text-lg font-bold">{{ $store->daysRemaining() }}</dd></div>
                @endif
            </dl>
            <div class="mt-6 space-y-2 border-t border-slate-100 pt-4">
                <p class="text-sm font-medium text-slate-700">Exporter mes données</p>
                <div class="flex gap-2">
                    <a href="{{ route('vendor.export', [$store, 'produits']) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Produits</a>
                    <a href="{{ route('vendor.export', [$store, 'commandes']) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Commandes</a>
                </div>
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            @if ($state === \App\Enums\AccessState::Suspended || $state === \App\Enums\AccessState::Archived)
                <div class="card card-body text-sm text-slate-700">
                    <p class="font-semibold text-slate-900">Boutique {{ mb_strtolower($state->label()) }} par GKAS-GROUP</p>
                    @if ($store->suspension_reason)<p class="mt-1">Motif : {{ $store->suspension_reason }}</p>@endif
                    <p class="mt-2">Contactez GKAS-GROUP pour la réactiver{{ $platform['contact_phone'] ? ' au '.$platform['contact_phone'] : '' }}.</p>
                </div>
            @endif

            @if ($hasPending)
                <div class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <x-icon name="clock" class="mt-0.5 size-5 shrink-0" />
                    <p>Un paiement est en cours de vérification par GKAS-GROUP. Votre abonnement sera activé dès sa confirmation.</p>
                </div>
            @elseif ($plans->isNotEmpty())
                <form method="POST" action="{{ route('vendor.subscription.pay', $store) }}" enctype="multipart/form-data" class="card">
                    @csrf
                    <div class="card-body space-y-5">
                        <div>
                            <h2 class="font-semibold text-slate-900">1. Choisissez votre formule</h2>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($plans as $plan)
                                    <label class="flex cursor-pointer flex-col rounded-xl border border-slate-200 p-4 hover:bg-slate-50 has-checked:border-brand-500 has-checked:ring-2 has-checked:ring-brand-500/20">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="font-semibold text-slate-900">{{ $plan->name }}</span>
                                            <input type="radio" name="plan_id" value="{{ $plan->id }}" required @checked((int) old('plan_id') === $plan->id) class="text-brand-600">
                                        </span>
                                        <span class="mt-1 text-xl font-extrabold text-slate-900">{{ fcfa($plan->price) }} <span class="text-sm font-normal text-slate-500">/ {{ $plan->durationLabel() }}</span></span>
                                        @if ($plan->features)
                                            <ul class="mt-2 space-y-1 text-xs text-slate-600">@foreach ($plan->features as $f)<li>✓ {{ $f }}</li>@endforeach</ul>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            @error('plan_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-900">2. Effectuez le paiement</h2>
                            <div class="mt-3 rounded-lg bg-slate-50 p-4 text-sm whitespace-pre-line text-slate-700">{{ $instructions ?: 'Les instructions de paiement seront communiquées par GKAS-GROUP. Contactez-nous'.($platform['contact_phone'] ? ' au '.$platform['contact_phone'] : '').'.' }}</div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <h2 class="font-semibold text-slate-900 sm:col-span-2">3. Déclarez votre paiement</h2>
                            <x-form.select name="method" label="Moyen de paiement" :options="collect($methods)->mapWithKeys(fn ($m) => [$m->value => $m->label()])" required />
                            <x-form.input name="transaction_reference" label="Référence de la transaction" required help="Numéro reçu par SMS après le paiement." />
                            <div class="sm:col-span-2">
                                <label class="form-label" for="proof">Justificatif (facultatif)</label>
                                <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-medium file:text-brand-700">
                                <p class="form-help">Capture d'écran du SMS ou reçu. Image ou PDF, 4 Mo max.</p>
                                @error('proof')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <x-form.textarea name="payer_note" label="Message pour GKAS-GROUP (facultatif)" rows="2" class="sm:col-span-2" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">Votre abonnement est activé après vérification du paiement par GKAS-GROUP.</p>
                        <button class="btn btn-primary">Envoyer pour vérification</button>
                    </div>
                </form>
            @else
                <div class="card"><x-empty icon="tag" title="Aucune formule disponible" text="Contactez GKAS-GROUP pour connaître les formules d'abonnement." /></div>
            @endif

            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Historique des paiements</h2>
                @forelse ($payments as $payment)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0 text-sm">
                        <div>
                            <p class="font-medium text-slate-900">{{ $payment->plan->name }} — {{ fcfa($payment->amount) }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->created_at->format('d/m/Y') }} · {{ $payment->method->label() }} · réf. {{ $payment->transaction_reference }}</p>
                            @if ($payment->review_note && $payment->status !== \App\Enums\PaymentStatus::Confirmed)<p class="text-xs text-red-600">{{ $payment->review_note }}</p>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :color="$payment->status->color()">{{ $payment->status->label() }}</x-badge>
                            @if ($payment->isPending())
                                <form method="POST" action="{{ route('vendor.subscription.cancel', [$store, $payment]) }}" data-confirm="Annuler cette déclaration de paiement ?">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm text-red-600">Annuler</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Aucun paiement pour le moment.</p>
                @endforelse
            </div>

            @if ($subscriptions->isNotEmpty())
                <div class="card overflow-hidden">
                    <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Périodes d'abonnement</h2>
                    @foreach ($subscriptions as $sub)
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-sm last:border-0">
                            <span>{{ $sub->plan_name }} · {{ $sub->starts_at->format('d/m/Y') }} → {{ $sub->ends_at->format('d/m/Y') }}</span>
                            <x-badge :color="$sub->isCurrent() ? 'green' : 'gray'">{{ $sub->statusLabel() }}</x-badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
