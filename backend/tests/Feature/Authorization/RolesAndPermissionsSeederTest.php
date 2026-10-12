<?php

namespace Tests\Feature\Authorization;

use App\Enums\Permission;
use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

final class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_matches_the_enum_definitions(): void
    {
        foreach (Permission::cases() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission->value, 'guard_name' => 'web']);
        }

        foreach (Role::cases() as $role) {
            $expected = array_map(fn (Permission $permission): string => $permission->value, $role->permissions());
            sort($expected);

            $actual = RoleModel::findByName($role->value, 'web')->permissions->pluck('name')->sort()->values()->all();

            $this->assertSame($expected, $actual, "Permission mismatch for role [{$role->value}]");
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $counts = fn (): array => [
            PermissionModel::query()->count(),
            RoleModel::query()->count(),
            DB::table('role_has_permissions')->count(),
        ];

        $before = $counts();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame($before, $counts());
    }
}
