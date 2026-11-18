<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\StoreTheme;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StoreService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DONNÉES DE DÉMONSTRATION — clairement identifiées (is_demo = true,
 * e-mails en @gkasshop.local, mention « démo » dans les noms).
 * Refusé en production. Idempotent : ne recrée pas une boutique existante.
 */
class DemoSeeder extends Seeder
{
    public function run(StoreService $stores, OrderService $orders): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Le DemoSeeder ne doit jamais être exécuté en production.');
        }

        $password = config('gkas.demo_password') ?: Str::password(14, symbols: false);
        $created = [];

        foreach ($this->catalog() as $def) {
            if (Store::withTrashed()->where('slug', $def['slug'])->exists()) {
                $this->command?->info("Boutique de démonstration « {$def['slug']} » déjà présente : ignorée.");
                continue;
            }

            $vendor = User::query()->firstOrNew(['email' => $def['vendor_email']]);
            if (! $vendor->exists) {
                $vendor->fill(['name' => $def['vendor_name'], 'phone' => $def['whatsapp'], 'password' => $password]);
                $vendor->role = UserRole::Vendor;
                $vendor->is_active = true;
                $vendor->must_change_password = false;
                $vendor->email_verified_at = now();
                $vendor->save();
                $created[] = [$vendor->email, $password];
            }

            $store = $stores->create([
                'name' => $def['name'],
                'description' => $def['description'],
                'primary_color' => $def['color'],
                'theme' => $def['theme'],
                'whatsapp' => $def['whatsapp'],
                'phone' => $def['whatsapp'],
                'address' => 'Lomé, Togo',
                'delivery_zone' => 'Lomé et environs, 24 à 48 h',
                'opening_hours' => 'Lundi – samedi, 8h – 19h',
                'order_conditions' => "Boutique de démonstration GKAS Shop.\nPaiement à la livraison ou par Mobile Money. Frais de livraison confirmés par le vendeur.",
                'is_published' => true,
            ], $vendor, $def['slug'], isDemo: true);

            $dir = "demo/{$store->slug}";
            Storage::disk('public')->put("{$dir}/logo.svg", DemoIllustrations::logo($def['initials'], $def['color']));
            Storage::disk('public')->put("{$dir}/banner.svg", DemoIllustrations::banner($def['color'], $def['color2']));
            $store->logo_path = "{$dir}/logo.svg";
            $store->banner_path = "{$dir}/banner.svg";
            $store->save();

            $categories = [];
            foreach ($def['categories'] as $i => $name) {
                $category = new Category(['name' => $name, 'position' => $i]);
                $category->store_id = $store->id;
                $category->slug = Str::slug($name);
                $category->save();
                $categories[$name] = $category;
            }

            $products = [];
            foreach ($def['products'] as $i => [$name, $category, $price, $sale, $stock, $shape, $c1, $c2, $featured, $description]) {
                $product = $store->products()->make([
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'sale_price' => $sale,
                    'stock_quantity' => $stock,
                    'is_active' => true,
                    'is_featured' => $featured,
                    'category_id' => $categories[$category]->id,
                ]);
                $product->slug = Str::slug($name);
                $product->save();

                $file = "{$dir}/products/".Str::slug($name).'.svg';
                Storage::disk('public')->put($file, DemoIllustrations::product($shape, $c1, $c2, $name));
                $product->images()->create(['path' => $file, 'position' => 1]);
                $products[] = $product;
            }

            $this->seedOrders($orders, $store, $products, $vendor);
            $this->command?->info("Boutique de démonstration créée : /{$store->slug}");
        }

        $this->seedDeliveryZones();

        if ($created) {
            $this->command?->warn('Comptes vendeurs de démonstration (local uniquement) :');
            $this->command?->table(['E-mail', 'Mot de passe'], $created);
            if (! config('gkas.demo_password')) {
                $this->command?->line('Mot de passe généré aléatoirement. Définissez GKAS_DEMO_PASSWORD dans .env pour le fixer.');
            }
        }
    }

    /** Zones de livraison des boutiques de démo (ajoutées seulement si la boutique n'en a pas). */
    private function seedDeliveryZones(): void
    {
        $zones = [
            ['Lomé centre', 'Bè, Tokoin, Nyékonakpoè… sous 24 h', 1000],
            ['Lomé périphérie', 'Agoè, Adidogomé, Baguida… sous 48 h', 1500],
            ['Retrait en boutique', 'Gratuit, sur rendez-vous', 0],
        ];

        foreach (['bella-fashion' => 50000, 'kente-store' => null] as $slug => $threshold) {
            $store = Store::where('slug', $slug)->where('is_demo', true)->first();

            if (! $store || $store->deliveryZones()->exists()) {
                continue;
            }

            foreach ($zones as $i => [$name, $description, $fee]) {
                $zone = $store->deliveryZones()->make(['name' => $name, 'description' => $description, 'fee' => $fee, 'is_active' => true, 'position' => $i]);
                $zone->save();
            }

            $store->free_delivery_threshold = $threshold;
            $store->save();
            $this->command?->info("Zones de livraison de démonstration ajoutées : /{$slug}");
        }
    }

    /** Commandes de démonstration créées par le vrai circuit (OrderService), marquées is_demo. */
    private function seedOrders(OrderService $orders, Store $store, array $products, User $vendor): void
    {
        $clients = [
            ['name' => 'Client Démo 1', 'phone' => '+228 90 11 22 33', 'area' => 'Bè', 'address' => 'Près du marché, portail bleu', 'notes' => 'Commande de démonstration'],
            ['name' => 'Client Démo 2', 'phone' => '+228 91 22 33 44', 'area' => 'Tokoin', 'address' => 'Derrière la pharmacie', 'notes' => null],
            ['name' => 'Client Démo 3', 'phone' => '+228 92 33 44 55', 'area' => 'Agoè', 'address' => 'Carrefour, 2e rue à gauche', 'notes' => 'Appeler avant de passer'],
            ['name' => 'Client Démo 4', 'phone' => '+228 93 44 55 66', 'area' => 'Adidogomé', 'address' => 'Face à l\'école primaire', 'notes' => null],
        ];
        $paths = [
            [],
            [OrderStatus::Confirmed],
            [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Delivered],
            [OrderStatus::Cancelled],
        ];

        foreach ($clients as $i => $client) {
            $items = [$products[$i % count($products)]->id => 1, $products[($i + 2) % count($products)]->id => $i % 2 + 1];
            $order = $orders->place($store, $items, $client, $i === 1 ? 'whatsapp' : 'web', isDemo: true);

            foreach ($paths[$i] as $status) {
                $order = $orders->changeStatus($order, $status, null, $vendor);
            }
            if ($i === 2) {
                $order->forceFill(['paid_at' => now()])->save();
            }
        }
    }

    private function catalog(): array
    {
        return [
            [
                'slug' => 'bella-fashion',
                'name' => 'Bella Fashion (démo)',
                'initials' => 'BF',
                'description' => 'Mode féminine et tenues en pagne pour toutes les occasions. Boutique de démonstration GKAS Shop.',
                'color' => '#db2777', 'color2' => '#7c3aed',
                'theme' => StoreTheme::Moderne,
                'whatsapp' => '+228 90 00 00 01',
                'vendor_email' => 'demo.mode@gkasshop.local',
                'vendor_name' => 'Vendeuse Démo Mode',
                'categories' => ['Robes', 'Hauts', 'Pantalons', 'Tenues traditionnelles'],
                'products' => [
                    ['Robe wax longue', 'Robes', 15000, 12500, 8, 'dress', '#f97316', '#db2777', true, "Robe longue en tissu wax 100 % coton.\nTailles : S, M, L, XL. Précisez votre taille dans les notes."],
                    ['Robe cocktail satinée', 'Robes', 18000, null, 5, 'dress', '#7c3aed', '#db2777', true, 'Robe mi-longue satinée, idéale pour les cérémonies.'],
                    ['Robe d\'été fleurie', 'Robes', 9500, null, null, 'dress', '#facc15', '#f97316', false, 'Robe légère et fluide pour les journées chaudes.'],
                    ['Blouse en pagne', 'Hauts', 7000, 6000, 12, 'shirt', '#059669', '#0891b2', false, 'Blouse cintrée en pagne, manches courtes.'],
                    ['T-shirt brodé', 'Hauts', 5000, null, 20, 'tshirt', '#0ea5e9', '#6366f1', false, 'T-shirt en coton avec broderie faite main.'],
                    ['Pantalon palazzo', 'Pantalons', 8500, null, 6, 'pants', '#334155', '#7c3aed', false, 'Pantalon large et confortable, taille élastique.'],
                    ['Ensemble boubou brodé', 'Tenues traditionnelles', 25000, 22000, 4, 'boubou', '#b45309', '#db2777', true, 'Boubou brodé avec foulard assorti. Taille unique.'],
                    ['Kaba moderne', 'Tenues traditionnelles', 20000, null, 0, 'boubou', '#be123c', '#f59e0b', false, 'Kaba coupe moderne. Actuellement en rupture (exemple d\'affichage).'],
                ],
            ],
            [
                'slug' => 'kente-store',
                'name' => 'Kente Store (démo)',
                'initials' => 'KS',
                'description' => 'Accessoires tendance : sacs, montres, lunettes et bijoux. Boutique de démonstration GKAS Shop.',
                'color' => '#0f766e', 'color2' => '#1e3a8a',
                'theme' => StoreTheme::Elegant,
                'whatsapp' => '+228 90 00 00 02',
                'vendor_email' => 'demo.accessoires@gkasshop.local',
                'vendor_name' => 'Vendeur Démo Accessoires',
                'categories' => ['Sacs', 'Montres', 'Lunettes', 'Bijoux', 'Chaussures'],
                'products' => [
                    ['Sac à main en cuir', 'Sacs', 22000, 18500, 7, 'bag', '#92400e', '#78350f', true, 'Sac à main en cuir véritable, fermeture zippée, bandoulière amovible.'],
                    ['Sac kente tressé', 'Sacs', 12000, null, 10, 'bag', '#ca8a04', '#15803d', true, 'Sac en tissu kente tressé à la main.'],
                    ['Montre dorée classique', 'Montres', 16000, null, 5, 'watch', '#a16207', '#1e3a8a', true, 'Montre à quartz, bracelet acier doré, étanche 30 m.'],
                    ['Montre sport', 'Montres', 9000, 7500, 15, 'watch', '#0f766e', '#0891b2', false, 'Montre digitale avec chronomètre et alarme.'],
                    ['Lunettes de soleil rondes', 'Lunettes', 6500, null, 25, 'glasses', '#1e293b', '#0f766e', false, 'Protection UV400, monture légère.'],
                    ['Collier perles africaines', 'Bijoux', 4500, null, 30, 'necklace', '#b91c1c', '#ca8a04', false, 'Collier en perles de verre, fabrication artisanale.'],
                    ['Boucles d\'oreilles dorées', 'Bijoux', 3500, 3000, null, 'earrings', '#ca8a04', '#be123c', false, 'Boucles légères, plaqué or.'],
                    ['Sandales en cuir', 'Chaussures', 11000, null, 9, 'shoe', '#78350f', '#0f766e', false, 'Sandales confortables, semelle antidérapante. Pointures 36 à 45.'],
                ],
            ],
        ];
    }
}
