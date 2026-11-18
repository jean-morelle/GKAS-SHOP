<?php

namespace App\Notifications;

use App\Models\Order;

class OrderStatusUpdated extends GkasNotification
{
    public function __construct(public Order $order) {}

    protected function content(object $notifiable): array
    {
        return [
            'title' => 'Commande '.$this->order->reference.' mise à jour',
            'message' => 'Nouveau statut : '.$this->order->status->label().'.',
            'url' => route('vendor.orders.show', [$this->order->store, $this->order]),
        ];
    }
}
