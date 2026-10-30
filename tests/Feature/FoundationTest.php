<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_is_public_and_panel_requires_login(): void
    {
        $this->get('/')->assertOk()->assertSee('ویرا');
        $this->get('/panel')->assertRedirect('/login');
    }

    public function test_customer_account_cannot_access_management_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/panel')->assertForbidden();
    }

    public function test_authorized_manager_can_open_panel_and_module_status(): void
    {
        $this->seed();
        $manager = User::factory()->create();
        $manager->assignRole('owner');
        $this->actingAs($manager)->get('/panel')->assertOk()->assertSee('ویرا');
        $this->get('/panel/modules')->assertOk()->assertSee('کالا و کاتالوگ');
    }

    public function test_disabled_crm_is_blocked_without_breaking_storefront(): void
    {
        $this->seed();
        $manager = User::factory()->create();
        $manager->assignRole('owner');
        config(['vira.modules.Crm.enabled' => false]);
        $this->actingAs($manager)->get('/panel/crm')->assertNotFound();
        $this->get('/')->assertOk();
        $this->get('/panel')->assertOk();
    }

    public function test_module_with_disabled_dependency_is_not_available(): void
    {
        $this->seed();
        $manager = User::factory()->create();
        $manager->assignRole('owner');
        config(['vira.modules.Catalog.enabled' => false]);
        $this->actingAs($manager)->get('/panel/pos')->assertNotFound();
    }

    public function test_panel_permission_does_not_grant_access_to_every_module(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->actingAs($operator)->get('/panel')->assertOk();
        $this->get('/panel/crm')->assertForbidden();
        $this->get('/panel/modules')->assertForbidden();
        $operator->givePermissionTo('crm.view');
        $this->get('/panel/crm')->assertOk();
    }

    public function test_valid_login_and_logout_work_without_public_registration(): void
    {
        $user = User::factory()->create(['password' => 'Secure-test-password-123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Secure-test-password-123'])
            ->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/register')->assertNotFound();
    }

    public function test_invalid_login_is_rejected_and_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'invalid'])
                ->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'invalid'])
            ->assertStatus(429);
        $this->assertGuest();
    }
}
