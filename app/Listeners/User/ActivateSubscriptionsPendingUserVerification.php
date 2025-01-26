<?php

namespace App\Listeners\User;

use App\Events\User\UserPhoneVerified;
use App\Services\SubscriptionManager;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActivateSubscriptionsPendingUserVerification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private SubscriptionManager $subscriptionManager
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserPhoneVerified $event): void
    {
        $this->subscriptionManager->activateSubscriptionsPendingUserVerification($event->user);
    }
}
