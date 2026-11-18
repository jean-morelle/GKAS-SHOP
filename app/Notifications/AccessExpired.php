<?php

namespace App\Notifications;

use App\Models\Store;

class AccessExpired extends GkasNotification
{
    public function __construct(public Store $store, public string $kind) {}

    protected function content(object $notifiable): array
    {
        $what = $this->kind === 'trial' ? 'Votre essai gratuit' : 'Votre abonnement';

        return [
            'title' => "{$what} est terminé",
            'message' => "La boutique « {$this->store->name} » n'accepte plus de nouvelles commandes. Vos données sont conservées : souscrivez une formule pour la réactiver.",
            'url' => route('vendor.subscription.show', $this->store),
            'level' => 'danger',
        ];
    }
}
