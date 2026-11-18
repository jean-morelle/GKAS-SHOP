@extends('admin._layout', ['title' => 'Enregistrer un paiement'])

@section('content')
    <x-page-header title="Enregistrer un paiement" :subtitle="$store->name" :back="route('admin.stores.show', $store)" />

    <form method="POST" action="{{ route('admin.stores.payments.store', $store) }}" enctype="multipart/form-data" class="card max-w-2xl">
        @csrf
        <div class="card-body space-y-4">
            <p class="text-sm text-slate-600">Pour un paiement reçu directement par GKAS-GROUP. Il sera enregistré « en attente » : vous le confirmerez ensuite pour activer l'abonnement.</p>
            <x-form.select name="plan_id" label="Formule" :options="$plans->mapWithKeys(fn ($p) => [$p->id => $p->name.' — '.fcfa($p->price).' / '.$p->durationLabel()])" placeholder="— Choisir —" required />
            <x-form.select name="method" label="Moyen de paiement" :options="collect($methods)->mapWithKeys(fn ($m) => [$m->value => $m->label()])" required />
            <x-form.input name="transaction_reference" label="Référence (n° de transaction, de reçu…)" required />
            <div>
                <label class="form-label" for="proof">Justificatif (facultatif)</label>
                <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" class="block w-full text-sm text-slate-600">
                @error('proof')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <x-form.textarea name="payer_note" label="Note" rows="2" />
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
@endsection
