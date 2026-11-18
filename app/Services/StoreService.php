<?php

namespace App\Services;

use App\Enums\StoreStatus;
use App\Models\Setting;
use App\Models\Store;
use App\Models\TrialHistory;
use App\Models\User;
use App\Notifications\StoreStatusChanged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreService
{
    /**
     * Crée une boutique, rattache son propriétaire et démarre l'essai gratuit.
     */
    public function create(array $data, User $owner, ?string $slug = null, bool $isDemo = false): Store
    {
        return DB::transaction(function () use ($data, $owner, $slug, $isDemo) {
            $store = new Store($data);
            $store->slug = $slug ?: $this->uniqueSlug($data['name']);
            $store->owner_id = $owner->id;
            $store->status = StoreStatus::Active;
            $store->is_demo = $isDemo;

            $days = Setting::trialDays();
            $start = now();
            $store->trial_starts_at = $start;
            $store->trial_ends_at = $start->copy()->addDays($days);
            $store->save();

            $store->users()->attach($owner->id, ['role' => 'owner']);

            $this->recordTrial($store, 'started', null, $store->trial_ends_at, $days, 'Essai gratuit initial');
            ActivityLogger::log('store.created', "Création de la boutique « {$store->name} » ({$store->slug})", $store, [
                'owner' => $owner->email,
                'trial_ends_at' => $store->trial_ends_at->toDateTimeString(),
            ]);

            return $store;
        });
    }

    public function update(Store $store, array $data, ?string $slug = null): Store
    {
        $store->fill($data);

        if ($slug !== null && $slug !== $store->slug) {
            ActivityLogger::log('store.slug_changed', "Identifiant public modifié : {$store->slug} → {$slug}", $store);
            $store->slug = $slug;
        }

        $store->save();

        return $store;
    }

    public function changeOwner(Store $store, User $owner): void
    {
        if ($store->owner_id === $owner->id) {
            return;
        }

        DB::transaction(function () use ($store, $owner) {
            $previous = $store->owner;
            $store->users()->syncWithoutDetaching([$owner->id => ['role' => 'owner']]);
            if ($previous) {
                $store->users()->detach($previous->id);
            }
            $store->owner_id = $owner->id;
            $store->save();

            ActivityLogger::log('store.owner_changed', "Nouveau propriétaire de « {$store->name} » : {$owner->email}", $store, [
                'previous' => $previous?->email,
            ]);
        });
    }

    /**
     * Change le statut administratif. Indépendant de l'abonnement :
     * un paiement ne réactive jamais une boutique suspendue.
     */
    public function changeStatus(Store $store, StoreStatus $status, ?string $reason = null): void
    {
        $previous = $store->status;

        if ($previous === $status) {
            return;
        }

        $store->status = $status;
        $store->suspension_reason = $status === StoreStatus::Active ? null : $reason;
        $store->suspended_at = $status === StoreStatus::Active ? null : now();
        $store->save();

        ActivityLogger::log('store.status_changed', "Boutique « {$store->name} » : {$previous->label()} → {$status->label()}", $store, [
            'from' => $previous->value,
            'to' => $status->value,
            'reason' => $reason,
        ]);

        Notifier::storeMembers($store, new StoreStatusChanged($store, $status, $reason));
    }

    /**
     * Prolonge exceptionnellement l'essai. L'autorisation est vérifiée par
     * la policy en amont ; l'auteur et le motif sont toujours enregistrés.
     */
    public function extendTrial(Store $store, int $days, string $reason, User $by): void
    {
        DB::transaction(function () use ($store, $days, $reason, $by) {
            $store = Store::query()->lockForUpdate()->findOrFail($store->id);
            $previous = $store->trial_ends_at;
            $base = $previous && $previous->isFuture() ? $previous->copy() : now();
            $store->trial_ends_at = $base->addDays($days);
            $store->save();

            $this->recordTrial($store, 'extended', $previous, $store->trial_ends_at, $days, $reason, $by);
            ActivityLogger::log('trial.extended', "Essai de « {$store->name} » prolongé de {$days} jour(s)", $store, [
                'previous_ends_at' => $previous?->toDateTimeString(),
                'new_ends_at' => $store->trial_ends_at->toDateTimeString(),
                'reason' => $reason,
            ]);
        });
    }

    public function recordTrial(Store $store, string $action, ?Carbon $previous, ?Carbon $new, ?int $days, ?string $reason, ?User $by = null): void
    {
        $history = new TrialHistory;
        $history->forceFill([
            'store_id' => $store->id,
            'action' => $action,
            'previous_ends_at' => $previous,
            'new_ends_at' => $new,
            'days' => $days,
            'reason' => $reason,
            'performed_by' => $by?->id ?? auth()->id(),
        ])->save();
    }

    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'boutique';
        $base = Str::limit($base, 50, '');

        if (in_array($base, config('gkas.reserved_slugs'), true)) {
            $base .= '-shop';
        }

        $slug = $base;
        $i = 2;

        while (Store::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
