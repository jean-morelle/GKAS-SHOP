<?php

namespace App\Notifications;

use App\Enums\StoreStatus;
use App\Models\Store;

class StoreStatusChanged extends GkasNotification
{
    public function __construct(public Store $store, public StoreStatus $status, public ?string $reason) {}

    protected function content(object $notifiable): array
    {
        return [
            'title' => "Boutique {$this->status->label()}",
            'message' => "La boutique « {$this->store->name} » est désormais : {$this->status->label()}."
                .($this->reason ? " Motif : {$this->reason}" : ''),
            'url' => route('vendor.dashboard', $this->store),
            'level' => $this->status === StoreStatus::Active ? 'success' : 'danger',
        ];
    }
}
