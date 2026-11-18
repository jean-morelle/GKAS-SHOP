<?php

namespace App\Notifications;

use App\Models\Store;

class AccessExpiringSoon extends GkasNotification
{
    public function __construct(public Store $store, public string $kind, public int $daysLeft) {}

    protected function content(object $notifiable): array
    {
        $what = $this->kind === 'trial' ? 'Votre essai gratuit' : 'Votre abonnement';
        $days = $this->daysLeft.' jour'.($this->daysLeft > 1 ? 's' : '');

        return [
            'title' => "{$what} se termine dans {$days}",
            'message' => "{$what} pour « {$this->store->name} » se termine le {$this->store->accessEndsAt()?->format('d/m/Y à H:i')}. Choisissez une formule pour continuer à recevoir des commandes.",
            'url' => route('vendor.subscription.show', $this->store),
            'level' => 'warning',
        ];
    }
}
