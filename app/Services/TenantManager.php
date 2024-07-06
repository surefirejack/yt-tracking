<?php

namespace App\Services;

use App\Constants\InvitationStatus;
use App\Constants\PlanType;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentProviders\PaymentManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    public function __construct(
        private PaymentManager $paymentManager
    ) {

    }
    // todo: when an email is invited, user should be able to see it in their dashboard (under invitations)
    // and be able to accept or reject it.
    // an email should be sent to the invited user with a link to accept the invitation

    public function acceptInvitation(Invitation $invitation, User $user): bool
    {
        // todo: make sure that the max_users limit of subscription is not exceeded (if any)
        // todo: for each seat-based subscription for this tenant, increase the quantity by 1 if the quantity of subscription is less than the number of users in the tenant
        // todo: send an email to the inviter that the invitation has been accepted
        // todo: send an email to the invited user that the invitation has been accepted

        if ($this->doTenantSubscriptionsAllowAddingUser($invitation->tenant) === false) {
            return false;
        }

        // todo: acquire a lock on the tenant to prevent race conditions: https://laravel.com/docs/11.x/cache#managing-locks

        // todo: move that part to subscription service manager
        $isProrated = config('app.payment.proration_enabled', true);
        $tenantSubscriptions = $invitation->tenant->subscriptions()->with('plan')->get();
        $tenantUserCount = $invitation->tenant->users->count();
        $tenantLockKey = $this->getTenantLockName($invitation->tenant);

        $lock = Cache::lock($tenantLockKey, 30);

        try {
            if ($lock->get()) {  // use a lock to avoid race conditions
                foreach ($tenantSubscriptions as $subscription) {
                    if ($subscription->plan->type === PlanType::SEAT_BASED->value &&
                        $subscription->quantity < $tenantUserCount + 1
                    ) {
                        $paymentProvider = $this->paymentManager->getPaymentProviderBySlug(
                            $subscription->paymentProvider->slug
                        );

                        $result = $paymentProvider->updateSubscriptionQuantity($subscription, $tenantUserCount + 1, $isProrated);

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

                    $invitation->update([
                        'status' => InvitationStatus::ACCEPTED,
                        'accepted_at' => now(),
                    ]);
                });
            }
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

        // todo: foreach tenant subscription, decrease the quantity by 1 if the quantity is greater than the number of users in the tenant
        $tenant->users()->detach($user);

        return true;
    }

    public function canInviteUser(Tenant $tenant, User $user): bool
    {
        // todo: check if invitations are enabled. If not, return false

        return $this->doTenantSubscriptionsAllowAddingUser($tenant);
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
