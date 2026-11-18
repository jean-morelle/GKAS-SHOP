<?php

namespace App\Enums;

/**
 * État d'accès calculé en temps réel à partir du statut administratif,
 * de la date de fin d'essai et de la date de fin d'abonnement.
 */
enum AccessState: string
{
    case Trial = 'trial';
    case Subscribed = 'subscribed';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Essai gratuit',
            self::Subscribed => 'Abonnement actif',
            self::Expired => 'Expirée',
            self::Suspended => 'Suspendue',
            self::Archived => 'Archivée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'blue',
            self::Subscribed => 'green',
            self::Expired => 'amber',
            self::Suspended => 'red',
            self::Archived => 'gray',
        };
    }

    public function isOperational(): bool
    {
        return in_array($this, [self::Trial, self::Subscribed], true);
    }
}
