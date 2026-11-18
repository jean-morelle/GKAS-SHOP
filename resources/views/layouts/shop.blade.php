@php
    $cartCount = app(\App\Services\CartService::class)->count($store);
    $theme = $store->theme?->value ?? 'moderne';
    $wa = $store->whatsappNumber();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ $store->name }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($store->description ?: 'Boutique '.$store->name.' sur GKAS Shop', 155) }}">
    <meta property="og:title" content="{{ $store->name }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($store->description, 155) }}">
    @if ($store->logoUrl())<meta property="og:image" content="{{ $store->logoUrl() }}">@endif
    <meta name="theme-color" content="{{ $store->primary_color }}">
    @if ($store->is_demo || request()->attributes->get('shop_preview'))<meta name="robots" content="noindex">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="shop shop-{{ $theme }} min-h-screen font-sans antialiased" style="--shop-primary: {{ $store->primary_color }}">
    @if (request()->attributes->get('shop_preview'))
        <div class="bg-amber-400 px-4 py-2 text-center text-sm font-medium text-amber-950">
            Prévisualisation — votre boutique n'est pas encore publiée. Vos clients ne la voient pas.
            @if (auth()->user()?->isStaff())
                <a href="{{ route('admin.stores.edit', $store) }}" class="underline">Publier (administration)</a>
            @else
                <a href="{{ route('vendor.appearance.edit', $store) }}" class="underline">Publier</a>
            @endif
        </div>
    @elseif ($store->is_demo)
        <div class="bg-slate-900 px-4 py-2 text-center text-xs text-slate-200">
            Boutique de démonstration GKAS Shop — les commandes passées ici ne sont pas livrées.
        </div>
    @endif

    <header class="shop-header sticky top-0 z-30 border-b border-black/5 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
            <a href="{{ shop_route('home', $store) }}" class="flex min-w-0 items-center gap-3">
                @if ($store->logoUrl())
                    <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="size-10 rounded-full object-cover ring-2 ring-white">
                @else
                    <span class="bg-shop-primary flex size-10 shrink-0 items-center justify-center rounded-full font-bold text-white ring-2 ring-white">{{ $store->initials() }}</span>
                @endif
                <span class="truncate text-lg font-bold">{{ $store->name }}</span>
            </a>
            <form action="{{ shop_route('home', $store) }}" class="ml-auto hidden max-w-xs flex-1 md:block" role="search">
                <label class="sr-only" for="shop-search">Rechercher</label>
                <input id="shop-search" type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit…"
                    class="rounded-shop w-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-900 focus:bg-white focus:outline-none">
            </form>
            <a href="{{ shop_route('cart', $store) }}" class="relative ml-auto rounded-full p-2 md:ml-0" aria-label="Panier ({{ $cartCount }} article(s))">
                <x-icon name="cart" class="size-7" />
                @if ($cartCount)
                    <span class="bg-shop-primary absolute -top-0.5 -right-0.5 flex min-w-5 items-center justify-center rounded-full px-1 text-[11px] font-bold text-white ring-2 ring-white">{{ $cartCount }}</span>
                @endif
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 pt-5 pb-28">
        @if (session('success') || session('error') || $errors->any())
            <div class="mb-5"><x-flash /></div>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-black/5 bg-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-3">
            <div>
                <p class="font-bold">{{ $store->name }}</p>
                @if ($store->address)<p class="mt-2 flex gap-2 text-sm text-slate-600"><x-icon name="map-pin" class="size-4 shrink-0" /> {{ $store->address }}</p>@endif
                @if ($store->opening_hours)<p class="mt-1 flex gap-2 text-sm text-slate-600"><x-icon name="clock" class="size-4 shrink-0" /> {{ $store->opening_hours }}</p>@endif
            </div>
            <div class="text-sm text-slate-600">
                @if ($store->delivery_zone)<p class="flex gap-2"><x-icon name="truck" class="size-4 shrink-0" /> Livraison : {{ $store->delivery_zone }}</p>@endif
                @if ($store->phone)<p class="mt-1 flex gap-2"><x-icon name="phone" class="size-4 shrink-0" /> <a href="tel:{{ preg_replace('/[^\d+]/', '', $store->phone) }}">{{ $store->phone }}</a></p>@endif
            </div>
            <div class="flex items-start gap-3 sm:justify-end">
                @foreach (['facebook_url' => 'facebook', 'instagram_url' => 'instagram', 'tiktok_url' => 'tiktok'] as $field => $icon)
                    @if ($store->{$field})
                        <a href="{{ $store->{$field} }}" target="_blank" rel="noopener nofollow" class="flex size-10 items-center justify-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200" aria-label="{{ ucfirst($icon) }}"><x-icon :name="$icon" class="size-5" /></a>
                    @endif
                @endforeach
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200" aria-label="WhatsApp"><x-icon name="whatsapp" class="size-5" /></a>
                @endif
            </div>
        </div>
        <p class="border-t border-black/5 py-4 text-center text-xs text-slate-400">
            Boutique propulsée par <a href="{{ route('home') }}" class="font-semibold hover:text-slate-600">GKAS Shop</a>
        </p>
    </footer>

    @if ($wa && ! request()->routeIs('shop.checkout*', 'shop.order.*'))
        <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Bonjour '.$store->name.', ') }}" target="_blank" rel="noopener"
            class="fixed right-4 bottom-4 z-40 flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-3 font-semibold text-white shadow-lg hover:bg-emerald-600">
            <x-icon name="whatsapp" class="size-6" /> <span class="hidden sm:inline">Nous écrire</span>
        </a>
    @endif
</body>
</html>
