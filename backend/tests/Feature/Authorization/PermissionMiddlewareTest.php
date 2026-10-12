<?php

namespace Tests\Feature\Authorization;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:sanctum', 'active', 'permission:'.Permission::PropertiesModerate->value])
            ->get('api/v1/__test/moderation-only', fn () => ApiResponse::success());
    }

    public function test_unauthenticated_requests_get_401(): void
    {
        $this->getJson('/api/v1/__test/moderation-only')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_users_without_the_permission_get_403(): void
    {
        $buyer = User::factory()->withRole(Role::Buyer)->create();

        $this->withToken($buyer->createToken('t')->plainTextToken)
            ->getJson('/api/v1/__test/moderation-only')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_users_with_the_permission_are_allowed(): void
    {
        $staff = User::factory()->withRole(Role::Staff)->create();

        $this->withToken($staff->createToken('t')->plainTextToken)
            ->getJson('/api/v1/__test/moderation-only')
            ->assertOk();
    }

    public function test_me_reports_merged_roles_and_permissions(): void
    {
        $user = User::factory()->withRole(Role::Owner)->withRole(Role::Staff)->create();

        $response = $this->withToken($user->createToken('t')->plainTextToken)->getJson('/api/v1/me')->assertOk();

        $this->assertSame(['owner', 'staff'], $response->json('data.roles'));
        $this->assertSame([
            'properties.create',
            'properties.manage-own',
            'properties.moderate',
            'properties.view-unpublished',
        ], $response->json('data.permissions'));
    }
}
