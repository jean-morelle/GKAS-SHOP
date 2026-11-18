@extends('admin._layout', ['title' => 'Nouveau prospect'])

@section('content')
    <x-page-header title="Ajouter un prospect" :back="route('admin.prospects.index')" />

    <form method="POST" action="{{ route('admin.prospects.store') }}" class="card max-w-3xl">
        @csrf
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Nom du vendeur" required />
            <x-form.input name="phone" type="tel" label="Téléphone / WhatsApp" required />
            <x-form.input name="business_name" label="Nom de la boutique" />
            <x-form.input name="activity" label="Activité" placeholder="ex. Vêtements, cosmétiques…" />
            <x-form.select name="social_network" label="Réseau social" :options="$networks" placeholder="— Choisir —" />
            <x-form.input name="social_handle" label="Compte / lien" />
            <x-form.input name="city" label="Ville" />
            <x-form.input name="email" type="email" label="E-mail" />
            <x-form.textarea name="message" label="Notes initiales" rows="3" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
@endsection
