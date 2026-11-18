<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ $platform['platform_name'] }}</title>
    <meta name="description" content="{{ $description ?? 'GKAS Shop : votre boutique en ligne professionnelle pour vendre sur TikTok, Facebook, Instagram et WhatsApp. 30 jours d\'essai gratuit.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                @include('partials.brand-logo')
            </a>
            @php
                $links = [
                    'site.features' => 'Fonctionnalités',
                    'site.how' => 'Comment ça marche',
                    'site.demo' => 'Démonstration',
                    'site.pricing' => 'Tarifs',
                    'site.faq' => 'FAQ',
                    'site.contact' => 'Contact',
                ];
            @endphp
            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                @foreach ($links as $route => $label)
                    <a href="{{ route($route) }}" @class(['rounded-md px-3 py-2 text-sm font-medium transition', 'text-brand-700 bg-brand-50' => request()->routeIs($route), 'text-slate-600 hover:text-slate-900' => ! request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" class="btn btn-secondary">Mon espace</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Connexion</a>
                @endauth
                <a href="{{ route('site.trial') }}" class="btn btn-accent">Essai gratuit</a>
            </div>
            <button type="button" class="btn btn-ghost lg:hidden" data-toggle="#mobile-nav" aria-expanded="false" aria-controls="mobile-nav">
                <x-icon name="menu" class="size-6" /><span class="sr-only">Menu</span>
            </button>
        </div>
        <nav id="mobile-nav" class="border-t border-slate-200 bg-white px-4 py-3 lg:hidden" hidden>
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" class="block rounded-md px-3 py-2.5 text-base font-medium text-slate-700 hover:bg-slate-50">{{ $label }}</a>
            @endforeach
            <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                <a href="{{ route('login') }}" class="btn btn-secondary">Connexion</a>
                <a href="{{ route('site.trial') }}" class="btn btn-accent">Essai gratuit</a>
            </div>
        </nav>
    </header>

    <main>
        @if (session('success') || session('error') || $errors->any())
            <div class="mx-auto max-w-3xl px-4 pt-6"><x-flash /></div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-24 bg-slate-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 text-white">@include('partials.brand-logo', ['dark' => true])</div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed">
                    La boutique en ligne simple et professionnelle pour les vendeurs actifs sur les réseaux sociaux.
                    Une solution {{ $platform['company_name'] }}.
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white">Plateforme</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('site.features') }}" class="hover:text-white">Fonctionnalités</a></li>
                    <li><a href="{{ route('site.pricing') }}" class="hover:text-white">Tarifs</a></li>
                    <li><a href="{{ route('site.demo') }}" class="hover:text-white">Démonstration</a></li>
                    <li><a href="{{ route('site.faq') }}" class="hover:text-white">Questions fréquentes</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white">{{ $platform['company_name'] }}</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    @if ($platform['contact_phone'])<li>{{ $platform['contact_phone'] }}</li>@endif
                    @if ($platform['contact_email'])<li>{{ $platform['contact_email'] }}</li>@endif
                    @if ($platform['contact_address'])<li>{{ $platform['contact_address'] }}</li>@endif
                    <li><a href="{{ route('site.contact') }}" class="hover:text-white">Nous contacter</a></li>
                    <li><a href="{{ route('site.terms') }}" class="hover:text-white">Conditions d'utilisation</a></li>
                    <li><a href="{{ route('site.privacy') }}" class="hover:text-white">Confidentialité</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 py-6 text-center text-xs">© {{ date('Y') }} {{ $platform['company_name'] }}. Tous droits réservés.</div>
    </footer>
</body>
</html>
