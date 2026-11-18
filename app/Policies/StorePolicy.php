<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    /** Consultation (admin/commercial, ou membre de la boutique). */
    public function view(User $user, Store $store): bool
    {
        return $user->isStaff() || $user->belongsToStore($store);
    }

    /** Accès à l'espace vendeur de la boutique (tout membre, quel que soit son rôle). */
    public function manage(User $user, Store $store): bool
    {
        return $user->isVendor() && $user->belongsToStore($store);
    }

    // ---- Droits selon le rôle dans la boutique (propriétaire / gérant / employé) ----

    public function manageOrders(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'orders');
    }

    public function manageCatalog(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'catalog');
    }

    public function manageSettings(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'settings');
    }

    public function viewStats(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'stats');
    }

    public function export(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'export');
    }

    public function manageBilling(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'billing');
    }

    public function manageTeam(User $user, Store $store): bool
    {
        return $user->canInStore($store, 'team');
    }

    // ---- Espace GKAS ----

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /** Modification depuis l'espace GKAS. */
    public function update(User $user, Store $store): bool
    {
        return $user->isStaff();
    }

    /** Activation, suspension, archivage, propriétaire, domaine personnalisé. */
    public function changeStatus(User $user, Store $store): bool
    {
        return $user->isAdmin();
    }

    public function extendTrial(User $user, Store $store): bool
    {
        return $user->isAdmin();
    }

    public function recordPayment(User $user, Store $store): bool
    {
        return $user->isAdmin();
    }
}
