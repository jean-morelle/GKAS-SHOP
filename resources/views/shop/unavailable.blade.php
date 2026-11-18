<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $store->name }} — momentanément indisponible</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        @if ($store->logoUrl())
            <img src="{{ $store->logoUrl() }}" alt="" class="mx-auto size-20 rounded-full object-cover">
        @else
            <span class="mx-auto flex size-20 items-center justify-center rounded-full text-2xl font-bold text-white" style="background: {{ $store->primary_color }}">{{ $store->initials() }}</span>
        @endif
        <h1 class="mt-5 text-xl font-bold text-slate-900">{{ $store->name }}</h1>
        @if (($reason ?? null) === 'unpublished')
            <p class="mt-3 text-slate-600">Cette boutique ouvre bientôt. Revenez dans quelques jours !</p>
        @else
            <p class="mt-3 text-slate-600">Cette boutique est momentanément indisponible et n'accepte pas de nouvelles commandes.</p>
            <p class="mt-1 text-sm text-slate-500">Si vous avez déjà passé commande, le vendeur reste joignable directement.</p>
        @endif
        @if ($store->whatsappNumber())
            <a href="https://wa.me/{{ $store->whatsappNumber() }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 font-semibold text-white hover:bg-emerald-600">
                <x-icon name="whatsapp" class="size-5" /> Contacter le vendeur
            </a>
        @endif
        <p class="mt-8 text-xs text-slate-400">Boutique hébergée par <a href="{{ route('home') }}" class="font-semibold hover:text-slate-600">GKAS Shop</a></p>
    </div>
</body>
</html>
