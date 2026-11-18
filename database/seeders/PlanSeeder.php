<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Formules initiales. Les prix sont des valeurs de départ à ajuster par
 * GKAS-GROUP dans Administration > Formules (rien n'est figé dans le code).
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'mensuel', 'name' => 'Mensuel', 'description' => 'Idéal pour démarrer', 'price' => 5000, 'duration_days' => 30, 'position' => 1,
                'features' => ['Boutique en ligne personnalisée', 'Produits illimités', 'Commandes illimitées', 'Commande via WhatsApp', 'Assistance GKAS-GROUP']],
            ['slug' => 'trimestriel', 'name' => 'Trimestriel', 'description' => '3 mois, prix avantageux', 'price' => 13500, 'duration_days' => 90, 'position' => 2,
                'features' => ['Tout le contenu Mensuel', 'Mise en avant de produits', 'Accompagnement pour vos visuels']],
            ['slug' => 'annuel', 'name' => 'Annuel', 'description' => 'Pour les vendeurs installés', 'price' => 48000, 'duration_days' => 365, 'position' => 3,
                'features' => ['Tout le contenu Trimestriel', 'Prioritaire sur l\'assistance', 'Bilan d\'activité trimestriel']],
        ];

        foreach ($plans as $data) {
            $plan = Plan::withTrashed()->firstOrNew(['slug' => $data['slug']]);
            if (! $plan->exists) {
                $plan->fill($data);
                $plan->slug = $data['slug'];
                $plan->is_active = true;
                $plan->save();
            }
        }
    }
}
