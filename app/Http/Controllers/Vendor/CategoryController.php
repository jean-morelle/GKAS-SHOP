<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CategoryRequest;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Store $store): View
    {
        $this->authorize('manageCatalog', $store);

        return view('seller.categories.index', [
            'store' => $store,
            'categories' => $store->categories()->withCount('products')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function store(CategoryRequest $request, Store $store): RedirectResponse
    {
        $category = new Category($request->validated());
        $category->store_id = $store->id;
        $category->slug = $this->uniqueSlug($store, $category->name);
        $category->save();

        return back()->with('success', "Catégorie « {$category->name} » créée.");
    }

    public function update(CategoryRequest $request, Store $store, Category $category): RedirectResponse
    {
        $category->fill($request->validated());
        if ($category->isDirty('name')) {
            $category->slug = $this->uniqueSlug($store, $category->name, $category->id);
        }
        $category->save();

        return back()->with('success', 'Catégorie modifiée.');
    }

    public function destroy(Store $store, Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->isUsed()) {
            return back()->with('error', "Impossible de supprimer « {$category->name} » : des produits y sont encore rattachés.");
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }

    private function uniqueSlug(Store $store, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'categorie';
        $slug = $base;
        $i = 2;

        while ($store->categories()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
