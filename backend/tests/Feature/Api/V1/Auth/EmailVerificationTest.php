<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user): string
    {
        return (new VerifyEmail)->toMail($user)->actionUrl;
    }

    public function test_registration_sends_a_verification_email_in_the_users_language(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret123A',
            'password_confirmation' => 'secret123A',
        ])->assertCreated();

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            fn ($notification, array $channels, User $notifiable, ?string $locale): bool => $locale === 'ar'
        );
    }

    public function test_signed_link_verifies_the_email_using_the_public_id(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user);

        $this->assertStringContainsString('/email/verify/'.$user->public_id.'/', $url);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('message', 'Your email address has been verified.');

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_tampered_and_expired_links_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user);

        $tampered = str_replace(sha1($user->email), sha1('attacker@example.com'), $url);
        $this->getJson($tampered)
            ->assertForbidden()
            ->assertJsonPath('code', 'invalid_verification_link');

        $this->travel(61)->minutes();

        $this->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('code', 'invalid_verification_link');

        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_already_verified_email_reports_so(): void
    {
        $user = User::factory()->create();

        $this->getJson($this->verificationUrl($user))
            ->assertOk()
            ->assertJsonPath('message', 'Your email address is already verified.');
    }

    public function test_resend_requires_authentication_and_only_sends_to_unverified_users(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/email/verification-notification')->assertUnauthorized();

        $unverified = User::factory()->unverified()->create();
        $this->withToken($unverified->createToken('t')->plainTextToken)
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk();
        Notification::assertSentTo($unverified, VerifyEmail::class);

        $this->app['auth']->forgetGuards();

        $verified = User::factory()->create();
        $this->withToken($verified->createToken('t')->plainTextToken)
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Your email address is already verified.');
        Notification::assertNotSentTo($verified, VerifyEmail::class);
    }
}
