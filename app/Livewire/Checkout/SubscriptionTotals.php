<?php

namespace App\Livewire\Checkout;

use App\Dto\SubscriptionCheckoutDto;
use App\Dto\TotalsDto;
use App\Models\Plan;
use App\Services\CalculationManager;
use App\Services\DiscountManager;
use App\Services\SessionManager;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionTotals extends Component
{
    public $page;

    public $planSlug;

    public $planHasTrial = false;

    public $isTrailSkipped = false;

    public $subtotal;

    public $discountAmount;

    public $amountDue;

    public $currencyCode;

    public $code;

    public ?string $unitMeterName;
    public ?string $planPriceType = null;
    public ?string $pricePerUnit = null;
    public ?array $tiers = null;
    public bool $canAddDiscount = true;

    private DiscountManager $discountManager;

    private CalculationManager $calculationManager;
    private SessionManager $sessionManager;

    public function boot(DiscountManager $discountManager, CalculationManager $calculationManager, SessionManager $sessionManager)
    {
        $this->discountManager = $discountManager;
        $this->calculationManager = $calculationManager;
        $this->sessionManager = $sessionManager;
    }

    public function mount(TotalsDto $totals, Plan $plan, $page, bool $canAddDiscount = true, bool $isTrailSkipped = false)
    {
        $this->page = $page;
        $this->planSlug = $plan->slug;
        $this->planHasTrial = $plan->has_trial;
        $this->isTrailSkipped = $isTrailSkipped;
        $this->subtotal = $totals->subtotal;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;
        $this->unitMeterName = $plan->meter?->name;
        $this->planPriceType = $totals->planPriceType;
        $this->pricePerUnit = $totals->pricePerUnit;
        $this->tiers = $totals->tiers;
        $this->canAddDiscount = $canAddDiscount;
    }

    public function getCodeFromSession(): ?string
    {
        return $this->sessionManager->getSubscriptionCheckoutDto()->discountCode;
    }

    public function add()
    {
        $code = $this->code;

        if ($code === null) {
            session()->flash('error', __('Please enter a discount code.'));

            return;
        }

        $plan = Plan::where('slug', $this->planSlug)->where('is_active', true)->firstOrFail();

        $isRedeemable = $this->discountManager->isCodeRedeemableForPlan($code, auth()->user(), $plan);

        if (! $isRedeemable) {
            session()->flash('error', __('This discount code is invalid.'));

            return;
        }

        /** @var SubscriptionCheckoutDto $subscriptionCheckoutDto */
        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();

        $subscriptionCheckoutDto->discountCode = $code;
        $subscriptionCheckoutDto->planSlug = $this->planSlug;

        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->updateTotals();

        session()->flash('success', __('The discount code has been applied.'));
    }

    public function remove()
    {
        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();

        $subscriptionCheckoutDto->discountCode = null;

        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        session()->flash('success', __('The discount code has been removed.'));

        $this->updateTotals();
    }

    #[On('calculations-updated')]
    public function updateTotals()
    {
        $totals = $this->calculationManager->calculatePlanTotals(
            auth()->user(),
            $this->planSlug,
            $this->getCodeFromSession(),
        );

        $this->subtotal = $totals->subtotal;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;
    }

    public function render()
    {
        return view('livewire.checkout.subscription-totals', [
            'addedCode' => $this->getCodeFromSession(),
        ]);
    }
}
