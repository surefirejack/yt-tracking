<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class TenantPermissionManager
{
    public function tenantUserHasPermissionTo(Tenant $tenant, User $user, string $permission): bool
    {
        // permissions are assigned to the TenantUser pivot table
        return $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot?->hasPermissionTo($permission) ?? false;
    }

    public function filterTenantsWhereUserHasPermission(Collection $tenants, string $permission)
    {
        return $tenants->filter(function ($tenant) use ($permission) {
            return $tenant->pivot->hasPermissionTo($permission);
        });
    }

    public function getTenantUserRoles(Tenant $tenant, User $user): array
    {
        return $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot?->getRoleNames()->toArray() ?? [];
    }

    public function assignTenantUserRole(Tenant $tenant, User $user, string $role): void
    {
        $this->removeAllTenantUserRoles($tenant, $user);
        $user->tenants()->where('tenant_id', $tenant->id)->first()->pivot->assignRole($role);
    }

    public function removeAllTenantUserRoles(Tenant $tenant, User $user): void
    {
        $user->tenants()->where('tenant_id', $tenant->id)->first()->pivot->syncRoles([]);
    }
}
