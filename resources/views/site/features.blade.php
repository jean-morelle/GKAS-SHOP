@extends('layouts.site', ['title' => 'Fonctionnalités'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Fonctionnalités</h1>
            <p class="mt-4 text-lg text-slate-600">Une boutique professionnelle pour vos clients, un espace de gestion simple pour vous.</p>
        </div>

        @foreach ([
            ['Pour vos clients', 'store', [
                ['device', 'Pensée pour le téléphone', 'Navigation rapide, grandes photos, boutons faciles à toucher.'],
                ['search', 'Recherche et catégories', 'Vos clients trouvent rapidement ce qu\'ils cherchent, avec des filtres simples.'],
                ['cart', 'Panier et commande sans compte', 'Le client choisit ses produits, indique son quartier et ses instructions de livraison.'],
                ['whatsapp', 'Commande via WhatsApp', 'Un message récapitulatif est préparé automatiquement pour vous l\'envoyer en un clic.'],
                ['tag', 'Prix en FCFA et promotions', 'Prix barrés, mise en avant de produits, disponibilité affichée.'],
                ['share', 'Vos réseaux sociaux', 'Liens vers votre TikTok, Instagram, Facebook et bouton WhatsApp toujours visible.'],
            ]],
            ['Pour vous, vendeur', 'chart', [
                ['chart', 'Tableau de bord clair', 'Commandes à traiter, ventes réalisées, produits disponibles : l\'essentiel en un coup d\'œil.'],
                ['cube', 'Catalogue facile à gérer', 'Ajoutez un produit avec ses photos en moins d\'une minute, depuis votre téléphone.'],
                ['truck', 'Suivi des commandes', 'De « Nouvelle » à « Livrée » : chaque étape est enregistrée et horodatée.'],
                ['brush', 'Votre identité', 'Logo, bannière, couleur principale et thèmes prêts à l\'emploi, avec prévisualisation.'],
                ['download', 'Vos données vous appartiennent', 'Exportez vos produits et commandes à tout moment (fichier CSV compatible Excel).'],
                ['shield', 'Sécurité', 'Chaque boutique est isolée ; vos données ne sont accessibles qu\'à vous.'],
            ]],
        ] as [$heading, $icon, $features])
            <h2 class="mt-16 flex items-center gap-3 text-2xl font-bold text-slate-900"><x-icon :name="$icon" class="size-7 text-brand-600" /> {{ $heading }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as [$fi, $title, $text])
                    <div class="card card-body">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-icon :name="$fi" class="size-5" /></span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="mt-16 rounded-2xl bg-slate-50 p-6 text-sm text-slate-600">
            <p><strong class="text-slate-900">Paiement des commandes :</strong> dans cette version, les commandes sont confirmées par vous, directement avec votre client (paiement à la livraison, Mobile Money, etc.). GKAS Shop n'encaisse pas d'argent à votre place.</p>
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('site.trial') }}" class="btn btn-accent px-6 py-3 text-base">Demander une boutique d'essai</a>
        </div>
    </section>
@endsection
