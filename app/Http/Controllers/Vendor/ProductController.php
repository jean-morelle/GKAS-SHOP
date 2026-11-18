<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Services\ImageUploader;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request, Store $store): View
    {
        $this->authorize('manageCatalog', $store);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('statut', '');
        $categoryId = $request->integer('categorie');

        $products = $store->products()->with(['mainImage', 'category'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($status === 'actif', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactif', fn ($q) => $q->where('is_active', false))
            ->when($status === 'rupture', fn ($q) => $q->where('stock_quantity', 0))
            ->when($status === 'vedette', fn ($q) => $q->where('is_featured', true))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('seller.products.index', [
            'store' => $store,
            'products' => $products,
            'categories' => $store->categories()->orderBy('name')->get(),
            'filters' => compact('search', 'status', 'categoryId'),
        ]);
    }

    public function create(Store $store): View
    {
        $this->authorize('manageCatalog', $store);

        return view('seller.products.form', [
            'store' => $store,
            'product' => new Product(['is_active' => true]),
            'categories' => $store->categories()->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request, Store $store): RedirectResponse
    {
        $product = $this->products->create($store, $request->safe()->except('images'), $request->file('images', []));

        return redirect()->route('vendor.products.index', $store)->with('success', "Produit « {$product->name} » ajouté.");
    }

    public function edit(Store $store, Product $product): View
    {
        $this->authorize('update', $product);

        return view('seller.products.form', [
            'store' => $store,
            'product' => $product->load('images'),
            'categories' => $store->categories()->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Store $store, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->safe()->except('images'), $request->file('images', []));

        return redirect()->route('vendor.products.edit', [$store, $product])->with('success', 'Produit mis à jour.');
    }

    public function toggle(Store $store, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->is_active = ! $product->is_active;
        $product->save();

        return back()->with('success', $product->is_active ? 'Produit activé : il est visible dans la boutique.' : 'Produit désactivé : il n\'est plus visible dans la boutique.');
    }

    /** Suppression logique : l'historique des commandes reste intact. */
    public function destroy(Store $store, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('vendor.products.index', $store)->with('success', "Produit « {$product->name} » supprimé.");
    }

    public function destroyImage(Store $store, Product $product, ProductImage $image, ImageUploader $uploader): RedirectResponse
    {
        $this->authorize('update', $product);

        $uploader->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image supprimée.');
    }
}
