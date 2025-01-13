<?php

namespace App\Livewire\Checkout;

use App\Exceptions\LoginException;
use App\Exceptions\NoPaymentProvidersAvailableException;
use App\Services\CalculationManager;
use App\Services\DiscountManager;
use App\Services\LoginManager;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\PlanManager;
use App\Services\SessionManager;
use App\Services\SubscriptionManager;
use App\Services\TenantSubscriptionManager;
use App\Services\UserManager;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class ConvertLocalSubscriptionCheckoutForm extends CheckoutForm
{
    private PlanManager $planManager;

    private SessionManager $sessionManager;

    private CalculationManager $calculationManager;

    private SubscriptionManager $subscriptionManager;

    private TenantSubscriptionManager $tenantSubscriptionManager;

    public function boot(
        PlanManager $planManager,
        SessionManager $sessionManager,
        CalculationManager $calculationManager,
        SubscriptionManager $subscriptionManager,
        TenantSubscriptionManager $tenantSubscriptionManager
    ) {
        $this->planManager = $planManager;
        $this->sessionManager = $sessionManager;
        $this->calculationManager = $calculationManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->tenantSubscriptionManager = $tenantSubscriptionManager;
    }

    public function render(PaymentManager $paymentManager)
    {
        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planManager->getActivePlanBySlug($planSlug);

        $totals = $this->calculationManager->calculatePlanTotals(
            auth()->user(),
            $planSlug,
            $subscriptionCheckoutDto?->discountCode,
            $subscriptionCheckoutDto->quantity,
        );

        return view('livewire.checkout.convert-local-subscription-checkout-form', [
            'plan' => $plan,
            'totals' => $totals,
            'userExists' => $this->userExists($this->email),
            'paymentProviders' => $this->getPaymentProviders($paymentManager),
            'isTenantPickerEnabled' => false,
        ]);
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        PaymentManager $paymentManager,
        DiscountManager $discountManager,
        UserManager $userManager,
        LoginManager $loginManager,
    ) {
        try {
            parent::handleLoginOrRegistration($loginValidator, $registerValidator, $userManager, $loginManager);
        } catch (LoginException $exception) { // 2fa is enabled, user has to go through typical login flow to enter 2fa code
            return redirect()->route('login');
        }

        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planManager->getActivePlanBySlug($planSlug);

        if ($plan === null) {
            return redirect()->route('home');
        }

        $paymentProvider = $paymentManager->getPaymentProviderBySlug(
            $this->paymentProvider
        );

        $user = auth()->user();

        $discount = null;
        if ($subscriptionCheckoutDto->discountCode !== null) {
            $discount = $discountManager->getActiveDiscountByCode($subscriptionCheckoutDto->discountCode);

            if (! $discountManager->isCodeRedeemableForPlan($subscriptionCheckoutDto->discountCode, $user, $plan)) {
                // this is to handle the case when user adds discount code that has max redemption limit per customer,
                // then logs-in during the checkout process and the discount code is not valid anymore
                $subscriptionCheckoutDto->discountCode = null;
                $discount = null;
                $this->dispatch('calculations-updated')->to(SubscriptionTotals::class);
            }
        }

        $subscription = $this->subscriptionManager->findById($subscriptionCheckoutDto->subscriptionId);

        if (! $subscription) {
            return redirect()->route('home');
        }

        if ($subscription->user_id !== $user->id) {
            return redirect()->route('home');
        }

        $quantity = max($subscriptionCheckoutDto->quantity, $this->tenantSubscriptionManager->calculateCurrentSubscriptionQuantity($subscription));

        $initData = $paymentProvider->initSubscriptionCheckout($plan, $subscription, $discount, $quantity);

        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        if ($paymentProvider->isRedirectProvider()) {
            $link = $paymentProvider->createSubscriptionCheckoutRedirectLink(
                $plan,
                $subscription,
                $discount,
                $quantity,
            );

            return redirect()->away($link);
        }

        $this->dispatch('start-overlay-checkout',
            paymentProvider: $paymentProvider->getSlug(),
            initData: $initData,
            successUrl: route('checkout.subscription.success'),
            email: $user->email,
            subscriptionUuid: $subscription->uuid,
        );
    }

    protected function getPaymentProviders(PaymentManager $paymentManager)
    {
        if (count($this->paymentProviders) > 0) {
            return $this->paymentProviders;
        }

        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planManager->getActivePlanBySlug($planSlug);

        $this->paymentProviders = $paymentManager->getActivePaymentProvidersForPlan($plan, true);

        if (empty($this->paymentProviders)) {
            logger()->error('No payment providers available for plan', [
                'plan' => $plan->slug,
            ]);

            throw new NoPaymentProvidersAvailableException('No payment providers available for plan'.$plan->slug);
        }

        if ($this->paymentProvider === null) {
            $this->paymentProvider = $this->paymentProviders[0]->getSlug();
        }

        return $this->paymentProviders;
    }
}
