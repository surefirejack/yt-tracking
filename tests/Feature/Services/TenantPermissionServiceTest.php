<?php

namespace Tests\Feature\Services;

use App\Constants\TenancyPermissionConstants;
use App\Models\Permission;
use App\Models\Role;
use App\Services\TenantPermissionService;
use Tests\Feature\FeatureTest;

class TenantPermissionServiceTest extends FeatureTest
{
    public function test_tenant_user_has_permission(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS]);

        $this->actingAs($user);

        $tenantPermissionService = new TenantPermissionService;

        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS));
        $this->assertFalse($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_VIEW_SUBSCRIPTIONS));
    }

    public function test_tenant_assign_role(): void
    {
        $role = Role::findOrCreate('tenancy: test role');
        $permission = Permission::findOrCreate('tenancy: test permission');
        $role->givePermissionTo([$permission]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $tenantPermissionService = new TenantPermissionService;

        $tenantPermissionService->assignTenantUserRole($tenant, $user, $role->name);

        $tenantRoles = $tenantPermissionService->getTenantUserRoles($tenant, $user);

        $this->assertContains($role->name, $tenantRoles);
        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, 'tenancy: test permission'));
    }

    public function test_tenant_remove_all_role(): void
    {
        $role = Role::findOrCreate('tenancy: test role');
        $permission = Permission::findOrCreate('tenancy: test permission');
        $role->givePermissionTo([$permission]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $tenantPermissionService = new TenantPermissionService;

        $tenantPermissionService->assignTenantUserRole($tenant, $user, $role->name);

        $tenantPermissionService->removeAllTenantUserRoles($tenant, $user);

        $tenantRoles = $tenantPermissionService->getTenantUserRoles($tenant, $user);

        $this->assertEmpty($tenantRoles);
        $this->assertFalse($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, 'tenancy: test permission'));
    }
}
