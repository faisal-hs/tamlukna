<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs App\Enums\Role / App\Enums\Permission into the database.
 * Idempotent: safe to run on every deployment.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * The User model's guard. API requests authenticate via Sanctum, but
     * permissions are resolved against the model's "web" guard.
     */
    private const GUARD = 'web';

    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, self::GUARD);
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, self::GUARD)->syncPermissions(
                array_map(fn (Permission $permission): string => $permission->value, $role->permissions())
            );
        }

        $registrar->forgetCachedPermissions();
    }
}
