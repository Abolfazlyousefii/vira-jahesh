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
        $this->get('/panel/modules')->assertOk()->assertSee('کالاهای نرم‌افزار');
    }

    public function test_owner_sees_both_workspaces_on_the_workspace_selector(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)->get('/workspace')
            ->assertOk()
            ->assertSee('مدیریت سایت')
            ->assertSee('مدیریت داخلی');
    }

    public function test_website_only_staff_is_redirected_to_site_admin_and_cannot_open_erp(): void
    {
        $this->seed();
        $manager = User::factory()->create();
        $manager->assignRole('website_operator');
        $manager->givePermissionTo(['website.manage', 'storefront.manage']);

        $this->actingAs($manager)->get('/workspace')->assertRedirect('/site-admin');
        $this->get('/site-admin')->assertOk()->assertSee('مدیریت سایت');
        $this->get('/panel')->assertForbidden();
    }

    public function test_erp_only_staff_is_redirected_to_internal_panel_and_cannot_open_site_admin(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)->get('/workspace')->assertRedirect('/panel');
        $this->get('/panel')->assertOk();
        $this->get('/site-admin')->assertForbidden();
    }

    public function test_user_without_workspace_permissions_cannot_open_workspace_selector(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/workspace')->assertForbidden();
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
        $this->seed();
        $user = User::factory()->create(['password' => 'Secure-test-password-123']);
        $user->assignRole('owner');

        $this->get('/panel')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'Secure-test-password-123'])
            ->assertRedirect('/workspace');
        $this->assertAuthenticatedAs($user);
        $this->get('/workspace')->assertOk()->assertSee('مدیریت سایت')->assertSee('مدیریت داخلی');
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

    public function test_disabling_website_admin_hides_that_destination_without_blocking_erp(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        config(['vira.modules.WebsiteAdmin.enabled' => false]);

        $this->actingAs($owner)->get('/workspace')->assertRedirect('/panel');
        $this->get('/site-admin')->assertNotFound();
        $this->get('/panel')->assertOk();
    }
}
