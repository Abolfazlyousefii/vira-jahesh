<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Core\Livewire\StaffPage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);

        return $owner;
    }

    public function test_only_owner_can_open_staff_management_even_if_operator_has_the_permission(): void
    {
        $this->get('/panel/staff')->assertRedirect('/login');
        $this->owner();
        $this->get('/panel/staff')->assertOk();
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->givePermissionTo('users.manage');
        $this->actingAs($operator)->get('/panel/staff')->assertForbidden();
        Livewire::actingAs($operator)->test(StaffPage::class)->assertForbidden();
    }

    public function test_owner_can_create_cashier_with_explicit_password_and_limited_permissions(): void
    {
        $this->owner();
        Livewire::test(StaffPage::class)->call('create')->set('form.name', 'صندوقدار')->set('form.email', 'cashier@example.test')->set('password', 'Secure-cashier-test-123!')->set('password_confirmation', 'Secure-cashier-test-123!')->call('applyPreset', 'cashier')->call('save')->assertHasNoErrors();
        $cashier = User::where('email', 'cashier@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Secure-cashier-test-123!', $cashier->password));
        $this->assertTrue($cashier->can('pos.manage'));
        $this->assertTrue($cashier->can('pos.view'));
        $this->assertFalse($cashier->can('users.manage'));
        $this->assertFalse($cashier->hasRole('owner'));
        $this->actingAs($cashier)->get('/panel/pos')->assertOk();
        $this->get('/panel/inventory')->assertForbidden();
    }

    public function test_missing_or_weak_password_does_not_create_an_account(): void
    {
        $this->owner();
        Livewire::test(StaffPage::class)->call('create')->set('form.name', 'کاربر')->set('form.email', 'operator@example.test')->call('save')->assertHasErrors('password')->set('password', 'short')->set('password_confirmation', 'short')->call('save')->assertHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_owner_accounts_and_unrecognized_permission_keys_are_protected(): void
    {
        $owner = $this->owner();
        Livewire::test(StaffPage::class)->call('edit', $owner->id)->assertForbidden();
        $employee = User::factory()->create();
        $employee->assignRole('operator');
        Livewire::test(StaffPage::class)->call('edit', $employee->id)->set('form.access.users', 'manage')->call('save')->assertHasErrors();
        $this->assertFalse($employee->fresh()->can('users.manage'));
    }

    public function test_edit_keeps_existing_password_and_permission_changes_take_effect(): void
    {
        $this->owner();
        $employee = User::factory()->create();
        $employee->assignRole('operator');
        $employee->givePermissionTo(['pos.view', 'pos.manage']);
        $hash = $employee->password;
        Livewire::test(StaffPage::class)->call('edit', $employee->id)->set('form.access.pos', 'none')->set('form.access.customers', 'view')->call('save')->assertHasNoErrors();
        $this->assertSame($hash, $employee->fresh()->password);
        $this->assertFalse($employee->fresh()->can('pos.manage'));
        $this->assertTrue($employee->fresh()->can('customers.view'));
        $this->assertFalse($employee->fresh()->can('customers.manage'));
        $this->assertDatabaseHas('activity_log', ['subject_id' => $employee->id, 'description' => 'staff.updated']);
    }

    public function test_disabled_employee_cannot_login_or_continue_an_existing_session(): void
    {
        $this->owner();
        $employee = User::factory()->create(['password' => 'Existing-staff-password-123!']);
        $employee->assignRole('operator');
        Livewire::test(StaffPage::class)->call('edit', $employee->id)->set('form.is_active', false)->call('save')->assertHasNoErrors();
        $this->actingAs($employee->fresh())->get('/panel')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => $employee->email, 'password' => 'Existing-staff-password-123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_mutation_rechecks_core_module_and_owner_authorization(): void
    {
        $this->owner();
        $page = Livewire::test(StaffPage::class);
        config(['vira.modules.Core.enabled' => false]);
        $page->call('save')->assertNotFound();
    }

    public function test_login_error_uses_persian_copy(): void
    {
        $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'incorrect'])->assertSessionHasErrors(['email' => 'ایمیل یا رمز عبور درست نیست.']);
    }

    public function test_custom_role_accounts_cannot_be_misrepresented_by_the_basic_access_editor(): void
    {
        $this->owner();
        $custom = Role::findOrCreate('custom-warehouse', 'web');
        $custom->givePermissionTo('inventory.manage');
        $employee = User::factory()->create();
        $employee->assignRole(['operator', 'custom-warehouse']);
        Livewire::test(StaffPage::class)->call('edit', $employee->id)->assertForbidden();
        $this->assertTrue($employee->fresh()->can('inventory.manage'));
    }

    public function test_a_modified_operator_role_cannot_grant_hidden_access_to_a_new_employee(): void
    {
        $this->owner();
        Role::findByName('operator', 'web')->givePermissionTo('sales.manage');
        Livewire::test(StaffPage::class)->call('create')->assertHasErrors('form.access');
        $this->assertDatabaseCount('users', 1);
    }
}
