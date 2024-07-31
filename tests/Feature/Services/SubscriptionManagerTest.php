<?php

namespace Tests\Feature\Services;

use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Plan;
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
        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'plan_id' => $plan->id,
        ])->save();

        $manager = app()->make(SubscriptionManager::class);

        $this->expectException(SubscriptionCreationNotAllowedException::class);
        $manager->create($slug, $user->id);
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


}
