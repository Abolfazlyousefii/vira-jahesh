<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_command_creates_a_hashed_account_and_refuses_to_overwrite_it(): void
    {
        putenv('VIRA_SETUP_PASSWORD=Owner-test-password-123');
        try {
            $this->artisan('vira:owner', ['email' => 'owner@example.test'])->assertSuccessful();
            $user = User::where('email', 'owner@example.test')->firstOrFail();
            $this->assertTrue($user->hasRole('owner'));
            $this->assertTrue(Hash::check('Owner-test-password-123', $user->password));
            $originalHash = $user->password;
            $this->artisan('vira:owner', ['email' => 'owner@example.test'])->assertFailed();
            $this->assertSame($originalHash, $user->fresh()->password);
            $this->assertDatabaseCount('users', 1);
        } finally {
            putenv('VIRA_SETUP_PASSWORD');
        }
    }
}
