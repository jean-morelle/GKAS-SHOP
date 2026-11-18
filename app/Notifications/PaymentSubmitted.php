<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentSubmitted extends GkasNotification
{
    public function __construct(public Payment $payment) {}

    protected function content(object $notifiable): array
    {
        return [
            'title' => 'Paiement à vérifier',
            'message' => "« {$this->payment->store->name} » a soumis un paiement de ".fcfa($this->payment->amount)." (réf. {$this->payment->transaction_reference}).",
            'url' => route('admin.payments.show', $this->payment),
            'level' => 'warning',
        ];
    }
}
