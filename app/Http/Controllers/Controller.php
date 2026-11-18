<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Layout à utiliser pour les pages communes (profil, notifications)
     * selon l'espace de l'utilisateur.
     *
     * @return array{layout: string, store: ?\App\Models\Store}
     */
    protected function areaLayout(User $user): array
    {
        if ($user->isStaff()) {
            return ['layout' => 'admin._layout', 'store' => null];
        }

        $store = $user->stores()->orderBy('name')->first();

        return ['layout' => $store ? 'seller._layout' : 'layouts.app', 'store' => $store];
    }
}
