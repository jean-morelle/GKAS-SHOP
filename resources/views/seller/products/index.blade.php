@extends('seller._layout', ['title' => 'Produits'])

@section('content')
    <x-page-header title="Produits" subtitle="Votre catalogue : ce que vos clients voient dans la boutique.">
        <a href="{{ route('vendor.export', [$store, 'produits']) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exporter</a>
        <a href="{{ route('vendor.products.create', $store) }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Ajouter un produit</a>
    </x-page-header>

    <form class="card mb-4 grid gap-3 p-4 sm:grid-cols-4" role="search">
        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Rechercher un produit…" class="form-input sm:col-span-2" aria-label="Rechercher">
        <select name="statut" class="form-input" aria-label="Filtrer par état">
            <option value="">Tous les états</option>
            @foreach (['actif' => 'Actifs', 'inactif' => 'Inactifs', 'rupture' => 'En rupture', 'vedette' => 'Mis en avant'] as $k => $v)
                <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="categorie" class="form-input" aria-label="Filtrer par catégorie">
                <option value="">Toutes catégories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($filters['categoryId'] === $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <button class="btn btn-secondary px-3" aria-label="Filtrer"><x-icon name="search" class="size-4" /></button>
        </div>
    </form>

    <div class="card overflow-hidden">
        @if ($products->isEmpty())
            <x-empty icon="cube" title="Aucun produit" :text="$filters['search'] || $filters['status'] || $filters['categoryId'] ? 'Aucun produit ne correspond à vos filtres.' : 'Ajoutez votre premier produit avec une belle photo et un prix clair.'">
                <a href="{{ route('vendor.products.create', $store) }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Ajouter un produit</a>
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Produit</th><th>Prix</th><th class="max-sm:hidden">Stock</th><th>État</th><th class="text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="size-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                            @if ($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="" class="size-full object-cover">@endif
                                        </span>
                                        <span class="min-w-0">
                                            <a href="{{ route('vendor.products.edit', [$store, $product]) }}" class="block truncate font-medium text-slate-900 hover:text-brand-700">{{ $product->name }}</a>
                                            <span class="text-xs text-slate-500">{{ $product->category?->name ?? 'Sans catégorie' }}</span>
                                            @if ($product->is_featured)<x-badge color="amber" class="ml-1">★ Vedette</x-badge>@endif
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="font-semibold">{{ fcfa($product->effectivePrice()) }}</span>
                                    @if ($product->hasDiscount())<span class="block text-xs text-slate-400 line-through">{{ fcfa($product->price) }}</span>@endif
                                </td>
                                <td class="max-sm:hidden">
                                    @if (! $product->tracksStock()) <span class="text-slate-400">—</span>
                                    @elseif ($product->stock_quantity === 0) <x-badge color="red">Rupture</x-badge>
                                    @else {{ $product->stock_quantity }} @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('vendor.products.toggle', [$store, $product]) }}">
                                        @csrf
                                        <button title="Cliquer pour {{ $product->is_active ? 'désactiver' : 'activer' }}">
                                            <x-badge :color="$product->is_active ? 'green' : 'gray'">{{ $product->is_active ? 'Visible' : 'Masqué' }}</x-badge>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('shop.product', [$store, $product->slug]) }}" target="_blank" class="btn btn-ghost btn-sm" title="Voir dans la boutique"><x-icon name="eye" class="size-4" /></a>
                                        <a href="{{ route('vendor.products.edit', [$store, $product]) }}" class="btn btn-ghost btn-sm" title="Modifier"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('vendor.products.destroy', [$store, $product]) }}" data-confirm="Supprimer « {{ $product->name }} » ? Il ne sera plus visible. Les commandes passées restent intactes.">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50" title="Supprimer"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        @endif
    </div>
@endsection
