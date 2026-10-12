<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_receives_a_token(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'OWNER@example.com',
            'password' => 'password',
            'device_name' => 'iPhone',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->public_id)
            ->assertJsonStructure(['data' => ['token' => ['access_token', 'expires_at']]]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertSame('iPhone', $user->tokens()->firstOrFail()->name);
    }

    public function test_wrong_password_and_unknown_email_return_the_same_error(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $wrongPassword = $this->postJson('/api/v1/auth/login', ['email' => 'owner@example.com', 'password' => 'wrong-password']);
        $unknownEmail = $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);

        $wrongPassword->assertStatus(422)->assertJsonValidationErrors(['email']);
        $unknownEmail->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->assertSame($wrongPassword->json('errors'), $unknownEmail->json('errors'));
    }

    public function test_suspended_account_cannot_log_in_even_with_the_correct_password(): void
    {
        $user = User::factory()->suspended()->create(['email' => 'suspended@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'suspended@example.com', 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('code', 'account_inactive');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_is_locked_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'owner@example.com', 'password' => 'wrong'])
                ->assertStatus(422);
        }

        // Even the correct password is refused during the lockout.
        $this->postJson('/api/v1/auth/login', ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertHeader('Retry-After');
    }
}
