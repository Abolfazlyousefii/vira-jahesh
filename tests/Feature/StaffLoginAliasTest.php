<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLoginAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_staff_alias_accepts_persian_digits_without_changing_email_login(): void
    {
        $this->seed();
        $user = User::factory()->create(['email' => '09120000000@vira.local', 'password' => 'Synthetic-alias-test-123!']);
        $user->assignRole('owner');
        $this->post('/login', ['email' => '۰۹۱۲۰۰۰۰۰۰۰', 'password' => 'Synthetic-alias-test-123!'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'Synthetic-alias-test-123!'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
    }

    public function test_disabled_alias_and_wrong_password_are_rejected(): void
    {
        $user = User::factory()->create(['email' => '09120000000@vira.local', 'password' => 'Synthetic-alias-test-123!', 'is_active' => false]);
        $this->post('/login', ['email' => '09120000000', 'password' => 'Synthetic-alias-test-123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $user->update(['is_active' => true]);
        $this->post('/login', ['email' => '09120000000', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
