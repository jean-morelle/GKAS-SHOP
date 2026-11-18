<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\StoreTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreAppearanceRequest;
use App\Http\Requests\Vendor\StoreSettingsRequest;
use App\Models\Store;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StoreSettingsController extends Controller
{
    public function edit(Store $store): View
    {
        $this->authorize('manageSettings', $store);

        return view('seller.settings', ['store' => $store]);
    }

    public function update(StoreSettingsRequest $request, Store $store): RedirectResponse
    {
        $store->fill($request->validated())->save();

        return back()->with('success', 'Informations de la boutique enregistrées.');
    }

    public function appearance(Store $store): View
    {
        $this->authorize('manageSettings', $store);

        return view('seller.appearance', ['store' => $store, 'themes' => StoreTheme::cases()]);
    }

    public function updateAppearance(StoreAppearanceRequest $request, Store $store, ImageUploader $uploader): RedirectResponse
    {
        $store->fill($request->safe()->only(['theme', 'primary_color', 'is_published']));

        foreach (['logo' => 'logo_path', 'banner' => 'banner_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $store->{$column} = $uploader->replace($store->{$column}, $request->file($input), "stores/{$store->id}/branding");
            } elseif ($request->boolean("remove_{$input}")) {
                $uploader->delete($store->{$column});
                $store->{$column} = null;
            }
        }

        $store->save();

        return back()->with('success', 'Apparence enregistrée. Utilisez « Prévisualiser » pour voir le résultat.');
    }
}
