@extends('admin._layout', ['title' => $plan->exists ? 'Modifier la formule' : 'Nouvelle formule'])

@section('content')
    <x-page-header :title="$plan->exists ? 'Modifier « '.$plan->name.' »' : 'Nouvelle formule'" :back="route('admin.plans.index')" />

    <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="card max-w-2xl">
        @csrf
        @if ($plan->exists) @method('PUT') @endif
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Nom" :value="$plan->name" required class="sm:col-span-2" />
            <x-form.input name="price" type="number" label="Prix (FCFA)" :value="$plan->price !== null ? (int) $plan->price : null" min="0" step="1" required />
            <x-form.input name="duration_days" type="number" label="Durée (jours)" :value="$plan->duration_days" min="1" max="1095" required help="30 = 1 mois, 90 = 3 mois, 365 = 1 an" />
            <x-form.input name="description" label="Description courte" :value="$plan->description" class="sm:col-span-2" />
            <x-form.textarea name="features" label="Fonctionnalités incluses" :value="implode(PHP_EOL, $plan->features ?? [])" rows="5" class="sm:col-span-2" help="Une fonctionnalité par ligne." />
            <x-form.input name="position" type="number" label="Ordre d'affichage" :value="$plan->position" min="0" />
            <x-form.checkbox name="is_active" label="Proposée aux vendeurs" :checked="$plan->is_active" class="self-end" />
        </div>
        @if ($plan->exists)
            <p class="px-6 pb-4 text-xs text-slate-500">Une modification de prix ou de durée ne s'applique qu'aux futurs paiements : les abonnements déjà payés conservent leurs conditions.</p>
        @endif
        <div class="flex justify-end border-t border-slate-100 px-6 py-4"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
@endsection
