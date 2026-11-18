@extends('layouts.shop', ['title' => $product->name])

@section('content')
    <nav class="mb-4 text-sm text-slate-500" aria-label="Fil d'Ariane">
        <a href="{{ shop_route('home', $store) }}" class="hover:underline">Accueil</a>
        @if ($product->category)
            / <a href="{{ shop_route('home', [$store, 'categorie' => $product->category->slug]) }}" class="hover:underline">{{ $product->category->name }}</a>
        @endif
    </nav>

    <div class="grid gap-6 md:grid-cols-2 md:gap-10">
        <div>
            <div class="rounded-shop aspect-square overflow-hidden bg-white">
                @if ($product->images->isNotEmpty())
                    <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" class="size-full object-cover" data-gallery-main>
                @else
                    <span class="flex size-full items-center justify-center text-slate-300"><x-icon name="photo" class="size-20" /></span>
                @endif
            </div>
            @if ($product->images->count() > 1)
                <div class="mt-3 flex gap-2 overflow-x-auto">
                    @foreach ($product->images as $image)
                        <button type="button" data-gallery-src="{{ $image->url() }}" class="rounded-shop size-16 shrink-0 overflow-hidden border-2 border-transparent bg-white focus:border-shop-primary sm:size-20">
                            <img src="{{ $image->url() }}" alt="Photo {{ $loop->iteration }}" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $product->name }}</h1>
            <p class="mt-3">
                <span class="text-shop-primary text-3xl font-extrabold">{{ fcfa($product->effectivePrice()) }}</span>
                @if ($product->hasDiscount())
                    <span class="ml-2 text-lg text-slate-400 line-through">{{ fcfa($product->price) }}</span>
                @endif
            </p>
            <p class="mt-2 text-sm">
                @if (! $product->isAvailable())
                    <span class="font-semibold text-red-600">Rupture de stock</span>
                @elseif ($product->tracksStock() && $product->stock_quantity <= 5)
                    <span class="font-semibold text-amber-600">Plus que {{ $product->stock_quantity }} en stock</span>
                @else
                    <span class="font-semibold text-emerald-600">Disponible</span>
                @endif
            </p>

            @if ($product->isAvailable())
                <form method="POST" action="{{ shop_route('cart.add', $store) }}" class="mt-6 space-y-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="flex items-center gap-3">
                        <label for="qty" class="text-sm font-medium">Quantité</label>
                        <input id="qty" type="number" name="quantity" value="1" min="1" max="{{ $product->tracksStock() ? min(99, $product->stock_quantity) : 99 }}"
                            class="rounded-shop w-24 border border-slate-300 bg-white px-3 py-2 text-center text-slate-900">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button class="btn-shop w-full" name="buy_now" value="1"><x-icon name="bag" class="size-5" /> Commander</button>
                        <button class="btn-shop-outline w-full"><x-icon name="cart" class="size-5" /> Ajouter au panier</button>
                    </div>
                </form>
            @endif

            @if ($store->whatsappNumber())
                <a href="https://wa.me/{{ $store->whatsappNumber() }}?text={{ rawurlencode("Bonjour {$store->name}, je suis intéressé(e) par : {$product->name} (".fcfa($product->effectivePrice()).")\n".shop_route('product', [$store, $product->slug])) }}"
                   target="_blank" rel="noopener" class="mt-3 flex items-center justify-center gap-2 rounded-shop border border-emerald-500 px-4 py-3 font-semibold text-emerald-700 hover:bg-emerald-50">
                    <x-icon name="whatsapp" class="size-5" /> Poser une question sur WhatsApp
                </a>
            @endif

            @if ($product->description)
                <div class="mt-8">
                    <h2 class="font-bold">Description</h2>
                    <p class="mt-2 leading-relaxed whitespace-pre-line text-slate-700">{{ $product->description }}</p>
                </div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-14">
            <h2 class="mb-4 text-lg font-bold">Vous aimerez aussi</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                @foreach ($related as $product)
                    @include('shop._product-card')
                @endforeach
            </div>
        </section>
    @endif
@endsection
