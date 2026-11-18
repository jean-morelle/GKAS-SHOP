<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed
     *  - Formules d'abonnement initiales (modifiables ensuite dans l'administration).
     *  - Données de démonstration (hors production uniquement).
     *
     * L'administrateur n'est jamais créé ici : utilisez « php artisan gkas:create-admin ».
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        } else {
            $this->command?->warn('Production : les données de démonstration ne sont pas créées.');
        }
    }
}
