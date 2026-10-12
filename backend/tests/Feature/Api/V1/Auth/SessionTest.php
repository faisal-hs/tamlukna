<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_authentication(): void
    {
        $this->get('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_me_returns_the_authenticated_user_without_sensitive_fields(): void
    {
        $user = User::factory()->withPhone()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->public_id)
            ->assertJsonPath('data.phone', $user->phone)
            ->assertJsonPath('data.phone_verified', true)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_tokens_of_suspended_accounts_are_rejected(): void
    {
        $user = User::factory()->suspended()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'account_inactive');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $phoneToken = $user->createToken('phone')->plainTextToken;
        $laptopToken = $user->createToken('laptop')->plainTextToken;

        $this->withToken($phoneToken)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($phoneToken)->getJson('/api/v1/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();

        $this->withToken($laptopToken)->getJson('/api/v1/me')->assertOk();
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $first = $user->createToken('one')->plainTextToken;
        $second = $user->createToken('two')->plainTextToken;

        $this->withToken($first)->postJson('/api/v1/auth/logout-all')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($second)->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('short-lived', ['*'], now()->addMinute())->plainTextToken;

        $this->travel(2)->minutes();

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
