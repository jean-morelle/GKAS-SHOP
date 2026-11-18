<?php

namespace App\Services;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Notifications\AccessExpiringSoon;
use App\Notifications\AccessExpired;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle des échéances d'essai et d'abonnement : rappels J-7 / J-3 / J-1
 * et notification d'expiration, sans doublon (table store_alerts).
 *
 * Appelé par la tâche planifiée « gkas:check-access » et, à la demande,
 * à l'ouverture du tableau de bord vendeur (si le planificateur ne tourne pas).
 * L'accès réel aux boutiques ne dépend jamais de ce contrôle : il est calculé
 * en direct à partir des dates (Store::accessState()).
 */
class SubscriptionMonitor
{
    public function __construct(private readonly StoreService $stores) {}

    /** @return array{reminders: int, expirations: int} */
    public function run(): array
    {
        $stats = ['reminders' => 0, 'expirations' => 0];

        Store::query()
            ->where('status', StoreStatus::Active)
            ->with('users')
            ->chunkById(100, function ($stores) use (&$stats) {
                foreach ($stores as $store) {
                    $result = $this->check($store);
                    $stats['reminders'] += $result['reminders'];
                    $stats['expirations'] += $result['expirations'];
                }
            });

        return $stats;
    }

    /** @return array{reminders: int, expirations: int} */
    public function check(Store $store): array
    {
        $stats = ['reminders' => 0, 'expirations' => 0];

        if ($store->status !== StoreStatus::Active) {
            return $stats;
        }

        $kind = $store->hasActiveSubscription() ? 'subscription' : 'trial';
        $endsAt = $kind === 'subscription' ? $store->subscription_ends_at : $store->trial_ends_at;

        if (! $endsAt) {
            return $stats;
        }

        if ($endsAt->isPast()) {
            // Expiration : seulement si aucun abonnement valide n'existe.
            $endKey = "{$kind}_expired:".$endsAt->format('Y-m-d-H-i');
            if ($this->claim($store, $endKey)) {
                if ($kind === 'trial') {
                    $this->stores->recordTrial($store, 'expired', $endsAt, $endsAt, null, 'Expiration automatique de l\'essai');
                }
                ActivityLogger::log("{$kind}.expired", "Accès expiré pour « {$store->name} »", $store);
                Notifier::storeMembers($store, new AccessExpired($store, $kind), ability: 'billing');
                $stats['expirations']++;
            }

            return $stats;
        }

        $daysLeft = (int) ceil(now()->diffInSeconds($endsAt) / 86400);

        $thresholds = config('gkas.reminder_days');
        sort($thresholds); // du plus proche (J-1) au plus lointain (J-7)

        foreach ($thresholds as $threshold) {
            if ($daysLeft <= $threshold) {
                // Une seule notification par seuil et par date de fin.
                $key = "{$kind}_reminder:{$threshold}:".$endsAt->format('Y-m-d-H-i');
                if ($this->claim($store, $key)) {
                    Notifier::storeMembers($store, new AccessExpiringSoon($store, $kind, $daysLeft), ability: 'billing');
                    $stats['reminders']++;
                }
                break; // seul le seuil le plus proche est envoyé
            }
        }

        return $stats;
    }

    /** Réserve atomiquement une clé d'alerte ; false si déjà envoyée. */
    private function claim(Store $store, string $key): bool
    {
        try {
            return DB::table('store_alerts')->insertOrIgnore([
                'store_id' => $store->id,
                'key' => $key,
                'created_at' => now(),
            ]) === 1;
        } catch (QueryException) {
            return false;
        }
    }
}
