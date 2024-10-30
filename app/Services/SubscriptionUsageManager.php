<?php

namespace App\Services;

use App\Constants\PlanType;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Services\PaymentProviders\PaymentManager;
use Filament\Facades\Filament;

class SubscriptionUsageManager
{
    public function __construct(
        private PaymentManager $paymentManager,
        private SubscriptionManager $subscriptionManager,
    ) {}

    public function reportUsage(int $unitCount, ?Subscription $subscription = null): bool
    {
        $subscription = $subscription ?? $this->subscriptionManager->findActiveTenantSubscriptionWithPlanType(PlanType::USAGE_BASED, Filament::getTenant());

        if (! $subscription) {
            return false;
        }

        if ($subscription->plan->type !== PlanType::USAGE_BASED->value) {
            return false;
        }

        $paymentProvider = $this->paymentManager->getPaymentProviderBySlug(
            $subscription->paymentProvider->slug
        );

        $result = $paymentProvider->reportUsage($subscription, $unitCount);

        if ($result) {
            SubscriptionUsage::create([
                'subscription_id' => $subscription->id,
                'unit_count' => $unitCount,
            ]);
        }

        return $result;
    }
}
