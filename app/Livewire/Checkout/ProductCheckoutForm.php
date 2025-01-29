<?php

namespace App\Livewire\Checkout;

use App\Exceptions\LoginException;
use App\Services\CalculationManager;
use App\Services\CheckoutManager;
use App\Services\DiscountManager;
use App\Services\LoginManager;
use App\Services\OneTimeProductManager;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\SessionManager;
use App\Services\UserManager;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class ProductCheckoutForm extends CheckoutForm
{
    private OneTimeProductManager $productManager;

    private SessionManager $sessionManager;

    private CalculationManager $calculationManager;

    private OneTimeProductManager $oneTimeProductManager;

    public function boot(
        OneTimeProductManager $productManager,
        SessionManager $sessionManager,
        CalculationManager $calculationManager,
        OneTimeProductManager $oneTimeProductManager,
    ) {
        $this->productManager = $productManager;
        $this->sessionManager = $sessionManager;
        $this->calculationManager = $calculationManager;
        $this->oneTimeProductManager = $oneTimeProductManager;
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        CheckoutManager $checkoutManager,
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

        $cartDto = $this->sessionManager->getCartDto();

        $order = $checkoutManager->initProductCheckout($cartDto);

        $cartDto->orderId = $order->id;

        $paymentProvider = $paymentManager->getPaymentProviderBySlug(
            $this->paymentProvider
        );

        $discount = null;
        if ($cartDto->discountCode !== null) {
            $discount = $discountManager->getActiveDiscountByCode($cartDto->discountCode);
            $product = $this->oneTimeProductManager->getOneTimeProductById($cartDto->items[0]->productId);

            if (! $discountManager->isCodeRedeemableForOneTimeProduct($cartDto->discountCode, auth()->user(), $product)) {
                // this is to handle the case when user adds discount code that has max redemption limit per customer,
                // then logs-in during the checkout process and the discount code is not valid anymore
                $cartDto->discountCode = null;
                $discount = null;
                $this->dispatch('calculations-updated')->to(ProductTotals::class);
            }
        }

        $initData = $paymentProvider->initProductCheckout($order, $discount);

        $this->sessionManager->saveCartDto($cartDto);

        $user = auth()->user();

        if ($paymentProvider->isRedirectProvider()) {
            $link = $paymentProvider->createProductCheckoutRedirectLink(
                $order,
                $discount,
            );

            return redirect()->away($link);
        }

        $this->dispatch('start-overlay-checkout',
            paymentProvider: $paymentProvider->getSlug(),
            initData: $initData,
            successUrl: route('checkout.product.success'),
            email: $user->email,
            orderUuid: $order->uuid,
        );
    }

    public function render(PaymentManager $paymentManager)
    {
        $cartDto = $this->sessionManager->getCartDto();

        $product = $this->productManager->getOneTimeProductById($cartDto->items[0]->productId);

        $totals = $this->calculationManager->calculateCartTotals($cartDto, auth()->user());

        return view('livewire.checkout.product-checkout-form', [
            'product' => $product,
            'cartDto' => $cartDto,
            'successUrl' => route('checkout.product.success'),
            'userExists' => $this->userExists($this->email),
            'paymentProviders' => $this->getPaymentProviders($paymentManager),
            'totals' => $totals,
        ]);
    }
}
