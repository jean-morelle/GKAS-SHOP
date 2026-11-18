<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Zones et frais de livraison d'une boutique.
 */
class DeliveryController extends Controller
{
    public function index(Store $store): View
    {
        $this->authorize('manageSettings', $store);

        return view('seller.delivery', [
            'store' => $store,
            'zones' => $store->deliveryZones()->orderBy('position')->orderBy('fee')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('manageSettings', $store);

        $zone = new DeliveryZone($this->validated($request));
        $zone->store_id = $store->id;
        $zone->save();

        return back()->with('success', "Zone « {$zone->name} » ajoutée.");
    }

    public function update(Request $request, Store $store, DeliveryZone $deliveryZone): RedirectResponse
    {
        $this->authorize('manageSettings', $store);

        $deliveryZone->fill($this->validated($request))->save();

        return back()->with('success', 'Zone de livraison mise à jour.');
    }

    /** Suppression sans perte : les commandes conservent le nom et les frais de la zone. */
    public function destroy(Store $store, DeliveryZone $deliveryZone): RedirectResponse
    {
        $this->authorize('manageSettings', $store);

        $deliveryZone->delete();

        return back()->with('success', "Zone « {$deliveryZone->name} » supprimée.");
    }

    public function options(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('manageSettings', $store);

        $data = $request->validate([
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ], [], ['free_delivery_threshold' => 'montant de livraison offerte']);

        $store->free_delivery_threshold = $data['free_delivery_threshold'] ?? null;
        $store->save();

        return back()->with('success', 'Options de livraison enregistrées.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ], [], ['name' => 'nom de la zone', 'fee' => 'frais de livraison']);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['position'] ??= 0;

        return $data;
    }
}
