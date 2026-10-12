<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_frontend_reset_link_to_existing_users(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'Owner@Example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'http://localhost:3000/reset-password?token=')
                && str_contains($url, urlencode('owner@example.com'));
        });
    }

    public function test_forgot_password_response_is_identical_for_unknown_emails(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'owner@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'owner@example.com']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_valid_token_resets_the_password_and_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $user->createToken('phone');
        $user->createToken('laptop');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'owner@example.com',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecret123', $user->password));
        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/v1/auth/login', ['email' => 'owner@example.com', 'password' => 'NewSecret123'])
            ->assertOk();
    }

    public function test_invalid_token_is_rejected_and_password_is_unchanged(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'owner@example.com',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_unknown_email_returns_the_same_error_as_an_invalid_token(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);
        $payload = ['token' => 'whatever', 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123'];

        $invalidToken = $this->postJson('/api/v1/auth/reset-password', $payload + ['email' => 'owner@example.com']);
        $unknownEmail = $this->postJson('/api/v1/auth/reset-password', $payload + ['email' => 'nobody@example.com']);

        $invalidToken->assertStatus(422);
        $unknownEmail->assertStatus(422);
        $this->assertSame($invalidToken->json('errors'), $unknownEmail->json('errors'));
    }

    public function test_new_password_must_be_strong_and_confirmed(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'owner@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
