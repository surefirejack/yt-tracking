<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class LinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all tenants
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please seed tenants first.');
            return;
        }

        $sampleUrls = [
            'https://google.com',
            'https://github.com',
            'https://stackoverflow.com',
            'https://laravel.com',
            'https://filamentphp.com',
            'https://tailwindcss.com',
            'https://youtube.com',
            'https://twitter.com',
            'https://facebook.com',
            'https://linkedin.com',
            'https://instagram.com',
            'https://reddit.com',
            'https://medium.com',
            'https://dev.to',
            'https://hackernews.com',
        ];

        $statuses = ['pending', 'processing', 'completed', 'failed'];
        $domains = ['dub.sh', 'short.ly', 'bit.ly'];

        foreach ($tenants as $tenant) {
            // Create 10-20 links per tenant
            $linkCount = $faker->numberBetween(10, 20);
            
            for ($i = 0; $i < $linkCount; $i++) {
                $status = $faker->randomElement($statuses);
                $originalUrl = $faker->randomElement($sampleUrls);
                $domain = $faker->randomElement($domains);
                $key = $faker->lexify('???????'); // 7 random letters
                
                $linkData = [
                    'tenant_id' => $tenant->id,
                    'original_url' => $originalUrl,
                    'status' => $status,
                ];

                // If status is completed, add Dub API response data
                if ($status === 'completed') {
                    $linkData = array_merge($linkData, [
                        'dub_id' => 'link_' . strtoupper($faker->bothify('?#?#?#?#?#?#?#?#?#?#?#?#?#')),
                        'domain' => $domain,
                        'key' => $key,
                        'url' => $originalUrl,
                        'short_link' => "https://{$domain}/{$key}",
                        'archived' => $faker->boolean(10), // 10% chance of being archived
                        'expires_at' => $faker->optional(0.2)->dateTimeBetween('now', '+1 year'), // 20% chance of expiry
                        'track_conversion' => $faker->boolean(30), // 30% chance
                        'proxy' => $faker->boolean(20), // 20% chance
                        'title' => $faker->optional(0.7)->sentence(3), // 70% chance of having title
                        'description' => $faker->optional(0.5)->sentence(10), // 50% chance of description
                        'utm_source' => $faker->optional(0.3)->word(),
                        'utm_medium' => $faker->optional(0.3)->randomElement(['email', 'social', 'cpc', 'organic']),
                        'utm_campaign' => $faker->optional(0.3)->words(2, true),
                        'rewrite' => $faker->boolean(15), // 15% chance
                        'do_index' => $faker->boolean(80), // 80% chance
                        'user_id_dub' => 'user_' . strtoupper($faker->bothify('?#?#?#?#?#?#?#?#?#?#?#?#?#')),
                        'project_id' => 'ws_' . strtoupper($faker->bothify('?#?#?#?#?#?#?#?#?#?#?#?#?#')),
                        'public_stats' => $faker->boolean(40), // 40% chance
                        'clicks' => $faker->numberBetween(0, 1000),
                        'last_clicked' => $faker->optional(0.6)->dateTimeBetween('-30 days', 'now'),
                        'leads' => $faker->numberBetween(0, 50),
                        'sales' => $faker->numberBetween(0, 20),
                        'sale_amount' => $faker->randomFloat(2, 0, 1000),
                        'comments' => $faker->optional(0.2)->sentence(),
                        'tags' => $faker->optional(0.4)->randomElements(['marketing', 'social', 'email', 'campaign', 'test'], $faker->numberBetween(1, 3)),
                        'qr_code' => "https://api.dub.co/qr?url=https://{$domain}/{$key}?qr=1",
                        'workspace_id' => 'ws_' . strtoupper($faker->bothify('?#?#?#?#?#?#?#?#?#?#?#?#?#')),
                    ]);
                } elseif ($status === 'failed') {
                    $linkData['error_message'] = $faker->randomElement([
                        'Invalid URL provided',
                        'API rate limit exceeded',
                        'Domain not allowed',
                        'Network timeout',
                        'Authentication failed'
                    ]);
                }

                Link::create($linkData);
            }
        }

        $this->command->info('Links seeded successfully!');
    }
}
