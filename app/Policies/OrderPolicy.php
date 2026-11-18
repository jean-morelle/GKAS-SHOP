<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isStaff() || $user->canInStore($order->store_id, 'orders');
    }

    /** Seul un membre de la boutique ayant le droit « commandes » les traite. */
    public function update(User $user, Order $order): bool
    {
        return $user->canInStore($order->store_id, 'orders');
    }
}
