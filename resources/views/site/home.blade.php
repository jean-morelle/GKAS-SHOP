@extends('layouts.site')

@section('content')
    {{-- Héros --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 via-white to-white">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 pt-14 pb-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pt-20">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-accent-500/10 px-3 py-1 text-sm font-semibold text-accent-600">
                    <x-icon name="sparkles" class="size-4" /> 30 jours d'essai gratuit
                </span>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                    Votre boutique en ligne,<br><span class="text-brand-600">prête à partager</span> sur vos réseaux.
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-slate-600">
                    Vous vendez sur TikTok, Facebook, Instagram ou WhatsApp ? GKAS Shop vous donne une vraie boutique
                    pour présenter vos produits, recevoir des commandes et organiser votre activité — sans connaissances techniques.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('site.trial') }}" class="btn btn-accent px-6 py-3 text-base">Demander une boutique d'essai</a>
                    <a href="{{ route('site.demo') }}" class="btn btn-secondary px-6 py-3 text-base"><x-icon name="eye" class="size-5" /> Découvrir la démonstration</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">Sans engagement. Nous créons la boutique avec vous.</p>
            </div>

            {{-- Aperçu illustratif d'une boutique sur téléphone --}}
            <div class="relative mx-auto w-full max-w-sm" aria-hidden="true">
                <div class="absolute -inset-6 -z-10 rounded-[3rem] bg-gradient-to-tr from-brand-200/60 to-accent-400/30 blur-2xl"></div>
                <div class="rounded-[2.5rem] border-[10px] border-slate-900 bg-white shadow-2xl">
                    <div class="rounded-t-[1.8rem] bg-brand-600 px-5 pt-6 pb-5 text-white">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-white/20 font-bold">VB</span>
                            <div><p class="font-semibold">Votre Boutique</p><p class="text-xs text-brand-100">gkasshop.com/votre-boutique</p></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-4">
                        @foreach ([['bg-rose-100', '12 500'], ['bg-amber-100', '8 000'], ['bg-emerald-100', '15 000'], ['bg-sky-100', '6 500']] as [$bg, $price])
                            <div class="rounded-xl border border-slate-100 p-2">
                                <div class="aspect-square rounded-lg {{ $bg }}"></div>
                                <div class="mt-2 h-2 w-3/4 rounded bg-slate-200"></div>
                                <p class="mt-1.5 text-xs font-bold text-slate-900">{{ $price }} FCFA</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-4 pb-6">
                        <div class="flex items-center justify-center gap-2 rounded-xl bg-emerald-500 py-3 text-sm font-semibold text-white">
                            <x-icon name="whatsapp" class="size-4" /> Commander sur WhatsApp
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Bénéfices --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Tout ce qu'il faut pour vendre sereinement</h2>
            <p class="mt-3 text-slate-600">Une plateforme pensée pour les vendeurs des réseaux sociaux, simple à utiliser depuis un téléphone.</p>
        </div>
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['cube', 'Présentez vos produits', 'Photos, prix en FCFA, promotions, catégories : un catalogue clair que vos clients parcourent facilement.'],
                ['bag', 'Recevez des commandes', 'Vos clients commandent sans créer de compte. Vous recevez tout : produits, adresse, téléphone.'],
                ['list', 'Organisez votre activité', 'Suivez chaque commande, de la confirmation à la livraison, depuis un tableau de bord simple.'],
                ['share', 'Partagez partout', 'Un lien unique à mettre dans votre bio TikTok, Instagram, Facebook ou votre statut WhatsApp.'],
            ] as [$icon, $title, $text])
                <div class="card card-body">
                    <span class="flex size-11 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-icon :name="$icon" class="size-6" /></span>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Étapes --}}
    <section class="bg-slate-50 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">Comment ça marche ?</h2>
            <ol class="mt-12 grid gap-6 md:grid-cols-4">
                @foreach ([
                    ['Vous nous contactez', 'Remplissez le formulaire ou écrivez-nous sur WhatsApp.'],
                    ['Nous créons votre boutique', 'Avec votre nom, votre logo et vos premiers produits.'],
                    ['30 jours pour essayer', 'Partagez votre lien et recevez de vraies commandes.'],
                    ['Vous continuez si ça vous plaît', 'Choisissez la formule adaptée. Sinon, rien à payer.'],
                ] as $i => [$title, $text])
                    <li class="relative rounded-xl bg-white p-6 shadow-sm">
                        <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-10 text-center">
                <a href="{{ route('site.how') }}" class="btn btn-secondary">En savoir plus <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </div>
    </section>

    @if ($demoStores->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Visitez une boutique de démonstration</h2>
                    <p class="mt-2 text-slate-600">Boutiques d'exemple créées par GKAS-GROUP pour illustrer la plateforme.</p>
                </div>
                <a href="{{ route('site.demo') }}" class="btn btn-secondary">Toutes les démonstrations</a>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-2">
                @foreach ($demoStores as $demo)
                    @include('site._demo-card', ['demo' => $demo])
                @endforeach
            </div>
        </section>
    @endif

    @if ($plans->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">Des formules accessibles</h2>
            <p class="mt-3 text-center text-slate-600">Commencez gratuitement pendant {{ \App\Models\Setting::trialDays() }} jours, puis choisissez votre formule.</p>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($plans as $plan)
                    @include('site._plan-card', ['plan' => $plan, 'highlight' => $loop->iteration === 2])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Appel à l'action --}}
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-br from-brand-700 to-brand-900 px-6 py-14 text-center text-white sm:px-12">
            <h2 class="text-3xl font-bold tracking-tight">Prêt à lancer votre boutique ?</h2>
            <p class="mx-auto mt-3 max-w-xl text-brand-100">Un conseiller GKAS-GROUP vous accompagne pour la mise en place. L'essai est gratuit pendant {{ \App\Models\Setting::trialDays() }} jours.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('site.trial') }}" class="btn btn-accent px-6 py-3 text-base">Demander une boutique d'essai</a>
                <a href="{{ route('site.contact') }}" class="btn bg-white/10 px-6 py-3 text-base text-white hover:bg-white/20">Contacter GKAS-GROUP</a>
            </div>
        </div>
    </section>
@endsection
