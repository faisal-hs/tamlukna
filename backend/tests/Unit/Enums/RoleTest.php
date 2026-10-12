<?php

namespace Tests\Unit\Enums;

use App\Enums\Permission;
use App\Enums\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function test_only_low_privilege_roles_are_self_assignable(): void
    {
        $this->assertSame(['buyer', 'renter', 'owner'], Role::selfAssignableValues());
    }

    public function test_admin_has_every_permission(): void
    {
        $this->assertSame(Permission::cases(), Role::Admin->permissions());
    }

    public function test_buyers_and_renters_have_no_special_permissions(): void
    {
        $this->assertSame([], Role::Buyer->permissions());
        $this->assertSame([], Role::Renter->permissions());
    }

    public function test_staff_can_moderate_but_not_administer(): void
    {
        $permissions = Role::Staff->permissions();

        $this->assertContains(Permission::PropertiesModerate, $permissions);
        $this->assertNotContains(Permission::PropertiesManageAny, $permissions);
        $this->assertNotContains(Permission::RolesManage, $permissions);
        $this->assertNotContains(Permission::UsersManage, $permissions);
    }
}
