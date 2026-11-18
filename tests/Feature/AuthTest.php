<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $this->get('/connexion')->assertOk()->assertSee('Connexion à votre espace');
    }

    public function test_vendor_can_log_in_and_is_sent_to_his_store(): void
    {
        [$vendor, $store] = $this->vendorWithStore();

        $this->post('/connexion', ['email' => $vendor->email, 'password' => 'password'])
            ->assertRedirect(route('vendor.dashboard', $store));

        $this->assertAuthenticatedAs($vendor);
        $this->assertNotNull($vendor->fresh()->last_login_at);
    }

    public function test_admin_is_sent_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/connexion', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from('/connexion')->post('/connexion', ['email' => $user->email, 'password' => 'mauvais'])
            ->assertRedirect('/connexion')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['email' => $user->email, 'password' => 'mauvais']);
        }

        $response = $this->post('/connexion', ['email' => $user->email, 'password' => 'password']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Trop de tentatives', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/deconnexion')->assertRedirect('/connexion');
        $this->assertGuest();
    }

    public function test_temporary_password_forces_change_before_anything_else(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $vendor->forceFill(['must_change_password' => true])->save();

        $this->actingAs($vendor)->get(route('vendor.dashboard', $store))->assertRedirect(route('account.edit'));
        // La boutique publique et le site restent accessibles (bouton « Voir la boutique »).
        $this->actingAs($vendor)->get("/{$store->slug}")->assertOk();
        $this->actingAs($vendor)->get('/')->assertOk();

        $this->put(route('account.password'), [
            'current_password' => 'password',
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'NouveauMotDePasse1',
        ])->assertRedirect();

        $this->assertFalse($vendor->fresh()->must_change_password);
        $this->get(route('vendor.dashboard', $store))->assertOk();
    }
}
