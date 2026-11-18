<?php

namespace App\Enums;

/**
 * Statut administratif d'une boutique, géré uniquement par GKAS-GROUP.
 * Indépendant de l'essai et de l'abonnement.
 */
enum StoreStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspendue',
            self::Archived => 'Archivée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'red',
            self::Archived => 'gray',
        };
    }
}
