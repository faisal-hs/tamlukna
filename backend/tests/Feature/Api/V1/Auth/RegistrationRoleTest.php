<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class RegistrationRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $extra
     */
    private function register(string $email, array $extra = []): TestResponse
    {
        return $this->postJson('/api/v1/auth/register', array_merge([
            'name' => 'Role Test',
            'email' => $email,
            'password' => 'secret123A',
            'password_confirmation' => 'secret123A',
        ], $extra));
    }

    public function test_new_users_get_the_buyer_role_by_default(): void
    {
        $this->register('default@example.com')
            ->assertCreated()
            ->assertJsonPath('data.user.roles', ['buyer'])
            ->assertJsonPath('data.user.permissions', []);
    }

    public function test_users_may_choose_renter_or_owner(): void
    {
        $this->register('renter@example.com', ['account_type' => 'renter'])
            ->assertCreated()
            ->assertJsonPath('data.user.roles', ['renter']);

        $this->register('owner@example.com', ['account_type' => 'owner'])
            ->assertCreated()
            ->assertJsonPath('data.user.roles', ['owner'])
            ->assertJsonPath('data.user.permissions', ['properties.create', 'properties.manage-own']);
    }

    public function test_privileged_or_unknown_roles_cannot_be_self_assigned(): void
    {
        foreach (['broker', 'developer', 'staff', 'admin', 'superuser'] as $i => $type) {
            $this->register("attempt{$i}@example.com", ['account_type' => $type])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['account_type']);
        }

        $this->assertSame(0, User::query()->count());
    }
}
