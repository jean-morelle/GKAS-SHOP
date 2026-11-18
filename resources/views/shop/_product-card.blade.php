<article class="shop-card rounded-shop group flex flex-col overflow-hidden border border-black/5 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ shop_route('product', [$store, $product->slug]) }}" class="relative block aspect-square overflow-hidden bg-slate-100">
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <span class="flex size-full items-center justify-center text-slate-300"><x-icon name="photo" class="size-12" /></span>
        @endif
        @if ($product->hasDiscount())
            <span class="absolute top-2 left-2 rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">Promo</span>
        @endif
        @if (! $product->isAvailable())
            <span class="absolute inset-x-0 bottom-0 bg-slate-900/75 py-1 text-center text-xs font-semibold text-white">Rupture de stock</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-3">
        <h3 class="line-clamp-2 text-sm font-medium"><a href="{{ shop_route('product', [$store, $product->slug]) }}">{{ $product->name }}</a></h3>
        <p class="mt-auto pt-2">
            <span class="text-shop-primary text-base font-bold whitespace-nowrap">{{ fcfa($product->effectivePrice()) }}</span>
            @if ($product->hasDiscount())
                <span class="ml-1 text-xs whitespace-nowrap text-slate-400 line-through">{{ fcfa($product->price) }}</span>
            @endif
        </p>
        @if ($product->isAvailable())
            <form method="POST" action="{{ shop_route('cart.add', $store) }}" class="mt-2">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button class="btn-shop w-full py-2 text-sm"><x-icon name="plus" class="size-4" /> Ajouter</button>
            </form>
        @endif
    </div>
</article>
