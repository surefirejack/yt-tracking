<?php

namespace App\Http\Controllers;

use App\Constants\SubscriptionType;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Plan;
use App\Services\CalculationManager;
use App\Services\DiscountManager;
use App\Services\SessionManager;
use App\Services\SubscriptionManager;

class SubscriptionCheckoutController extends Controller
{
    public function __construct(
        private DiscountManager $discountManager,
        private CalculationManager $calculationManager,
        private SubscriptionManager $subscriptionManager,
        private SessionManager $sessionManager,
    ) {}

    public function subscriptionCheckout(string $planSlug)
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();
        $checkoutDto = $this->sessionManager->getSubscriptionCheckoutDto();

        $user = auth()->user();

        if ($user && ! $this->subscriptionManager->canCreateSubscription($user->id)) {
            throw new SubscriptionCreationNotAllowedException(__('You already have subscription.'));
        }

        if ($checkoutDto->planSlug !== $planSlug) {
            $checkoutDto = $this->sessionManager->resetSubscriptionCheckoutDto();
        }

        $checkoutDto->planSlug = $planSlug;

        $this->sessionManager->saveSubscriptionCheckoutDto($checkoutDto);

        if ($plan->has_trial && config('app.trial_without_payment.enabled')) {
            return view('checkout.local-subscription');
        }

        return view('checkout.subscription');
    }

    public function convertLocalSubscriptionCheckout(?string $subscriptionUuid = null)
    {
        $subscription = $this->subscriptionManager->findByUuidOrFail($subscriptionUuid);

        if (! $this->subscriptionManager->isLocalSubscription($subscription)) {
            return redirect()->route('home');
        }

        $planSlug = $subscription->plan->slug;
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        $checkoutDto = $this->sessionManager->getSubscriptionCheckoutDto();

        $user = auth()->user();

        if ($user && ! $this->subscriptionManager->canCreateSubscription($user->id)) {
            throw new SubscriptionCreationNotAllowedException(__('You already have subscription.'));
        }

        if ($checkoutDto->planSlug !== $planSlug) {
            $checkoutDto = $this->sessionManager->resetSubscriptionCheckoutDto();
        }

        $checkoutDto->planSlug = $planSlug;
        $checkoutDto->subscriptionId = $subscription->id;

        $this->sessionManager->saveSubscriptionCheckoutDto($checkoutDto);

        $totals = $this->calculationManager->calculatePlanTotals(
            auth()->user(),
            $planSlug,
            $checkoutDto?->discountCode,
        );

        return view('checkout.convert-local-subscription', [
            'plan' => $plan,
            'totals' => $totals,
            'checkoutDto' => $checkoutDto,
        ]);
    }

    public function subscriptionCheckoutSuccess()
    {
        $result = $this->handleSubscriptionSuccess();

        if (! $result) {
            return redirect()->route('home');
        }

        $checkoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $subscription = $this->subscriptionManager->findById($checkoutDto->subscriptionId);

        $this->sessionManager->resetSubscriptionCheckoutDto();

        if ($subscription && $subscription->type === SubscriptionType::LOCALLY_MANAGED) {
            return view('checkout.local-subscription-thank-you');
        }

        return view('checkout.subscription-thank-you');
    }

    public function convertLocalSubscriptionCheckoutSuccess()
    {
        $result = $this->handleSubscriptionSuccess();

        if (! $result) {
            return redirect()->route('home');
        }

        $this->sessionManager->resetSubscriptionCheckoutDto();

        return view('checkout.convert-local-subscription-thank-you');
    }

    private function handleSubscriptionSuccess(): bool
    {
        $checkoutDto = $this->sessionManager->getSubscriptionCheckoutDto();

        if ($checkoutDto->subscriptionId === null) {
            return false;
        }

        $this->subscriptionManager->setAsPending($checkoutDto->subscriptionId);

        if ($checkoutDto->discountCode !== null) {
            $this->discountManager->redeemCodeForSubscription($checkoutDto->discountCode, auth()->user(), $checkoutDto->subscriptionId);
        }

        return true;
    }
}
