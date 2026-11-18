<?php

namespace App\Enums;

enum StoreTheme: string
{
    case Moderne = 'moderne';
    case Elegant = 'elegant';
    case Vibrant = 'vibrant';
    case Minimal = 'minimal';

    public function label(): string
    {
        return match ($this) {
            self::Moderne => 'Moderne',
            self::Elegant => 'Élégant',
            self::Vibrant => 'Vibrant',
            self::Minimal => 'Minimal',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Moderne => 'Coins arrondis, fond clair, aéré.',
            self::Elegant => 'Typographie à empattements, tons sobres.',
            self::Vibrant => 'En-tête coloré, boutons très visibles.',
            self::Minimal => 'Épuré, angles droits, noir et blanc.',
        };
    }
}
