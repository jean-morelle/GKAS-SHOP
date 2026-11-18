<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Shipping = 'shipping';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::PendingConfirmation => 'En attente de confirmation',
            self::Confirmed => 'Confirmée',
            self::Preparing => 'En préparation',
            self::Ready => 'Prête pour livraison',
            self::Shipping => 'En livraison',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::PendingConfirmation => 'amber',
            self::Confirmed => 'indigo',
            self::Preparing => 'purple',
            self::Ready => 'cyan',
            self::Shipping => 'sky',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * Transitions autorisées depuis ce statut.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::PendingConfirmation, self::Confirmed, self::Cancelled],
            self::PendingConfirmation => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Ready, self::Cancelled],
            self::Preparing => [self::Ready, self::Cancelled],
            self::Ready => [self::Shipping, self::Delivered, self::Cancelled],
            self::Shipping => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Statuts « à traiter » par le vendeur. */
    public static function toProcess(): array
    {
        return [self::New->value, self::PendingConfirmation->value];
    }

    /** Statuts confirmés mais pas encore livrés. */
    public static function inProgress(): array
    {
        return [self::Confirmed->value, self::Preparing->value, self::Ready->value, self::Shipping->value];
    }
}
