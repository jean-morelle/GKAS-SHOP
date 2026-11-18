@extends('admin._layout', ['title' => 'Paiement #'.$payment->id])

@section('content')
    <x-page-header :title="'Paiement #'.$payment->id" :subtitle="$payment->store->name" :back="route('admin.payments.index')">
        <x-badge :color="$payment->status->color()" class="px-3 py-1 text-sm">{{ $payment->status->label() }}</x-badge>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body lg:col-span-2">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Boutique</dt><dd><a href="{{ route('admin.stores.show', $payment->store) }}" class="font-medium text-brand-700 hover:underline">{{ $payment->store->name }}</a> <x-badge :color="$payment->store->accessState()->color()">{{ $payment->store->accessState()->label() }}</x-badge></dd></div>
                <div><dt class="text-slate-500">Formule</dt><dd class="font-medium">{{ $payment->plan->name }} ({{ $payment->plan->durationLabel() }})</dd></div>
                <div><dt class="text-slate-500">Montant attendu</dt><dd class="text-lg font-bold">{{ fcfa($payment->amount) }}</dd></div>
                <div><dt class="text-slate-500">Moyen de paiement</dt><dd>{{ $payment->method->label() }}</dd></div>
                <div><dt class="text-slate-500">Référence de transaction</dt><dd class="font-mono text-base font-semibold">{{ $payment->transaction_reference }}</dd></div>
                <div><dt class="text-slate-500">Déclaré par</dt><dd>{{ $payment->submitter?->name ?? '—' }} le {{ $payment->created_at->format('d/m/Y à H:i') }}</dd></div>
                @if ($payment->payer_note)<div class="sm:col-span-2"><dt class="text-slate-500">Message</dt><dd class="rounded bg-slate-50 p-2">{{ $payment->payer_note }}</dd></div>@endif
                <div class="sm:col-span-2"><dt class="text-slate-500">Justificatif</dt><dd>
                    @if ($payment->proof_path)
                        <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank" class="btn btn-secondary btn-sm mt-1"><x-icon name="document" class="size-4" /> Ouvrir le justificatif</a>
                    @else <span class="text-slate-500">Aucun</span> @endif
                </dd></div>
                @if ($payment->reviewed_at)
                    <div class="sm:col-span-2 border-t border-slate-100 pt-4"><dt class="text-slate-500">Traitement</dt>
                        <dd>{{ $payment->status->label() }} par {{ $payment->reviewer?->name ?? '—' }} le {{ $payment->reviewed_at->format('d/m/Y à H:i') }}
                            @if ($payment->review_note)<span class="block text-slate-600">« {{ $payment->review_note }} »</span>@endif
                            @if ($payment->subscription)<span class="block text-emerald-700">Abonnement : {{ $payment->subscription->starts_at->format('d/m/Y') }} → {{ $payment->subscription->ends_at->format('d/m/Y') }}</span>@endif
                        </dd></div>
                @endif
            </dl>
        </div>

        @if ($payment->isPending())
            @can('review', $payment)
                <div class="space-y-6">
                    <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" class="card card-body space-y-3" data-confirm="Confirmer ce paiement de {{ fcfa($payment->amount) }} ? L'abonnement sera activé ou prolongé." data-once>
                        @csrf
                        <h2 class="font-semibold text-slate-900">Confirmer le paiement</h2>
                        <p class="text-sm text-slate-600">Vérifiez la réception effective des fonds (relevé Mobile Money / bancaire) avant de confirmer.</p>
                        @if (! $payment->store->hasActiveSubscription())
                            <p class="text-xs text-slate-500">L'abonnement démarrera immédiatement.</p>
                        @else
                            <p class="text-xs text-slate-500">Renouvellement : la nouvelle période démarrera le {{ $payment->store->subscription_ends_at->format('d/m/Y') }}.</p>
                        @endif
                        @if ($payment->store->status !== \App\Enums\StoreStatus::Active)
                            <p class="rounded bg-amber-50 p-2 text-xs text-amber-900">La boutique est {{ mb_strtolower($payment->store->status->label()) }} : la confirmation ne la réactivera pas.</p>
                        @endif
                        <x-form.input name="note" label="Note (facultatif)" />
                        <button class="btn btn-primary w-full"><x-icon name="check" class="size-4" /> Confirmer et activer</button>
                    </form>
                    <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="card card-body space-y-3" data-confirm="Rejeter ce paiement ?">
                        @csrf
                        <h2 class="font-semibold text-slate-900">Rejeter</h2>
                        <x-form.textarea name="reason" label="Motif du rejet" rows="2" required />
                        <button class="btn btn-danger w-full">Rejeter le paiement</button>
                    </form>
                </div>
            @else
                <div class="card card-body text-sm text-slate-600">Seul un administrateur peut confirmer ou rejeter un paiement.</div>
            @endcan
        @endif
    </div>
@endsection
