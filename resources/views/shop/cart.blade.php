@extends('layouts.shop', ['title' => 'Panier'])

@section('content')
    <h1 class="mb-5 text-2xl font-extrabold">Mon panier</h1>

    @if ($cart['lines']->isEmpty())
        <div class="rounded-shop border border-dashed border-slate-300 bg-white">
            <x-empty icon="cart" title="Votre panier est vide" text="Parcourez la boutique et ajoutez les produits qui vous plaisent.">
                <a href="{{ shop_route('home', $store) }}" class="btn-shop">Voir les produits</a>
            </x-empty>
        </div>
    @else
        <form method="POST" action="{{ shop_route('cart.update', $store) }}" class="grid gap-6 lg:grid-cols-3">
            @csrf @method('PATCH')
            <div class="space-y-3 lg:col-span-2">
                @foreach ($cart['lines'] as $line)
                    <div class="rounded-shop flex gap-3 border border-black/5 bg-white p-3">
                        <a href="{{ shop_route('product', [$store, $line->product->slug]) }}" class="rounded-shop size-20 shrink-0 overflow-hidden bg-slate-100">
                            @if ($line->product->imageUrl())<img src="{{ $line->product->imageUrl() }}" alt="" class="size-full object-cover">@endif
                        </a>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $line->product->name }}</p>
                            <p class="text-sm text-slate-500">{{ fcfa($line->unit_price) }} / unité</p>
                            @if ($line->exceeds_stock)
                                <p class="text-xs font-semibold text-red-600">Seulement {{ $line->product->stock_quantity }} disponible(s)</p>
                            @endif
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <label class="flex items-center gap-2 text-sm">
                                    <span class="sr-only">Quantité</span>
                                    <input type="number" name="quantities[{{ $line->product->id }}]" value="{{ $line->quantity }}" min="0" max="99"
                                        class="rounded-shop w-20 border border-slate-300 bg-white px-2 py-1.5 text-center text-slate-900">
                                </label>
                                <span class="font-bold whitespace-nowrap">{{ fcfa($line->line_total) }}</span>
                            </div>
                        </div>
                        <button type="submit" name="quantities[{{ $line->product->id }}]" value="0" class="self-start p-1 text-slate-400 hover:text-red-600" title="Retirer">
                            <x-icon name="trash" class="size-5" /><span class="sr-only">Retirer {{ $line->product->name }}</span>
                        </button>
                    </div>
                @endforeach
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn-shop-outline py-2 text-sm">Mettre à jour les quantités</button>
                    <a href="{{ shop_route('home', $store) }}" class="px-3 py-2 text-sm underline">Continuer mes achats</a>
                </div>
            </div>

            <aside class="rounded-shop h-fit border border-black/5 bg-white p-5 lg:sticky lg:top-24">
                <h2 class="font-bold">Récapitulatif</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Articles</dt><dd>{{ $cart['count'] }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold"><dt>Total</dt><dd>{{ fcfa($cart['total']) }}</dd></div>
                </dl>
                <p class="mt-2 text-xs text-slate-500">
                    @if ($store->deliveryZones()->where('is_active', true)->exists())
                        Frais de livraison calculés selon votre zone à l'étape suivante.
                    @else
                        Les frais de livraison éventuels sont confirmés par le vendeur.
                    @endif
                </p>
                <a href="{{ shop_route('checkout', $store) }}" class="btn-shop mt-5 w-full">Passer la commande <x-icon name="arrow-right" class="size-4" /></a>
            </aside>
        </form>
    @endif
@endsection
