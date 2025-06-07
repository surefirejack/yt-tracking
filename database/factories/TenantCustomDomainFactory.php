<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantCustomDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenantCustomDomain>
 */
class TenantCustomDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'domain' => fake()->domainName(),
            'is_verified' => fake()->boolean(30), // 30% chance of being verified
            'is_primary' => false, // Will be set explicitly when needed
            'verified_at' => fake()->boolean(30) ? fake()->dateTimeBetween('-1 month', 'now') : null,
            'verification_token' => fake()->sha256(),
            'ssl_status' => fake()->randomElement(['pending', 'active', 'failed']),
            'ssl_data' => null,
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the domain is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'ssl_status' => 'active',
        ]);
    }

    /**
     * Indicate that the domain is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'ssl_status' => 'active',
        ]);
    }

    /**
     * Indicate that the domain is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
            'ssl_status' => 'pending',
        ]);
    }
}
