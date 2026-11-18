<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur GKAS',
            self::Sales => 'Commercial GKAS',
            self::Vendor => 'Vendeur',
        };
    }
}
