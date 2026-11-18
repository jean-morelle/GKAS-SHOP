@extends('layouts.shop')

@section('content')
    {{-- Présentation --}}
    @if (! $search && ! $category && ! request('page'))
        <section class="rounded-shop relative mb-6 overflow-hidden {{ $store->bannerUrl() ? '' : 'bg-shop-primary' }}">
            @if ($store->bannerUrl())
                <img src="{{ $store->bannerUrl() }}" alt="" class="absolute inset-0 size-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-black/20"></div>
            @endif
            <div class="relative px-5 py-10 text-white sm:px-10 sm:py-14">
                <h1 class="text-3xl font-extrabold sm:text-4xl">{{ $store->name }}</h1>
                @if ($store->description)
                    <p class="mt-3 max-w-2xl text-white/90">{{ $store->description }}</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-2 text-sm">
                    @if ($store->delivery_zone)<span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 backdrop-blur"><x-icon name="truck" class="size-4" /> {{ $store->delivery_zone }}</span>@endif
                    @if ($store->opening_hours)<span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 backdrop-blur"><x-icon name="clock" class="size-4" /> {{ $store->opening_hours }}</span>@endif
                </div>
            </div>
        </section>
    @endif

    {{-- Recherche mobile --}}
    <form action="{{ shop_route('home', $store) }}" class="mb-4 md:hidden" role="search">
        <input type="search" name="q" value="{{ $search }}" placeholder="Rechercher un produit…" aria-label="Rechercher"
            class="rounded-shop w-full border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none">
    </form>

    {{-- Catégories --}}
    @if ($categories->isNotEmpty())
        <nav class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" aria-label="Catégories">
            <a href="{{ shop_route('home', $store) }}" @class(['rounded-shop shrink-0 border px-4 py-2 text-sm font-medium whitespace-nowrap', 'bg-shop-primary border-transparent text-white' => ! $category, 'border-slate-200 bg-white' => $category])>Tout</a>
            @foreach ($categories as $cat)
                <a href="{{ shop_route('home', [$store, 'categorie' => $cat->slug]) }}" @class(['rounded-shop shrink-0 border px-4 py-2 text-sm font-medium whitespace-nowrap', 'bg-shop-primary border-transparent text-white' => $category?->is($cat), 'border-slate-200 bg-white' => ! $category?->is($cat)])>{{ $cat->name }}</a>
            @endforeach
        </nav>
    @endif

    {{-- Produits mis en avant --}}
    @if ($featured->isNotEmpty())
        <section class="mb-8">
            <h2 class="mb-3 flex items-center gap-2 text-lg font-bold"><x-icon name="star" class="text-shop-primary size-5" /> À la une</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                @foreach ($featured as $product)
                    @include('shop._product-card')
                @endforeach
            </div>
        </section>
    @endif

    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold">
            @if ($search) Résultats pour « {{ $search }} »
            @elseif ($category) {{ $category->name }}
            @else Tous nos produits @endif
            <span class="text-sm font-normal text-slate-500">({{ $products->total() }})</span>
        </h2>
        <form action="{{ shop_route('home', $store) }}" class="flex items-center gap-2">
            @if ($search)<input type="hidden" name="q" value="{{ $search }}">@endif
            @if ($category)<input type="hidden" name="categorie" value="{{ $category->slug }}">@endif
            <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="promo" value="1" @checked(request()->boolean('promo')) onchange="this.form.submit()"> Promos</label>
            <select name="tri" onchange="this.form.submit()" class="rounded-shop border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900" aria-label="Trier">
                <option value="">Nouveautés</option>
                <option value="prix-asc" @selected($sort === 'prix-asc')>Prix croissant</option>
                <option value="prix-desc" @selected($sort === 'prix-desc')>Prix décroissant</option>
            </select>
            <noscript><button class="btn-shop py-2 text-sm">OK</button></noscript>
        </form>
    </div>

    @if ($products->isEmpty())
        <div class="rounded-shop border border-dashed border-slate-300 bg-white">
            <x-empty icon="search" title="Aucun produit trouvé" text="Essayez une autre recherche ou une autre catégorie.">
                <a href="{{ shop_route('home', $store) }}" class="btn-shop-outline">Voir tous les produits</a>
            </x-empty>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('shop._product-card')
            @endforeach
        </div>
        <div class="rounded-shop mt-6 overflow-hidden bg-white">{{ $products->links() }}</div>
    @endif

    @if ($store->order_conditions)
        <section class="rounded-shop mt-10 border border-black/5 bg-white p-5">
            <h2 class="font-bold">Conditions de commande</h2>
            <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $store->order_conditions }}</p>
        </section>
    @endif
@endsection
