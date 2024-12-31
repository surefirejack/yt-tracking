<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SessionConstants;
use App\Constants\SubscriptionType;
use App\Dto\SubscriptionCheckoutDto;
use App\Exceptions\CouldNotCreateLocalSubscriptionException;
use App\Livewire\Checkout\LocalSubscriptionCheckoutForm;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class LocalSubscriptionCheckoutFormTest extends FeatureTest
{
    public function test_can_checkout_new_user()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';
        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('checkout.subscription.success'));

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        $latestSubscription = Subscription::latest()->first();
        $this->assertEquals($latestSubscription->type, SubscriptionType::LOCALLY_MANAGED);
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_checkout_existing_user()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'existing+sub1@gmail.com')
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('checkout.subscription.success'));

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        $latestSubscription = Subscription::latest()->first();
        $this->assertEquals($latestSubscription->type, SubscriptionType::LOCALLY_MANAGED);
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_not_checkout_when_plan_has_no_trial()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $this->expectException(CouldNotCreateLocalSubscriptionException::class);
        $this->expectExceptionMessage('Could not determine local subscription end date');

        $email = 'something+'.rand(1, 1000000).'@gmail.com';
        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout');
    }

    public function test_checkout_success_if_plan_type_is_usage_based()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $sessionDto = new SubscriptionCheckoutDto;
        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('checkout.subscription.success'));

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        $latestSubscription = Subscription::latest()->first();
        $this->assertEquals($latestSubscription->type, SubscriptionType::LOCALLY_MANAGED);
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_not_checkout_if_trial_without_payment_is_disabled()
    {
        config(['app.trial_without_payment.enabled' => false]);

        $sessionDto = new SubscriptionCheckoutDto;
        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('home'));

        // assert user has not been created
        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);

        // assert user is not logged in
        $this->assertGuest();
    }
}
