@extends('seller._layout', ['title' => 'Catégories'])

@section('content')
    <x-page-header title="Catégories" subtitle="Regroupez vos produits pour aider vos clients à s'y retrouver." />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('vendor.categories.store', $store) }}" class="card card-body h-fit space-y-4">
            @csrf
            <h2 class="font-semibold text-slate-900">Nouvelle catégorie</h2>
            <x-form.input name="name" label="Nom" required maxlength="100" placeholder="ex. Robes, Sacs, Chaussures…" />
            <x-form.input name="position" type="number" label="Ordre d'affichage" min="0" help="Facultatif. Les plus petits nombres s'affichent en premier." />
            <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> Créer</button>
        </form>

        <div class="card overflow-hidden lg:col-span-2">
            @forelse ($categories as $category)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center">
                    <form method="POST" action="{{ route('vendor.categories.update', [$store, $category]) }}" class="flex flex-1 flex-wrap items-center gap-2">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $category->name }}" required maxlength="100" class="form-input min-w-40 flex-1" aria-label="Nom de la catégorie">
                        <input name="position" type="number" value="{{ $category->position }}" min="0" class="form-input w-20" aria-label="Ordre">
                        <button class="btn btn-secondary btn-sm">Renommer</button>
                    </form>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500">{{ $category->products_count }} produit(s)</span>
                        <form method="POST" action="{{ route('vendor.categories.destroy', [$store, $category]) }}" data-confirm="Supprimer la catégorie « {{ $category->name }} » ?">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50" @disabled($category->products_count > 0) title="{{ $category->products_count ? 'Retirez d\'abord les produits de cette catégorie' : 'Supprimer' }}"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </div>
            @empty
                <x-empty icon="tag" title="Aucune catégorie" text="Créez des catégories comme « Robes », « Sacs » ou « Accessoires »." />
            @endforelse
        </div>
    </div>
@endsection
