<?php

namespace Tests\Feature;

use App\Enums\StoreRole;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AccessExpiringSoon;
use App\Notifications\NewOrderReceived;
use App\Services\OrderService;
use App\Services\SubscriptionMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private function member($store, StoreRole $role): User
    {
        $user = User::factory()->create();
        $store->users()->attach($user->id, ['role' => $role->value]);

        return $user;
    }

    private function order($store)
    {
        return app(OrderService::class)->place($store, [Product::factory()->for($store)->create()->id => 1], [
            'name' => 'Client', 'phone' => '90000000', 'area' => 'Bè', 'address' => 'Rue 1',
        ]);
    }

    public function test_owner_can_add_a_new_staff_member_with_temporary_password(): void
    {
        [$owner, $store] = $this->vendorWithStore();

        $this->actingAs($owner)->post(route('vendor.team.store', $store), [
            'name' => 'Koffi Employé', 'email' => 'koffi@example.com', 'role' => 'staff',
        ])->assertSessionHas('credentials');

        $staff = User::where('email', 'koffi@example.com')->firstOrFail();
        $this->assertSame(StoreRole::Staff, $staff->storeRole($store));
        $this->assertTrue($staff->must_change_password);
        $this->assertDatabaseHas('activity_logs', ['action' => 'store.member_added']);
    }

    public function test_existing_vendor_can_be_added_but_not_a_gkas_account(): void
    {
        [$owner, $store] = $this->vendorWithStore();
        $vendor = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($owner)->post(route('vendor.team.store', $store), ['name' => 'X', 'email' => $vendor->email, 'role' => 'manager'])
            ->assertSessionMissing('credentials')->assertSessionHas('success');
        $this->assertSame(StoreRole::Manager, $vendor->storeRole($store));

        $this->actingAs($owner)->post(route('vendor.team.store', $store), ['name' => 'A', 'email' => $admin->email, 'role' => 'manager'])
            ->assertSessionHasErrors('email');
        $this->assertFalse($admin->belongsToStore($store));
    }

    public function test_owner_role_cannot_be_granted_from_the_team_page(): void
    {
        [$owner, $store] = $this->vendorWithStore();

        $this->actingAs($owner)->post(route('vendor.team.store', $store), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'owner'])
            ->assertSessionHasErrors('role');
    }

    public function test_staff_can_only_process_orders(): void
    {
        [, $store] = $this->vendorWithStore();
        $staff = $this->member($store, StoreRole::Staff);
        $order = $this->order($store);

        $this->actingAs($staff)->get(route('vendor.dashboard', $store))->assertOk()->assertDontSee('Ventes du mois')->assertDontSee('Mon abonnement');
        $this->actingAs($staff)->get(route('vendor.orders.index', $store))->assertOk();
        $this->actingAs($staff)->post(route('vendor.orders.status', [$store, $order]), ['status' => 'confirmed'])->assertSessionHas('success');
        $this->assertSame('confirmed', $order->fresh()->status->value);

        foreach ([route('vendor.products.index', $store), route('vendor.products.create', $store), route('vendor.categories.index', $store),
            route('vendor.settings.edit', $store), route('vendor.appearance.edit', $store), route('vendor.delivery.index', $store),
            route('vendor.subscription.show', $store), route('vendor.team.index', $store), route('vendor.export', [$store, 'commandes'])] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }

        $this->actingAs($staff)->post(route('vendor.products.store', $store), ['name' => 'X', 'price' => 1000])->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => 'X']);
    }

    public function test_manager_cannot_manage_billing_or_team(): void
    {
        [, $store] = $this->vendorWithStore();
        $manager = $this->member($store, StoreRole::Manager);
        $plan = Plan::factory()->create();

        $this->actingAs($manager)->get(route('vendor.products.index', $store))->assertOk();
        $this->actingAs($manager)->get(route('vendor.settings.edit', $store))->assertOk();
        $this->actingAs($manager)->get(route('vendor.export', [$store, 'produits']))->assertOk();
        $this->actingAs($manager)->post(route('vendor.products.store', $store), ['name' => 'Sac', 'price' => 5000])->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Sac', 'store_id' => $store->id]);

        $this->actingAs($manager)->get(route('vendor.subscription.show', $store))->assertForbidden();
        $this->actingAs($manager)->post(route('vendor.subscription.pay', $store), ['plan_id' => $plan->id, 'method' => 'mobile_money', 'transaction_reference' => 'TX-1'])->assertForbidden();
        $this->actingAs($manager)->get(route('vendor.team.index', $store))->assertForbidden();
        $this->actingAs($manager)->post(route('vendor.team.store', $store), ['name' => 'Y', 'email' => 'y@example.com', 'role' => 'manager'])->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_owner_and_self_cannot_be_modified_and_members_can_be_removed(): void
    {
        [$owner, $store] = $this->vendorWithStore();
        $manager = $this->member($store, StoreRole::Manager);
        $staff = $this->member($store, StoreRole::Staff);

        $this->actingAs($owner)->delete(route('vendor.team.destroy', [$store, $owner]))->assertForbidden();
        $this->actingAs($owner)->put(route('vendor.team.update', [$store, $manager]), ['role' => 'staff'])->assertSessionHas('success');
        $this->assertSame(StoreRole::Staff, $manager->fresh()->storeRole($store));

        $this->actingAs($owner)->delete(route('vendor.team.destroy', [$store, $staff]))->assertSessionHas('success');
        $this->assertFalse($staff->fresh()->belongsToStore($store));
        $this->actingAs($staff->fresh())->get(route('vendor.dashboard', $store))->assertNotFound();
    }

    public function test_owner_cannot_manage_members_of_another_store(): void
    {
        [$ownerA, $storeA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();
        $memberB = $this->member($storeB, StoreRole::Staff);

        $this->actingAs($ownerA)->delete("/vendeur/{$storeA->slug}/equipe/{$memberB->id}")->assertNotFound();
        $this->actingAs($ownerA)->delete("/vendeur/{$storeB->slug}/equipe/{$memberB->id}")->assertNotFound();
        $this->assertTrue($memberB->belongsToStore($storeB));
    }

    public function test_notifications_are_targeted_by_role(): void
    {
        Notification::fake();
        [$owner, $store] = $this->vendorWithStore(['trial_ends_at' => now()->addDays(2)]);
        $staff = $this->member($store, StoreRole::Staff);

        $this->order($store);
        Notification::assertSentTo([$owner, $staff], NewOrderReceived::class);

        app(SubscriptionMonitor::class)->check($store);
        Notification::assertSentTo($owner, AccessExpiringSoon::class);
        Notification::assertNotSentTo($staff, AccessExpiringSoon::class);
    }
}
