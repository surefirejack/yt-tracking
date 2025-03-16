<?php

namespace App\Services\PaymentProviders;

use App\Models\PaymentProvider;
use App\Models\Plan;

class PaymentService
{
    private array $paymentProviders;

    public function __construct(PaymentProviderInterface ...$paymentProviders)
    {
        $this->paymentProviders = $paymentProviders;
    }

    public function getActivePaymentProviders(): array
    {
        $paymentProviderInterfaceMap = $this->getPaymentProviderInterfaceMap();

        $activePaymentProviders = $this->getActivePaymentProvidersFromDatabase();

        $paymentProviders = [];

        foreach ($activePaymentProviders as $activePaymentProvider) {
            if (isset($paymentProviderInterfaceMap[$activePaymentProvider->slug])) {
                $paymentProviders[] = $paymentProviderInterfaceMap[$activePaymentProvider->slug];
            }
        }

        return $paymentProviders;
    }

    public function getActivePaymentProvidersForPlan(Plan $plan, bool $shouldSupportSkippingTrial = false): array
    {
        $paymentProviderInterfaceMap = $this->getPaymentProviderInterfaceMap();

        $activePaymentProviders = $this->getActivePaymentProvidersFromDatabase();

        $paymentProviders = [];
        foreach ($activePaymentProviders as $paymentProvider) {
            if (isset($paymentProviderInterfaceMap[$paymentProvider->slug]) &&
                in_array($plan->type, $paymentProviderInterfaceMap[$paymentProvider->slug]->getSupportedPlanTypes())
            ) {
                $currentPaymentProvider = $paymentProviderInterfaceMap[$paymentProvider->slug];
                if ($plan->has_trial && $shouldSupportSkippingTrial) {
                    if ($currentPaymentProvider->supportsSkippingTrial()) {
                        $paymentProviders[] = $currentPaymentProvider;
                    }
                } else {
                    $paymentProviders[] = $currentPaymentProvider;
                }
            }
        }

        return $paymentProviders;
    }

    public function getPaymentProviderBySlug(string $slug): PaymentProviderInterface
    {
        $paymentProviderInterfaceMap = $this->getPaymentProviderInterfaceMap();

        if (isset($paymentProviderInterfaceMap[$slug])) {
            return $paymentProviderInterfaceMap[$slug];
        }

        throw new \Exception('Payment provider not found: '.$slug);
    }

    private function getPaymentProviderInterfaceMap(): array
    {
        $paymentProviderInterfaceMap = [];

        foreach ($this->paymentProviders as $paymentProvider) {
            $paymentProviderInterfaceMap[$paymentProvider->getSlug()] = $paymentProvider;
        }

        return $paymentProviderInterfaceMap;
    }

    private function getActivePaymentProvidersFromDatabase()
    {
        return PaymentProvider::where('is_active', true)->orderBy('sort', 'asc')->get();
    }
}
