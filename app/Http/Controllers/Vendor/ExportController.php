<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des données du vendeur (disponible même en accès limité).
 */
class ExportController extends Controller
{
    public function export(Store $store, string $type): StreamedResponse
    {
        $this->authorize('export', $store);

        $filename = "{$store->slug}-{$type}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($store, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

            if ($type === 'produits') {
                fputcsv($out, ['Nom', 'Catégorie', 'Prix', 'Prix promo', 'Stock', 'Actif', 'Vedette', 'Créé le'], ';');
                $store->products()->with('category')->orderBy('name')->chunk(200, function ($products) use ($out) {
                    foreach ($products as $p) {
                        fputcsv($out, [$p->name, $p->category?->name, $p->price, $p->sale_price, $p->stock_quantity, $p->is_active ? 'oui' : 'non', $p->is_featured ? 'oui' : 'non', $p->created_at->format('d/m/Y')], ';');
                    }
                });
            } else {
                fputcsv($out, ['Référence', 'Date', 'Statut', 'Client', 'Téléphone', 'Quartier', 'Adresse', 'Articles', 'Sous-total', 'Zone de livraison', 'Frais de livraison', 'Total', 'Payée'], ';');
                $store->orders()->with('items')->latest()->chunk(200, function ($orders) use ($out) {
                    foreach ($orders as $o) {
                        $items = $o->items->map(fn ($i) => "{$i->quantity} x {$i->product_name}")->implode(' | ');
                        fputcsv($out, [$o->reference, $o->created_at->format('d/m/Y H:i'), $o->status->label(), $o->customer_name, $o->customer_phone, $o->customer_area, $o->delivery_address, $items, $o->subtotal, $o->delivery_zone_name, $o->delivery_fee, $o->total, $o->paid_at ? 'oui' : 'non'], ';');
                    }
                });
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
