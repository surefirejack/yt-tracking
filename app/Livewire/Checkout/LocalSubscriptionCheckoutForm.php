<?php

namespace App\Livewire\Checkout;

use App\Exceptions\LoginException;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Services\CalculationManager;
use App\Services\CheckoutManager;
use App\Services\LoginManager;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\PlanManager;
use App\Services\SessionManager;
use App\Services\UserManager;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class LocalSubscriptionCheckoutForm extends CheckoutForm
{
    private PlanManager $planManager;

    private SessionManager $sessionManager;

    private CalculationManager $calculationManager;

    public function boot(
        PlanManager $planManager,
        SessionManager $sessionManager,
        CalculationManager $calculationManager,
    ) {
        $this->planManager = $planManager;
        $this->sessionManager = $sessionManager;
        $this->calculationManager = $calculationManager;
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

        return view('livewire.checkout.local-subscription-checkout-form', [
            'userExists' => $this->userExists($this->email),
            'plan' => $plan,
            'totals' => $totals,
        ]);
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        CheckoutManager $checkoutManager,
        UserManager $userManager,
        LoginManager $loginManager,
    ) {
        if (! config('app.trial_without_payment.enabled')) {
            return redirect()->route('home');
        }

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

        try {
            $subscription = $checkoutManager->initLocalSubscriptionCheckout($planSlug, $subscriptionCheckoutDto->tenantUuid, $subscriptionCheckoutDto->quantity);
        } catch (SubscriptionCreationNotAllowedException $e) {
            return redirect()->route('checkout.subscription.already-subscribed');
        }

        $subscriptionCheckoutDto->subscriptionId = $subscription->id;
        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->redirect(route('checkout.subscription.success'));
    }
}
