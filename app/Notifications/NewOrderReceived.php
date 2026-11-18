<?php

namespace App\Notifications;

use App\Models\Order;

class NewOrderReceived extends GkasNotification
{
    public function __construct(public Order $order) {}

    protected function content(object $notifiable): array
    {
        $store = $this->order->store;

        return [
            'title' => 'Nouvelle commande '.$this->order->reference,
            'message' => "{$this->order->customer_name} a commandé pour ".fcfa($this->order->total)." sur « {$store->name} ».",
            'url' => route('vendor.orders.show', [$store, $this->order]),
            'level' => 'success',
        ];
    }
}
