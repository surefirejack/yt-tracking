<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class TenantCreationManager
{
    public function getOrCreateTenantForUserSubscription(User $user)
    {
        // get a tenant that doesn't have a subscription and also doesn't have any other users (except the current user)
        $tenant = $user->tenants()->whereDoesntHave('subscriptions')->whereDoesntHave('users', function ($query) use ($user) {
            $query->where('users.id', '!=', $user->id);
        })->first();

        if ($tenant === null) {
            $tenant = Tenant::create([
                'name' => $user->name.' '.__('workspace'),  // todo: maybe put this in a config file
                'uuid' => (string) Str::uuid(),
                'is_name_auto_generated' => true,
            ]);

            $tenant->users()->attach($user);
        }

        return $tenant;
    }

}
