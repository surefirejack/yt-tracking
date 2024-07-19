<?php

namespace App\Services;

use App\Constants\SubscriptionStatus;
use App\Constants\TenancyPermissionConstants;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class TenantCreationManager
{
    public function __construct(
        private TenantPermissionManager $tenantPermissionManager
    ) {

    }

    public function findUserTenantsForNewOrder(?User $user)
    {
        if ($user === null) {
            return collect();
        }

        return $this->tenantPermissionManager->filterTenantsWhereUserHasPermission(
            $user->tenants()->get(),
            TenancyPermissionConstants::PERMISSION_CREATE_ORDERS
        );
    }

    public function findUserTenantForNewOrderByUuid(User $user, ?string $tenantUuid): ?Tenant
    {
        if ($tenantUuid === null) {
            return null;
        }

        return $this->tenantPermissionManager->filterTenantsWhereUserHasPermission(
            $user->tenants()->where('uuid', $tenantUuid)->get(),
            TenancyPermissionConstants::PERMISSION_CREATE_ORDERS
        )->first();
    }

    public function findUserTenantsForNewSubscription(?User $user)
    {
        if ($user === null) {
            return collect();
        }

        // where doesn't have any subscriptions with status other than New
        return $this->tenantPermissionManager->filterTenantsWhereUserHasPermission(
            $user->tenants()->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', '!=', SubscriptionStatus::NEW->value);
            })->get(),
            TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS
        );
//        // get a tenant that doesn't have a subscription and also doesn't have any other users (except the current user)
//        return $user->tenants()->whereDoesntHave('subscriptions')->whereDoesntHave('users', function ($query) use ($user) {
//            $query->where('users.id', '!=', $user->id);
//        })->get();
    }

    public function findUserTenantForNewSubscriptionByUuid(User $user, ?string $tenantUuid): ?Tenant
    {
        if ($tenantUuid === null) {
            return null;
        }

        return $this->tenantPermissionManager->filterTenantsWhereUserHasPermission(
            $user->tenants()->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', '!=', SubscriptionStatus::NEW->value);
            })->where('uuid', $tenantUuid)->get(),
            TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS
        )->first();
    }

//    public function getOrCreateTenantForNewUserSubscription(User $user)
//    {
//        // get a tenant that doesn't have a subscription and also doesn't have any other users (except the current user)
//        $tenant = $user->tenants()->whereDoesntHave('subscriptions')->whereDoesntHave('users', function ($query) use ($user) {
//            $query->where('users.id', '!=', $user->id);
//        })->first();
//
//        if ($tenant === null) {
//            $tenant = $this->createTenant($user);
//        }
//
//        return $tenant;
//    }

//    public function getOrCreateTenantForUserOrder(User $user)
//    {
//        // get a tenant that doesn't have an order and also doesn't have any other users (except the current user)
//        $tenant = $user->tenants()->whereDoesntHave('orders')->whereDoesntHave('users', function ($query) use ($user) {
//            $query->where('users.id', '!=', $user->id);
//        })->first();
//
//        if ($tenant === null) {
//            $tenant = $this->createTenant($user);
//        }
//
//        return $tenant;
//    }

    public function createTenant(User $user)
    {
        // add an enumeration to the name to avoid name conflicts

        $latestUserTenant = $user->tenants()->latest()->first();

        $number = 1;
        if ($latestUserTenant) {
            $parts = explode('#', $latestUserTenant->name);
            if (count($parts) > 1) {
                $number = $parts[count($parts) - 1];
                $number = (int) $number + 1;
            }
        }

        $name = $user->name.' '.__('workspace'); // todo: maybe put this in a config file

        $name .= ' #'.$number;

        $tenant = Tenant::create([
            'name' => $name,
            'uuid' => (string) Str::uuid(),
            'is_name_auto_generated' => true,
        ]);

        $tenant->users()->attach($user);

        $this->tenantPermissionManager->assignTenantUserRole($tenant, $user, TenancyPermissionConstants::TENANT_CREATOR_ROLE);

        return $tenant;
    }
}
