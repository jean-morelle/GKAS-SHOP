<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/connexion');
    }

    public function test_vendor_cannot_access_admin_area(): void
    {
        [$vendor] = $this->vendorWithStore();

        $this->actingAs($vendor)->get('/admin')->assertForbidden();
        $this->actingAs($vendor)->get('/admin/boutiques')->assertForbidden();
        $this->actingAs($vendor)->get('/admin/parametres')->assertForbidden();
    }

    public function test_admin_pages_are_accessible_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $store = Store::factory()->create();

        foreach (['/admin', '/admin/boutiques', '/admin/boutiques/creer', "/admin/boutiques/{$store->slug}", '/admin/essais',
            '/admin/paiements', '/admin/abonnements', '/admin/prospects', '/admin/utilisateurs', '/admin/formules',
            '/admin/parametres', '/admin/journal'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_sales_has_limited_admin_rights(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get('/admin')->assertOk();
        $this->actingAs($sales)->get('/admin/prospects')->assertOk();
        $this->actingAs($sales)->get('/admin/parametres')->assertForbidden();
        $this->actingAs($sales)->get('/admin/utilisateurs')->assertForbidden();
        $this->actingAs($sales)->get('/admin/formules')->assertForbidden();
    }

    public function test_admin_can_create_store_with_new_vendor_and_temporary_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/boutiques', [
            'owner_mode' => 'new',
            'owner_name' => 'Awa Mensah',
            'owner_email' => 'awa@example.com',
            'name' => 'Délices de Lomé',
            'slug' => 'delices-de-lome',
            'primary_color' => '#059669',
            'theme' => 'moderne',
            'is_published' => '1',
        ])->assertRedirect(route('admin.stores.show', 'delices-de-lome'))->assertSessionHas('credentials');

        $vendor = User::where('email', 'awa@example.com')->firstOrFail();
        $store = Store::where('slug', 'delices-de-lome')->firstOrFail();
        $this->assertTrue($vendor->must_change_password);
        $this->assertTrue($vendor->belongsToStore($store));
        $this->assertDatabaseHas('activity_logs', ['action' => 'store.created', 'subject_id' => $store->id]);
    }

    public function test_reserved_slug_is_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->create();

        $this->actingAs($admin)->post('/admin/boutiques', [
            'owner_mode' => 'existing', 'owner_id' => $vendor->id,
            'name' => 'Admin', 'slug' => 'admin', 'primary_color' => '#000000', 'theme' => 'moderne',
        ])->assertSessionHasErrors('slug');
    }

    public function test_only_admin_can_suspend_a_store(): void
    {
        $sales = User::factory()->sales()->create();
        $admin = User::factory()->admin()->create();
        $store = Store::factory()->create();

        $this->actingAs($sales)->post(route('admin.stores.status', $store), ['status' => 'suspended', 'reason' => 'Test'])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.stores.status', $store), ['status' => 'suspended', 'reason' => 'Impayé'])->assertRedirect();
        $this->assertSame('suspended', $store->fresh()->status->value);
        $this->assertDatabaseHas('activity_logs', ['action' => 'store.status_changed']);
    }
}
