<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Envoi des notifications sans jamais bloquer l'action métier :
 * une erreur de messagerie est journalisée, pas propagée.
 */
class Notifier
{
    public static function send(iterable|User $users, Notification $notification): void
    {
        try {
            NotificationFacade::send($users, $notification);
        } catch (Throwable $e) {
            Log::warning('Notification non envoyée : '.$e->getMessage(), ['notification' => $notification::class]);
        }
    }

    /**
     * Notifie les membres de la boutique ; avec $ability, seulement ceux dont le rôle
     * a ce droit (ex. un employé ne reçoit pas les rappels d'abonnement).
     */
    public static function storeMembers(Store $store, Notification $notification, ?User $except = null, ?string $ability = null): void
    {
        $users = $store->users()->where('is_active', true)
            ->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->get()
            ->when($ability, fn ($users) => $users->filter(
                fn (User $u) => \App\Enums\StoreRole::tryFrom($u->pivot->role)?->can($ability)
            ));

        if ($users->isNotEmpty()) {
            self::send($users, $notification);
        }
    }

    public static function admins(Notification $notification): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->where('is_active', true)->get();

        if ($admins->isNotEmpty()) {
            self::send($admins, $notification);
        }
    }

    /** Canaux à utiliser : base de données toujours, e-mail si activé. */
    public static function channels(object $notifiable): array
    {
        $channels = ['database'];

        if (config('gkas.mail_notifications') && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
