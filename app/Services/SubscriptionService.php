<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use App\Notifications\PaymentSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Paiements manuels et abonnements. Un paiement n'active l'abonnement
 * qu'après vérification par un administrateur habilité.
 */
class SubscriptionService
{
    public function submitPayment(
        Store $store,
        Plan $plan,
        PaymentMethod $method,
        string $reference,
        User $by,
        ?UploadedFile $proof = null,
        ?string $note = null,
    ): Payment {
        if (! $plan->is_active) {
            throw ValidationException::withMessages(['plan_id' => 'Cette formule n\'est plus disponible.']);
        }

        $payment = new Payment;
        $payment->forceFill([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price, // montant toujours issu de la formule, jamais du formulaire
            'method' => $method,
            'transaction_reference' => trim($reference),
            // Justificatif stocké sur le disque privé : consultable seulement par l'administration.
            'proof_path' => $proof?->store("payments/{$store->id}", 'local'),
            'payer_note' => $note,
            'status' => PaymentStatus::Pending,
            'submitted_by' => $by->id,
        ])->save();

        ActivityLogger::log('payment.submitted', "Paiement soumis pour « {$store->name} » ({$plan->name}, réf. {$payment->transaction_reference})", $payment);

        if (! $by->isAdmin()) {
            Notifier::admins(new PaymentSubmitted($payment));
        }

        return $payment;
    }

    /**
     * Confirme un paiement et active / prolonge l'abonnement.
     * Idempotent : verrou sur le paiement + contrôle du statut + contrainte
     * unique subscriptions.payment_id. Une seconde confirmation est refusée.
     */
    public function confirm(Payment $payment, User $admin, ?string $note = null): Subscription
    {
        $subscription = DB::transaction(function () use ($payment, $admin, $note) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Pending || Subscription::query()->where('payment_id', $payment->id)->exists()) {
                throw ValidationException::withMessages(['payment' => 'Ce paiement a déjà été traité.']);
            }

            $store = Store::query()->lockForUpdate()->findOrFail($payment->store_id);
            $plan = Plan::withTrashed()->findOrFail($payment->plan_id);

            // Le renouvellement prend effet à la fin de l'abonnement en cours, sinon maintenant.
            $start = $store->hasActiveSubscription() ? $store->subscription_ends_at->copy() : now();
            $end = $start->copy()->addDays($plan->duration_days);

            $subscription = new Subscription;
            $subscription->forceFill([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'plan_name' => $plan->name,
                'price' => $payment->amount,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'active',
                'created_by' => $admin->id,
            ])->save();

            $store->subscription_ends_at = $end;
            $store->subscription_activated_at ??= now();
            // Le statut administratif (suspendu / archivé) n'est volontairement PAS modifié ici.
            $store->save();

            $payment->forceFill([
                'status' => PaymentStatus::Confirmed,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            ActivityLogger::log('payment.confirmed', "Paiement confirmé pour « {$store->name} » : abonnement {$plan->name} jusqu'au {$end->format('d/m/Y')}", $payment, [
                'amount' => (string) $payment->amount,
                'starts_at' => $start->toDateTimeString(),
                'ends_at' => $end->toDateTimeString(),
            ]);

            return $subscription;
        });

        $payment->refresh();
        Notifier::storeMembers($payment->store, new PaymentReviewed($payment), ability: 'billing');

        return $subscription;
    }

    public function reject(Payment $payment, User $admin, string $reason): void
    {
        $this->close($payment, PaymentStatus::Rejected, $admin, $reason);
        $payment->refresh();
        Notifier::storeMembers($payment->store, new PaymentReviewed($payment), ability: 'billing');
    }

    public function cancel(Payment $payment, User $by): void
    {
        $this->close($payment, PaymentStatus::Cancelled, $by, 'Annulé par '.$by->name);
    }

    private function close(Payment $payment, PaymentStatus $status, User $by, string $reason): void
    {
        DB::transaction(function () use ($payment, $status, $by, $reason) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages(['payment' => 'Ce paiement a déjà été traité.']);
            }

            $locked->forceFill([
                'status' => $status,
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();

            ActivityLogger::log('payment.'.$status->value, "Paiement #{$locked->id} : {$status->label()} — {$reason}", $locked);
        });
    }
}
