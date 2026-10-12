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

    /**
     * @return array<string, string>
     */
    private function registration(string $email): array
    {
        return [
            'name' => 'New User',
            'email' => $email,
            'password' => 'secret123A',
            'password_confirmation' => 'secret123A',
        ];
    }

    public function test_registration_sends_the_verification_email_in_the_request_language(): void
    {
        Notification::fake();

        $this->withHeaders(['Accept-Language' => ''])
            ->postJson('/api/v1/auth/register', $this->registration('arabic@example.com'))
            ->assertCreated();
        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/v1/auth/register', $this->registration('english@example.com'))
            ->assertCreated();

        $arabic = User::query()->where('email', 'arabic@example.com')->firstOrFail();
        $english = User::query()->where('email', 'english@example.com')->firstOrFail();

        $this->assertSame('ar', $arabic->preferred_locale);
        $this->assertSame('en', $english->preferred_locale);

        Notification::assertSentTo($arabic, VerifyEmail::class,
            fn ($notification, array $channels, User $notifiable, ?string $locale): bool => $locale === 'ar');
        Notification::assertSentTo($english, VerifyEmail::class,
            fn ($notification, array $channels, User $notifiable, ?string $locale): bool => $locale === 'en');
    }

    public function test_verification_links_use_app_url_even_with_a_forged_host_header(): void
    {
        Notification::fake();

        $this->postJson('http://evil.example/api/v1/auth/register', $this->registration('victim@example.com'))
            ->assertCreated();

        $user = User::query()->where('email', 'victim@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, rtrim((string) config('app.url'), '/').'/')
                && ! str_contains($url, 'evil.example');
        });
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
