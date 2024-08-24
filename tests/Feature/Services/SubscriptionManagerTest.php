<?php

namespace Tests\Feature\Services;

use App\Constants\SubscriptionStatus;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class SubscriptionManagerTest extends FeatureTest
{

    /**
     * @dataProvider nonDeadSubscriptionProvider
     */
    public function test_can_only_create_subscription_if_no_other_non_dead_subscription_exists($status)
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ])->save();

        /** @var SubscriptionManager $manager */
        $manager = app()->make(SubscriptionManager::class);

        $this->expectException(SubscriptionCreationNotAllowedException::class);
        $manager->create($slug, $user->id, 1, $tenant);
    }

    public static function nonDeadSubscriptionProvider()
    {
        return [
            'pending' => [
                'pending',
            ],
            'active' => [
                'active',
            ],
            'paused' => [
                'paused',
            ],
            'past_due' => [
                'past_due',
            ],
        ];
    }

    public function test_create_subscription_in_case_new_subscription_exists()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $planPrice = PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'price' => 100,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::NEW->value,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ])->save();

        /** @var SubscriptionManager $manager */
        $manager = app()->make(SubscriptionManager::class);

        $subscription = $manager->create($slug, $user->id, 1, $tenant);

        $this->assertNotNull($subscription);
    }
}
