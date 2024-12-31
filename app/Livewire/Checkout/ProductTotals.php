<?php

namespace App\Livewire\Checkout;

use App\Dto\CartDto;
use App\Dto\TotalsDto;
use App\Models\OneTimeProduct;
use App\Services\CalculationManager;
use App\Services\DiscountManager;
use App\Services\SessionManager;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductTotals extends Component
{
    public $page;

    public $subtotal;

    public $product;

    public $discountAmount;

    public $amountDue;

    public $currencyCode;

    public $code;

    private DiscountManager $discountManager;

    private CalculationManager $calculationManager;
    private SessionManager $sessionManager;

    public function boot(DiscountManager $discountManager, CalculationManager $calculationManager, SessionManager $sessionManager)
    {
        $this->discountManager = $discountManager;
        $this->calculationManager = $calculationManager;
        $this->sessionManager = $sessionManager;
    }

    public function mount(TotalsDto $totals, OneTimeProduct $product, $page)
    {
        $this->page = $page;
        $this->product = $product;
        $this->subtotal = $totals->subtotal;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;
    }

    private function getCartDto(): ?CartDto
    {
        return $this->sessionManager->getCartDto();
    }

    private function saveCartDto(CartDto $cartDto): void
    {
        $this->sessionManager->saveCartDto($cartDto);
    }

    public function add()
    {
        $code = $this->code;

        if ($code === null) {
            session()->flash('error', __('Please enter a discount code.'));

            return;
        }

        $isRedeemable = $this->discountManager->isCodeRedeemableForOneTimeProduct($code, auth()->user(), $this->product);

        if (! $isRedeemable) {
            session()->flash('error', __('This discount code is invalid.'));

            return;
        }

        $cartDto = $this->getCartDto();
        $cartDto->discountCode = $code;

        $this->saveCartDto($cartDto);

        $this->updateTotals();

        session()->flash('success', __('The discount code has been applied.'));
    }

    public function remove()
    {
        $cartDto = $this->getCartDto();
        $cartDto->discountCode = null;
        $this->saveCartDto($cartDto);

        session()->flash('success', __('The discount code has been removed.'));

        $this->updateTotals();
    }

    #[On('calculations-updated')]
    public function updateTotals()
    {
        $totals = $this->calculationManager->calculateCartTotals(
            $this->getCartDto(),
            auth()->user()
        );

        $this->subtotal = $totals->subtotal;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;
    }

    public function render()
    {
        return view('livewire.checkout.product-totals', [
            'addedCode' => $this->getCartDto()->discountCode,
        ]);
    }
}
