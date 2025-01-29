<?php

namespace App\Livewire\Checkout;

use App\Models\Plan;
use App\Services\PlanManager;
use App\Services\SessionManager;
use App\Services\SubscriptionManager;
use App\Services\TenantSubscriptionManager;
use Livewire\Component;

class SubscriptionSeats extends Component
{
    public $planType;

    public $quantity;

    public $planSlug;

    public $maxQuantity;

    private SessionManager $sessionManager;

    private PlanManager $planManager;

    private SubscriptionManager $subscriptionManager;

    private TenantSubscriptionManager $tenantSubscriptionManager;

    public function boot(
        SessionManager $sessionManager,
        PlanManager $planManager,
        SubscriptionManager $subscriptionManager,
        TenantSubscriptionManager $tenantSubscriptionManager
    ) {
        $this->sessionManager = $sessionManager;
        $this->planManager = $planManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->tenantSubscriptionManager = $tenantSubscriptionManager;
    }

    public function mount(Plan $plan)
    {
        $this->planType = $plan->type;
        $this->planSlug = $plan->slug;
        $this->quantity = $this->sessionManager->getSubscriptionCheckoutDto()->quantity;
        $this->maxQuantity = $plan->max_users_per_tenant;
    }

    public function updatedQuantity(int $value)
    {
        $plan = $this->planManager->getActivePlanBySlug($this->planSlug);

        $maxRule = '';
        if ($plan->max_users_per_tenant > 0) {
            $maxRule = '|max:'.$plan->max_users_per_tenant;
        }

        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();

        $min = 1;

        if ($subscriptionCheckoutDto->subscriptionId !== null) {
            $subscription = $this->subscriptionManager->findById($subscriptionCheckoutDto->subscriptionId);
            $min = $this->tenantSubscriptionManager->calculateCurrentSubscriptionQuantity($subscription);
        }

        $this->validate([
            'quantity' => 'required|integer|min:'.$min.$maxRule,
        ]);

        $subscriptionCheckoutDto->quantity = $value;
        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->dispatch('calculations-updated')->to(SubscriptionTotals::class);
    }

    public function render()
    {
        return view('livewire.checkout.subscription-seats');
    }
}
