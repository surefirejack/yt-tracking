<?php

namespace App\Services;

use App\Constants\InvitationStatus;
use App\Constants\PlanType;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantManager
{
    public function __construct(
        private TenantPermissionManager $tenantPermissionManager,
        private TenantSubscriptionManager $tenantSubscriptionManager,
    ) {

    }

    public function acceptInvitation(Invitation $invitation, User $user): bool
    {
        // todo: make sure that the max_users limit of subscription is not exceeded (if any)
        // todo: for each seat-based subscription for this tenant, increase the quantity by 1 if the quantity of subscription is less than the number of users in the tenant
        // todo: send an email to the inviter that the invitation has been accepted
        // todo: send an email to the invited user that the invitation has been accepted

        if ($this->doTenantSubscriptionsAllowAddingUser($invitation->tenant) === false) {
            return false;
        }

        // todo: move that part to subscription service manager
        $tenantSubscriptions = $this->tenantSubscriptionManager->getTenantSubscriptions($invitation->tenant);
        $tenantUserCount = $invitation->tenant->users->count();
        $tenantLockKey = $this->getTenantLockName($invitation->tenant);

        $lock = Cache::lock($tenantLockKey, 30);

        try {
            if ($lock->block(30)) {  // use a lock to avoid race conditions
                foreach ($tenantSubscriptions as $subscription) {
                    if ($subscription->plan->type === PlanType::SEAT_BASED->value &&
                        $subscription->quantity < $tenantUserCount + 1
                    ) {
                        $result = $this->tenantSubscriptionManager->updateSubscriptionQuantity($subscription, $tenantUserCount + 1);

                        if ($result === false) {
                            return false;
                        }
                    }
                }

                DB::transaction(function () use ($invitation, $user) {
                    // add the user to the tenant
                    $invitation->tenant->users()->attach($user);

                    $user->tenants()->update(['is_default' => false]);

                    // set the default tenant for the user to this tenant
                    $user->tenants()->updateExistingPivot($invitation->tenant->id, ['is_default' => true]);

                    $roleName = $invitation->role;

                    if ($roleName) {
                        $this->tenantPermissionManager->assignTenantUserRole($invitation->tenant, $user, $roleName);
                    }

                    $invitation->update([
                        'status' => InvitationStatus::ACCEPTED,
                        'accepted_at' => now(),
                    ]);
                });
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return false;
        } finally {
            $lock?->release();
        }

        // todo: fire event UserJoinedTenant

        return true;
    }

    public function rejectInvitation(Invitation $invitation, User $user): bool
    {
        $invitation->update([
            'status' => InvitationStatus::REJECTED,
        ]);

        return true;
    }

    public function getUserInvitations(User $user)
    {
        return Invitation::where('email', $user->email)
            ->where('expires_at', '>=', now())
            ->where('status', InvitationStatus::PENDING->value)
            ->with('tenant')
            ->get();
    }

    public function getUserInvitationCount(User $user)
    {
        return Invitation::where('email', $user->email)
            ->where('expires_at', '>=', now())
            ->where('status', InvitationStatus::PENDING->value)
            ->count();
    }

    public function canRemoveUser(Tenant $tenant, User $user): bool
    {
        if (auth()->user()->is($user)) {
            return false;
        }

        if ($tenant->users->count() === 1) {
            return false;
        }

        return $tenant->users->contains($user);
    }

    public function removeUser(Tenant $tenant, User $user): bool
    {
        if (! $this->canRemoveUser($tenant, $user)) {
            return false;
        }

        $tenantLockKey = $this->getTenantLockName($tenant);
        $lock = Cache::lock($tenantLockKey, 30);
        $tenantSubscriptions = $this->tenantSubscriptionManager->getTenantSubscriptions($tenant);
        $tenantUserCount = $tenant->users->count();
        $isProrated = config('app.payment.proration_enabled', true);

        try {

            if ($lock->block(30)) {  // use a lock to avoid race conditions

                foreach ($tenantSubscriptions as $subscription) {
                    if ($subscription->plan->type === PlanType::SEAT_BASED->value &&
                        $subscription->quantity != $tenantUserCount - 1
                    ) {
                        $result = $this->tenantSubscriptionManager->updateSubscriptionQuantity($subscription, $tenantUserCount - 1);

                        if ($result === false) {
                            return false;
                        }
                    }
                }

                DB::transaction(function () use ($tenant, $user) {
                    $this->tenantPermissionManager->removeAllTenantUserRoles($tenant, $user);
                    $tenant->users()->detach($user);
                });
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return false;
        } finally {
            $lock?->release();
        }

        return true;
    }

    public function canInviteUser(Tenant $tenant, User $user): bool
    {
        // todo: check if invitations are enabled. If not, return false

        return $this->doTenantSubscriptionsAllowAddingUser($tenant);
    }

    public function getTenantByUuid(string $uuid): Tenant
    {
        return Tenant::where('uuid', $uuid)->firstOrFail();
    }

    private function doTenantSubscriptionsAllowAddingUser(Tenant $tenant): bool
    {
        $tenantSubscriptions = $tenant->subscriptions()->with('plan')->get();
        $tenantUserCount = $tenant->users->count();

        foreach ($tenantSubscriptions as $subscription) {
            if ($subscription->plan->type === PlanType::SEAT_BASED->value &&
                $subscription->plan->max_users_per_tenant !== 0 &&
                $tenantUserCount >= $subscription->plan->max_users_per_tenant) {
                return false;
            }
        }

        return true;
    }

    private function getTenantLockName(Tenant $tenant): string
    {
        return 'tenant_'.$tenant->id;
    }
}
