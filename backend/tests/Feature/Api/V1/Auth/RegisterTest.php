<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Faisal Test',
            'email' => 'faisal@example.com',
            'password' => 'secret123A',
            'password_confirmation' => 'secret123A',
            'device_name' => 'phpunit',
        ], $overrides);
    }

    public function test_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'faisal@example.com')
            ->assertJsonPath('data.user.status', 'active')
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.token.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonStructure(['data' => [
                'user' => ['id', 'name', 'email', 'phone', 'preferred_locale', 'status', 'created_at'],
                'token' => ['access_token', 'token_type', 'expires_at'],
            ]]);

        $user = User::query()->where('email', 'faisal@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret123A', $user->password));
        $this->assertSame($user->public_id, $response->json('data.user.id'));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_registration_normalizes_email_and_phone(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload([
            'email' => 'Faisal@Example.COM',
            'phone' => '0512345678',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'faisal@example.com')
            ->assertJsonPath('data.user.phone', '+966512345678');
    }

    public function test_registration_rejects_duplicate_email_case_insensitively(): void
    {
        User::factory()->create(['email' => 'faisal@example.com']);

        $this->postJson('/api/v1/auth/register', $this->payload(['email' => 'FAISAL@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validates_password_strength_and_confirmation(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload([
            'password' => 'short',
            'password_confirmation' => 'different',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_rejects_non_saudi_mobile_numbers(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['phone' => '0112345678']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_client_cannot_set_account_status(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['status' => 'suspended']))
            ->assertCreated()
            ->assertJsonPath('data.user.status', 'active');
    }

    public function test_field_errors_are_arabic_by_default(): void
    {
        $this->withHeaders(['Accept-Language' => ''])
            ->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'حقل الاسم مطلوب.');
    }
}
