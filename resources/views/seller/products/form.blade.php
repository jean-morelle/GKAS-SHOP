@extends('seller._layout', ['title' => $product->exists ? 'Modifier le produit' : 'Nouveau produit'])

@section('content')
    <x-page-header :title="$product->exists ? 'Modifier « '.$product->name.' »' : 'Nouveau produit'" :back="route('vendor.products.index', $store)" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $product->exists ? route('vendor.products.update', [$store, $product]) : route('vendor.products.store', $store) }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body space-y-4">
                <x-form.input name="name" label="Nom du produit" :value="$product->name" required maxlength="150" placeholder="ex. Robe wax longue" />
                <x-form.textarea name="description" label="Description" :value="$product->description" rows="5" help="Matière, tailles disponibles, couleurs, conseils…" />
            </div>

            <div class="card card-body">
                <h2 class="font-semibold text-slate-900">Photos</h2>
                <p class="mt-1 text-sm text-slate-500">JPG, PNG ou WEBP, 2 Mo maximum par image, jusqu'à {{ config('gkas.uploads.max_product_images') }} images. La première image est l'image principale.</p>

                @if ($product->exists && $product->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($product->images as $image)
                            <div class="group relative aspect-square overflow-hidden rounded-lg border border-slate-200">
                                <img src="{{ $image->url() }}" alt="" class="size-full object-cover">
                                @if ($loop->first)<span class="absolute top-1 left-1 rounded bg-slate-900/70 px-1.5 text-[10px] text-white">Principale</span>@endif
                                <button type="submit" form="delete-image-{{ $image->id }}" class="absolute top-1 right-1 rounded-full bg-white/90 p-1 text-red-600 shadow hover:bg-white" title="Supprimer cette image">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <label class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 px-4 py-8 text-center hover:border-brand-400 hover:bg-brand-50/30">
                    <x-icon name="photo" class="size-10 text-slate-400" />
                    <span class="mt-2 text-sm font-medium text-brand-700">Choisir des photos</span>
                    <span class="text-xs text-slate-500">ou prendre une photo avec votre téléphone</span>
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-3 block w-full max-w-xs text-xs text-slate-500">
                </label>
                @error('images')<p class="form-error">{{ $message }}</p>@enderror
                @foreach ($errors->get('images.*') as $messages)
                    @foreach ($messages as $message)<p class="form-error">{{ $message }}</p>@endforeach
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-body space-y-4">
                <x-form.input name="price" type="number" label="Prix (FCFA)" :value="$product->price !== null ? (int) $product->price : null" required min="0" step="1" inputmode="numeric" />
                <x-form.input name="sale_price" type="number" label="Prix promotionnel (FCFA)" :value="$product->sale_price !== null ? (int) $product->sale_price : null" min="0" step="1" inputmode="numeric" help="Facultatif. Laissez vide s'il n'y a pas de promotion." />
                <x-form.input name="stock_quantity" type="number" label="Quantité disponible" :value="$product->stock_quantity" min="0" step="1" inputmode="numeric" help="Facultatif. Laissez vide pour ne pas suivre le stock." />
                <div>
                    <x-form.select name="category_id" label="Catégorie" :options="$categories->pluck('name', 'id')" :value="$product->category_id" placeholder="— Sans catégorie —" />
                    <a href="{{ route('vendor.categories.index', $store) }}" class="mt-1 inline-block text-xs text-brand-700 hover:underline">Gérer les catégories</a>
                </div>
            </div>
            <div class="card card-body space-y-4">
                <x-form.checkbox name="is_active" label="Visible dans la boutique" :checked="$product->is_active ?? true" help="Décochez pour masquer le produit sans le supprimer." />
                <x-form.checkbox name="is_featured" label="Mettre en avant" :checked="$product->is_featured" help="Affiché dans la section « À la une »." />
            </div>
            <button class="btn btn-primary w-full">{{ $product->exists ? 'Enregistrer les modifications' : 'Ajouter le produit' }}</button>
        </div>
    </form>

    @if ($product->exists)
        @foreach ($product->images as $image)
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('vendor.products.images.destroy', [$store, $product, $image]) }}" data-confirm="Supprimer cette image ?" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endif
@endsection
