<?php

namespace Tests\Feature;

use App\Enums\AccessState;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Notifications\AccessExpired;
use App\Notifications\AccessExpiringSoon;
use App\Services\StoreService;
use App\Services\SubscriptionMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_store_gets_a_30_day_calendar_trial(): void
    {
        Carbon::setTestNow('2026-10-10 09:30:00');
        $vendor = User::factory()->create();

        $store = app(StoreService::class)->create(['name' => 'Bella Fashion'], $vendor);

        $this->assertSame('2026-10-10 09:30:00', $store->trial_starts_at->toDateTimeString());
        $this->assertSame('2026-11-09 09:30:00', $store->trial_ends_at->toDateTimeString());
        $this->assertSame(30, $store->daysRemaining());
        $this->assertSame(AccessState::Trial, $store->accessState());
        $this->assertDatabaseHas('trial_histories', ['store_id' => $store->id, 'action' => 'started', 'days' => 30]);
        $this->assertTrue($vendor->belongsToStore($store));
    }

    public function test_trial_duration_follows_the_platform_setting(): void
    {
        Setting::put(['trial_days' => '14']);
        $store = app(StoreService::class)->create(['name' => 'Test'], User::factory()->create());

        $this->assertSame(14, $store->daysRemaining());
    }

    public function test_access_expires_without_relying_on_the_scheduler(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $store = Store::factory()->create(['trial_ends_at' => '2026-10-11 10:00:00']);
        $this->assertTrue($store->isOperational());

        Carbon::setTestNow('2026-10-11 10:00:01');
        $this->assertSame(AccessState::Expired, $store->fresh()->accessState());
        $this->assertSame(0, $store->fresh()->daysRemaining());
    }

    public function test_admin_can_extend_a_trial_with_a_reason(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $admin = User::factory()->admin()->create();
        $store = Store::factory()->create(['trial_ends_at' => '2026-10-15 10:00:00']);

        $this->actingAs($admin)->post(route('admin.stores.trial.extend', $store), ['days' => 7, 'reason' => 'Vendeur en déplacement'])
            ->assertSessionHas('success');

        $this->assertSame('2026-10-22 10:00:00', $store->fresh()->trial_ends_at->toDateTimeString());
        $this->assertDatabaseHas('trial_histories', ['store_id' => $store->id, 'action' => 'extended', 'days' => 7, 'performed_by' => $admin->id, 'reason' => 'Vendeur en déplacement']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'trial.extended', 'user_id' => $admin->id]);
    }

    public function test_extension_requires_a_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $store = Store::factory()->create();

        $this->actingAs($admin)->post(route('admin.stores.trial.extend', $store), ['days' => 7])->assertSessionHasErrors('reason');
    }

    public function test_sales_and_vendors_cannot_extend_a_trial(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $sales = User::factory()->sales()->create();
        $before = $store->trial_ends_at->toDateTimeString();

        $this->actingAs($sales)->post(route('admin.stores.trial.extend', $store), ['days' => 30, 'reason' => 'Je veux'])->assertForbidden();
        $this->actingAs($vendor)->post(route('admin.stores.trial.extend', $store), ['days' => 30, 'reason' => 'Je veux'])->assertForbidden();

        $this->assertSame($before, $store->fresh()->trial_ends_at->toDateTimeString());
    }

    public function test_reminders_are_sent_once_per_threshold(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-10 10:00:00');
        [$vendor, $store] = $this->vendorWithStore(['trial_ends_at' => '2026-10-16 12:00:00']); // ≈ J-7
        $monitor = app(SubscriptionMonitor::class);

        $monitor->run();
        $monitor->run();
        Notification::assertSentToTimes($vendor, AccessExpiringSoon::class, 1);

        Carbon::setTestNow('2026-10-13 13:00:00'); // ≈ J-3
        $monitor->run();
        $monitor->check($store->fresh());
        Notification::assertSentToTimes($vendor, AccessExpiringSoon::class, 2);

        Carbon::setTestNow('2026-10-16 12:00:01'); // expiré
        $monitor->run();
        $monitor->run();
        Notification::assertSentToTimes($vendor, AccessExpired::class, 1);
        $this->assertDatabaseHas('trial_histories', ['store_id' => $store->id, 'action' => 'expired']);
    }

    public function test_vendor_dashboard_shows_remaining_days_and_triggers_on_demand_check(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        [$vendor, $store] = $this->vendorWithStore(['trial_ends_at' => '2026-10-12 10:00:00']);

        $this->actingAs($vendor)->get(route('vendor.dashboard', $store))->assertOk()->assertSee('2 jour(s)', false);
        $this->assertDatabaseCount('store_alerts', 1);
        $this->assertSame(1, $vendor->notifications()->count());
    }

    public function test_expired_vendor_keeps_limited_access(): void
    {
        [$vendor, $store] = $this->vendorWithStore(['trial_starts_at' => now()->subDays(40), 'trial_ends_at' => now()->subDays(10)]);

        $this->actingAs($vendor)->get(route('vendor.dashboard', $store))->assertOk()->assertSee('Accès limité');
        $this->actingAs($vendor)->get(route('vendor.subscription.show', $store))->assertOk();
        $this->actingAs($vendor)->get(route('vendor.export', [$store, 'commandes']))->assertOk();
        $this->assertDatabaseHas('stores', ['id' => $store->id, 'deleted_at' => null]);
    }
}
