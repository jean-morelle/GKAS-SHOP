<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->isStaff() || $user->canInStore($payment->store_id, 'billing');
    }

    /** Confirmation ou rejet : administrateur uniquement. */
    public function review(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $payment->isPending() && $user->canInStore($payment->store_id, 'billing');
    }
}
