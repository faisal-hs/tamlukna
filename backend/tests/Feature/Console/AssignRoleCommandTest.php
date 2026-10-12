<?php

namespace Tests\Feature\Console;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssignRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_a_role_by_email(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->artisan('users:assign-role', ['email' => 'Staff@Example.com', 'role' => 'staff'])
            ->assertSuccessful();

        $this->assertTrue(User::query()->findOrFail($user->id)->hasRole('staff'));
    }

    public function test_revokes_a_role(): void
    {
        $user = User::factory()->withRole(Role::Staff)->create(['email' => 'staff@example.com']);

        $this->artisan('users:assign-role', ['email' => 'staff@example.com', 'role' => 'staff', '--revoke' => true])
            ->assertSuccessful();

        $this->assertFalse(User::query()->findOrFail($user->id)->hasRole('staff'));
    }

    public function test_rejects_unknown_roles(): void
    {
        $user = User::factory()->create(['email' => 'someone@example.com']);

        $this->artisan('users:assign-role', ['email' => 'someone@example.com', 'role' => 'superuser'])
            ->assertFailed();

        $this->assertCount(0, User::query()->findOrFail($user->id)->getRoleNames());
    }

    public function test_rejects_unknown_emails(): void
    {
        $this->artisan('users:assign-role', ['email' => 'nobody@example.com', 'role' => 'admin'])
            ->assertFailed();
    }
}
