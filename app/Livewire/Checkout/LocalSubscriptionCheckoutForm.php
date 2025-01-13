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
use App\Services\SubscriptionManager;
use App\Services\UserManager;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class LocalSubscriptionCheckoutForm extends CheckoutForm
{
    private PlanManager $planManager;

    private SessionManager $sessionManager;

    private CalculationManager $calculationManager;

    private SubscriptionManager $subscriptionManager;

    public function boot(
        PlanManager $planManager,
        SessionManager $sessionManager,
        CalculationManager $calculationManager,
        SubscriptionManager $subscriptionManager,
    ) {
        $this->planManager = $planManager;
        $this->sessionManager = $sessionManager;
        $this->calculationManager = $calculationManager;
        $this->subscriptionManager = $subscriptionManager;
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

        if (! $this->subscriptionManager->canUserHaveSubscriptionTrial(auth()->user())) {
            return redirect()->route('home');
        }

        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planManager->getActivePlanBySlug($planSlug);

        if ($plan === null) {
            return redirect()->route('home');
        }

        try {
            $subscription = $checkoutManager->initLocalSubscriptionCheckout($planSlug);
        } catch (SubscriptionCreationNotAllowedException $e) {
            return redirect()->route('checkout.subscription.already-subscribed');
        }

        $subscriptionCheckoutDto->subscriptionId = $subscription->id;
        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->redirect(route('checkout.subscription.success'));
    }
}
