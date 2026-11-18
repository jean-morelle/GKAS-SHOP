<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, Store $store): View
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('categorie', '');
        $sort = (string) $request->query('tri', '');

        $categories = $store->categories()->orderBy('position')->orderBy('name')
            ->whereHas('products', fn ($q) => $q->visible())->get();
        $category = $categorySlug ? $categories->firstWhere('slug', $categorySlug) : null;

        $products = $store->products()->visible()->with('mainImage')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('description', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($request->boolean('promo'), fn ($q) => $q->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price'))
            ->when($sort === 'prix-asc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($sort === 'prix-desc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when(! in_array($sort, ['prix-asc', 'prix-desc'], true), fn ($q) => $q->orderByDesc('is_featured')->latest('id'))
            ->paginate(12)
            ->withQueryString();

        $featured = ($search === '' && ! $category && ! $request->has('page'))
            ? $store->products()->visible()->where('is_featured', true)->with('mainImage')->latest('id')->take(4)->get()
            : collect();

        return view('shop.index', compact('store', 'products', 'categories', 'category', 'search', 'sort', 'featured'));
    }

    public function show(Store $store, Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('images', 'category');
        $related = $store->products()->visible()->with('mainImage')
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->inRandomOrder()->take(4)->get();

        return view('shop.product', compact('store', 'product', 'related'));
    }
}
