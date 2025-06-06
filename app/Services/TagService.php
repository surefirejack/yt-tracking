<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TagService
{
    /**
     * Create a tag via Dub API and store locally
     */
    public function createTag(string $name, int $tenantId): ?Tag
    {
        try {
            // Check if tag already exists locally
            $existingTag = Tag::where('tenant_id', $tenantId)
                ->where('name', $name)
                ->first();

            if ($existingTag) {
                return $existingTag;
            }

            // Get Dub API configuration
            $apiKey = config('services.dub.api_key');
            $createTagUrl = config('services.dub.create_tag_url');

            if (!$apiKey || !$createTagUrl) {
                Log::warning('Dub API configuration missing for tag creation', [
                    'tenant_id' => $tenantId,
                    'tag_name' => $name,
                ]);
                
                // Create tag locally without dub_id if API is not configured
                return Tag::create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'color' => 'red', // Default color
                ]);
            }

            // Prepare the payload
            $payload = [
                'name' => $name,
            ];

            // Make the API call to Dub to create the tag
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($createTagUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Create the tag locally with Dub API response data
                $tag = Tag::create([
                    'tenant_id' => $tenantId,
                    'dub_id' => $data['id'] ?? null,
                    'name' => $data['name'] ?? $name,
                    'color' => $data['color'] ?? 'red',
                ]);

                Log::info('Tag created successfully via Dub API', [
                    'tag_id' => $tag->id,
                    'tenant_id' => $tenantId,
                    'dub_id' => $tag->dub_id,
                    'name' => $tag->name,
                ]);

                return $tag;
            } else {
                Log::error('Failed to create tag via Dub API', [
                    'tenant_id' => $tenantId,
                    'tag_name' => $name,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                // Create tag locally without dub_id if API call fails
                return Tag::create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'color' => 'red', // Default color
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while creating tag', [
                'tenant_id' => $tenantId,
                'tag_name' => $name,
                'error' => $e->getMessage(),
            ]);

            // Create tag locally without dub_id if exception occurs
            return Tag::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'color' => 'red', // Default color
            ]);
        }
    }

    /**
     * Get or create tags by names and return Tag model instances
     */
    public function getOrCreateTags(array $tagNames, int $tenantId): array
    {
        $tags = [];

        foreach ($tagNames as $tagName) {
            if (empty(trim($tagName))) {
                continue;
            }

            $tag = $this->createTag(trim($tagName), $tenantId);
            if ($tag) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
} 