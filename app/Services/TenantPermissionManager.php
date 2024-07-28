<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class TenantPermissionManager
{
    private static $permissionCache = [];

    public function tenantUserHasPermissionTo(Tenant $tenant, User $user, string $permission): bool
    {
        if ($tenant->id != Filament::getTenant()->id) {
            return false;
        }

        // we need some kind of cache because filament calls this method multiple times
        // you need to find another way to cache this if you want to use FrankenPhp to avoid memory leaks
        $cacheKey = implode('-', [$tenant->id, $user->id, $permission]);

        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }

        // permissions are assigned to the TenantUser pivot table
        $result = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot?->hasPermissionTo($permission) ?? false;

        self::$permissionCache[$cacheKey] = $result;

        return $result;
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
