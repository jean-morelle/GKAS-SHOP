<?php

namespace Tests\Feature;

use App\Enums\AccessState;
use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Store;
use App\Models\User;
use App\Notifications\PaymentSubmitted;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPayment(Store $store, Plan $plan, string $ref = 'TX-0001'): Payment
    {
        return app(SubscriptionService::class)->submitPayment($store, $plan, PaymentMethod::MobileMoney, $ref, $store->owner);
    }

    public function test_vendor_submission_creates_pending_payment_with_plan_price(): void
    {
        Notification::fake();
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        [$vendor, $store] = $this->vendorWithStore();
        $plan = Plan::factory()->create(['price' => 5000]);

        $this->actingAs($vendor)->post(route('vendor.subscription.pay', $store), [
            'plan_id' => $plan->id, 'method' => 'mobile_money', 'transaction_reference' => 'FL123456',
            'amount' => 1, // ignoré
            'proof' => $this->fakePng('recu.png'),
        ])->assertSessionHas('success');

        $payment = Payment::firstOrFail();
        $this->assertSame('pending', $payment->status->value);
        $this->assertSame('5000.00', (string) $payment->amount);
        Storage::disk('local')->assertExists($payment->proof_path);
        Notification::assertSentTo($admin, PaymentSubmitted::class);

        // Une simple déclaration n'active rien.
        $this->assertFalse($store->fresh()->hasActiveSubscription());
    }

    public function test_confirmation_activates_subscription(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $admin = User::factory()->admin()->create();
        [, $store] = $this->vendorWithStore(['trial_starts_at' => now()->subDays(40), 'trial_ends_at' => now()->subDays(10)]);
        $plan = Plan::factory()->create(['duration_days' => 30]);
        $payment = $this->pendingPayment($store, $plan);
        $this->assertSame(AccessState::Expired, $store->accessState());

        $this->actingAs($admin)->post(route('admin.payments.confirm', $payment))->assertSessionHas('success');

        $store->refresh();
        $this->assertSame(AccessState::Subscribed, $store->accessState());
        $this->assertSame('2026-11-09 10:00:00', $store->subscription_ends_at->toDateTimeString());
        $this->assertSame('confirmed', $payment->fresh()->status->value);
        $this->assertSame($admin->id, $payment->fresh()->reviewed_by);
        $this->assertDatabaseHas('subscriptions', ['payment_id' => $payment->id, 'store_id' => $store->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.confirmed']);
    }

    public function test_renewal_extends_from_the_end_of_current_subscription(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $admin = User::factory()->admin()->create();
        [, $store] = $this->vendorWithStore(['subscription_ends_at' => '2026-10-20 10:00:00']);
        $plan = Plan::factory()->create(['duration_days' => 90]);

        app(SubscriptionService::class)->confirm($this->pendingPayment($store, $plan), $admin);

        $this->assertSame('2027-01-18 10:00:00', $store->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_double_confirmation_does_not_extend_twice(): void
    {
        $admin = User::factory()->admin()->create();
        [, $store] = $this->vendorWithStore();
        $plan = Plan::factory()->create(['duration_days' => 30]);
        $payment = $this->pendingPayment($store, $plan);
        $service = app(SubscriptionService::class);

        $service->confirm($payment, $admin);
        $endAfterFirst = $store->fresh()->subscription_ends_at->toDateTimeString();

        try {
            $service->confirm($payment, $admin);
            $this->fail('La seconde confirmation aurait dû être refusée.');
        } catch (ValidationException) {
        }

        // Également via HTTP (double clic / rejeu de requête).
        $this->actingAs($admin)->post(route('admin.payments.confirm', $payment))->assertSessionHasErrors('payment');

        $this->assertSame($endAfterFirst, $store->fresh()->subscription_ends_at->toDateTimeString());
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_rejected_payment_cannot_be_confirmed_later(): void
    {
        $admin = User::factory()->admin()->create();
        [, $store] = $this->vendorWithStore();
        $payment = $this->pendingPayment($store, Plan::factory()->create());

        $this->actingAs($admin)->post(route('admin.payments.reject', $payment), ['reason' => 'Référence introuvable'])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.payments.confirm', $payment))->assertSessionHasErrors('payment');

        $this->assertSame('rejected', $payment->fresh()->status->value);
        $this->assertNull($store->fresh()->subscription_ends_at);
    }

    public function test_unauthorized_users_cannot_confirm_payments(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $sales = User::factory()->sales()->create();
        $payment = $this->pendingPayment($store, Plan::factory()->create());

        $this->actingAs($vendor)->post(route('admin.payments.confirm', $payment))->assertForbidden();
        $this->actingAs($sales)->post(route('admin.payments.confirm', $payment))->assertForbidden();
        $this->post('/deconnexion');
        $this->post(route('admin.payments.confirm', $payment))->assertRedirect('/connexion');

        $this->assertSame('pending', $payment->fresh()->status->value);
        $this->assertNull($store->fresh()->subscription_ends_at);
    }

    public function test_payment_does_not_reactivate_a_suspended_store(): void
    {
        $admin = User::factory()->admin()->create();
        [, $store] = $this->vendorWithStore(['status' => 'suspended', 'suspension_reason' => 'Fraude']);
        $payment = $this->pendingPayment($store, Plan::factory()->create());

        app(SubscriptionService::class)->confirm($payment, $admin);

        $store->refresh();
        $this->assertTrue($store->hasActiveSubscription());
        $this->assertSame('suspended', $store->status->value);
        $this->assertSame(AccessState::Suspended, $store->accessState());
    }

    public function test_transaction_reference_cannot_be_reused(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $plan = Plan::factory()->create();
        $first = $this->pendingPayment($store, $plan, 'TX-DUP');
        app(SubscriptionService::class)->cancel($first, $vendor);

        $this->actingAs($vendor)->post(route('vendor.subscription.pay', $store), [
            'plan_id' => $plan->id, 'method' => 'mobile_money', 'transaction_reference' => 'TX-DUP',
        ])->assertSessionHasErrors('transaction_reference');
    }

    public function test_vendor_cannot_submit_payment_for_another_store(): void
    {
        [$vendorA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();
        $plan = Plan::factory()->create();

        $this->actingAs($vendorA)->post(route('vendor.subscription.pay', $storeB), [
            'plan_id' => $plan->id, 'method' => 'mobile_money', 'transaction_reference' => 'TX-9999',
        ])->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }
}
