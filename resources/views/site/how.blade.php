@extends('layouts.site', ['title' => 'Comment ça marche'])

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-center text-4xl font-extrabold tracking-tight text-slate-900">Comment ça marche ?</h1>
        <p class="mt-4 text-center text-lg text-slate-600">De la première prise de contact à votre première commande, nous vous accompagnons.</p>

        @php $days = \App\Models\Setting::trialDays(); @endphp
        <ol class="relative mt-14 space-y-10 border-l-2 border-brand-100 pl-8">
            @foreach ([
                ['chat', 'Prise de contact', 'Vous remplissez le formulaire de demande d\'essai, ou un conseiller GKAS-GROUP vous contacte. Nous discutons de votre activité et de vos produits.'],
                ['eye', 'Démonstration', 'Nous vous montrons la plateforme : à quoi ressemblera votre boutique, comment recevoir et suivre vos commandes.'],
                ['store', 'Création de votre boutique', 'GKAS-GROUP crée votre boutique avec votre nom, votre logo, vos couleurs et vos premiers produits. Vous recevez vos identifiants de connexion.'],
                ['clock', "{$days} jours d'essai gratuit", "Vous partagez votre lien (ex. gkasshop.com/votre-boutique) sur vos réseaux et recevez de vraies commandes. Vous voyez à tout moment le nombre de jours restants."],
                ['card', 'Choix d\'une formule', 'Avant la fin de l\'essai, nous vous recontactons. Si vous souhaitez continuer, vous choisissez une formule et payez (Mobile Money, virement…). Votre abonnement est activé dès la vérification du paiement.'],
                ['lock', 'Et si vous ne continuez pas ?', 'Votre boutique cesse simplement d\'accepter des commandes. Vos données sont conservées : vous pouvez les exporter, et réactiver votre boutique plus tard.'],
            ] as $i => [$icon, $title, $text])
                <li class="relative">
                    <span class="absolute -left-[3.05rem] flex size-10 items-center justify-center rounded-full bg-brand-600 text-white ring-4 ring-white"><x-icon :name="$icon" class="size-5" /></span>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $i + 1 }}. {{ $title }}</h2>
                    <p class="mt-1 text-slate-600">{{ $text }}</p>
                </li>
            @endforeach
        </ol>

        <div class="mt-14 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ route('site.trial') }}" class="btn btn-accent px-6 py-3 text-base">Demander une boutique d'essai</a>
            <a href="{{ route('site.demo') }}" class="btn btn-secondary px-6 py-3 text-base">Voir une démonstration</a>
        </div>
    </section>
@endsection
