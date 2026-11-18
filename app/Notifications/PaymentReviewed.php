<?php

namespace App\Notifications;

use App\Enums\PaymentStatus;
use App\Models\Payment;

class PaymentReviewed extends GkasNotification
{
    public function __construct(public Payment $payment) {}

    protected function content(object $notifiable): array
    {
        $store = $this->payment->store;
        $confirmed = $this->payment->status === PaymentStatus::Confirmed;

        return [
            'title' => $confirmed ? 'Paiement confirmé' : 'Paiement refusé',
            'message' => $confirmed
                ? "Votre abonnement pour « {$store->name} » est actif jusqu'au {$store->subscription_ends_at?->format('d/m/Y')}. Merci !"
                : "Votre paiement (réf. {$this->payment->transaction_reference}) a été refusé : {$this->payment->review_note}",
            'url' => route('vendor.subscription.show', $store),
            'level' => $confirmed ? 'success' : 'danger',
        ];
    }
}
