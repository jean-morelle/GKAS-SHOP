<?php

namespace App\Enums;

/**
 * Rôle d'un utilisateur au sein d'une boutique (table store_user).
 */
enum StoreRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::Manager => 'Gérant',
            self::Staff => 'Employé',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Tous les droits, y compris l\'abonnement et l\'équipe.',
            self::Manager => 'Produits, commandes, livraison, informations et apparence de la boutique, statistiques et export. Pas d\'abonnement ni d\'équipe.',
            self::Staff => 'Traitement des commandes uniquement (consultation, statut, notes).',
        };
    }

    /**
     * Droits : orders, catalog, settings, stats, export, billing, team.
     */
    public function can(string $ability): bool
    {
        return in_array($ability, match ($this) {
            self::Owner => ['orders', 'catalog', 'settings', 'stats', 'export', 'billing', 'team'],
            self::Manager => ['orders', 'catalog', 'settings', 'stats', 'export'],
            self::Staff => ['orders'],
        }, true);
    }

    /** Rôles attribuables depuis la gestion d'équipe. */
    public static function assignable(): array
    {
        return [self::Manager, self::Staff];
    }
}
