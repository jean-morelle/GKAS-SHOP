@extends('layouts.site', ['title' => 'Démonstration'])

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-center text-4xl font-extrabold tracking-tight text-slate-900">Démonstration</h1>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-slate-600">
            Parcourez une boutique comme le ferait l'un de vos clients : catalogue, fiche produit, panier et commande.
        </p>
        <p class="mx-auto mt-2 max-w-2xl text-center text-sm text-slate-500">
            Ces boutiques sont des exemples créés par GKAS-GROUP. Les commandes passées sur une boutique de démonstration ne sont pas livrées.
        </p>

        <div class="mt-12 grid gap-6 md:grid-cols-2">
            @forelse ($demoStores as $demo)
                @include('site._demo-card', ['demo' => $demo])
            @empty
                <div class="card md:col-span-2">
                    <x-empty icon="store" title="Démonstration bientôt disponible" text="Contactez-nous : un conseiller vous présentera la plateforme en direct.">
                        <a href="{{ route('site.contact') }}" class="btn btn-primary">Contacter GKAS-GROUP</a>
                    </x-empty>
                </div>
            @endforelse
        </div>

        <div class="mt-14 rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center">
            <h2 class="text-xl font-bold text-slate-900">Vous voulez voir l'espace de gestion ?</h2>
            <p class="mt-2 text-slate-600">Un conseiller vous fait une démonstration personnalisée, avec vos propres produits.</p>
            <a href="{{ route('site.trial') }}" class="btn btn-accent mt-6">Demander une démonstration</a>
        </div>
    </section>
@endsection
