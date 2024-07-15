<?php

namespace App\Services;

use App\Constants\OrderStatus;
use App\Constants\PlanType;
use App\Dto\CartDto;

class CheckoutManager
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private OrderManager $orderManager,
        private TenantCreationManager $tenantCreationManager,
    ) {

    }

    public function initSubscriptionCheckout(string $planSlug, ?string $tenantUuid, int $quantity = 1)
    {
        $tenant = $this->tenantCreationManager->findUserTenantForNewSubscriptionByUuid(auth()->user(), $tenantUuid);

        if ($tenant === null) {
            $tenant = $this->tenantCreationManager->createTenant(auth()->user());
        }

        $subscription = $this->subscriptionManager->findNewByPlanSlugAndTenant($planSlug, $tenant);
        if ($subscription === null) {
            $subscription = $this->subscriptionManager->create(
                planSlug: $planSlug,
                userId: auth()->id(),
                paymentProvider: null,
                paymentProviderSubscriptionId: null,
                quantity: $quantity,
                tenant: $tenant,
            );
        }

        $plan = $subscription->plan;

        if ($plan->type === PlanType::SEAT_BASED->value) { // in case tenant already had users inside
            $subscription->update(['quantity' => max($tenant->users->count(), $quantity)]);
        }

        return $subscription;
    }

    public function initProductCheckout(CartDto $cartDto)
    {
        $user = auth()->user();

        $order = null;
        if ($cartDto->orderId !== null) {
            $order = $this->orderManager->findNewByIdForUser($cartDto->orderId, $user);
        }

        if ($order === null) {
            $order = $this->orderManager->create($user);
        }

        $this->orderManager->refreshOrder($cartDto, $order);

        $order->status = OrderStatus::PENDING->value;
        $order->save();

        return $order;
    }
}
