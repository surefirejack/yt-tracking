<?php

namespace App\Services\EmailServiceProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KitServiceProvider implements EmailServiceProviderInterface
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl = 'https://api.convertkit.com/v3';
    protected int $timeout = 30;
    protected int $retryAttempts = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function __construct(array $credentials)
    {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->apiSecret = $credentials['api_secret'] ?? '';
    }

    public function getSlug(): string
    {
        return 'kit';
    }

    public function getName(): string
    {
        return 'Kit (ConvertKit)';
    }

    public function checkSubscriber(string $email): array
    {
        try {
            // Check both active and inactive subscribers
            $activeResponse = $this->makeApiCall('GET', '/subscribers', [
                'email_address' => $email,
                'subscriber_state' => 'active'
            ]);

            $inactiveResponse = $this->makeApiCall('GET', '/subscribers', [
                'email_address' => $email,
                'subscriber_state' => 'inactive'
            ]);

            $subscriber = null;
            
            // Check active subscribers first
            if ($activeResponse['success'] && !empty($activeResponse['data']['subscribers'])) {
                $subscriber = $activeResponse['data']['subscribers'][0];
            }
            // Then check inactive subscribers
            elseif ($inactiveResponse['success'] && !empty($inactiveResponse['data']['subscribers'])) {
                $subscriber = $inactiveResponse['data']['subscribers'][0];
            }

            if ($subscriber) {
                return [
                    'is_subscribed' => true, // Consider both active and inactive as subscribed
                    'subscriber_id' => $subscriber['id'],
                    'tags' => $this->getSubscriberTags($subscriber['id']),
                    'created_at' => $subscriber['created_at'] ?? null,
                    'state' => $subscriber['state'],
                ];
            }

            return [
                'is_subscribed' => false,
                'subscriber_id' => null,
                'tags' => [],
            ];
        } catch (\Exception $e) {
            Log::error('Kit API checkSubscriber failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'is_subscribed' => false,
                'subscriber_id' => null,
                'tags' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getTags(): array
    {
        try {
            $cacheKey = 'kit_tags_' . hash('sha256', $this->apiKey);
            
            return Cache::remember($cacheKey, 300, function () { // 5 minute cache
                $response = $this->makeApiCall('GET', '/tags');

                if ($response['success']) {
                    return collect($response['data']['tags'] ?? [])
                        ->map(function ($tag) {
                            return [
                                'id' => (string) $tag['id'],
                                'name' => $tag['name'],
                                'created_at' => $tag['created_at'] ?? null,
                            ];
                        })
                        ->toArray();
                }

                return [];
            });
        } catch (\Exception $e) {
            Log::error('Kit API getTags failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getForms(): array
    {
        try {
            $cacheKey = 'kit_forms_' . hash('sha256', $this->apiKey);
            
            return Cache::remember($cacheKey, 300, function () { // 5 minute cache
                $response = $this->makeApiCall('GET', '/forms');

                if ($response['success']) {
                    return collect($response['data']['forms'] ?? [])
                        ->map(function ($form) {
                            return [
                                'id' => (string) $form['id'],
                                'name' => $form['name'],
                                'created_at' => $form['created_at'] ?? null,
                            ];
                        })
                        ->toArray();
                }

                return [];
            });
        } catch (\Exception $e) {
            Log::error('Kit API getForms failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function addSubscriber(string $email, array $tags = []): array
    {
        try {
            // In Kit API v3, we need to add subscribers to a form, not directly
            // Let's get the first available form and use it
            $forms = $this->getForms();
            
            if (empty($forms)) {
                return [
                    'success' => false,
                    'error' => 'No forms available to add subscriber to'
                ];
            }

            // Use the first form to add the subscriber
            $formId = $forms[0]['id'];
            
            $data = [
                'email' => $email,
            ];

            // Add tags if provided
            if (!empty($tags)) {
                $data['tags'] = $tags;
            }

            $response = $this->makeApiCall('POST', "/forms/{$formId}/subscribe", $data);

            if ($response['success']) {
                $subscription = $response['data']['subscription'];
                $subscriberId = $subscription['subscriber']['id'];
                
                // Try to confirm the subscriber by updating their state to active
                $this->confirmSubscriber($subscriberId);
                
                return [
                    'success' => true,
                    'subscriber' => [
                        'id' => $subscription['subscriber']['id'],
                        'email' => $subscription['subscriber']['email_address'],
                        'state' => $subscription['subscriber']['state'],
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Unknown error'
            ];
        } catch (\Exception $e) {
            Log::error('Kit API addSubscriber failed', [
                'email' => $email,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Try to confirm a subscriber by updating their state
     */
    private function confirmSubscriber(string $subscriberId): bool
    {
        try {
            $response = $this->makeApiCall('PUT', "/subscribers/{$subscriberId}", [
                'state' => 'active'
            ]);

            if ($response['success']) {
                Log::info('Subscriber confirmed via API', [
                    'subscriber_id' => $subscriberId
                ]);
                return true;
            } else {
                Log::warning('Failed to confirm subscriber via API', [
                    'subscriber_id' => $subscriberId,
                    'error' => $response['error'] ?? 'Unknown error'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::warning('Exception while confirming subscriber', [
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function addTagToSubscriber(string $email, string $tagId): bool
    {
        try {
            // Use the correct Kit API endpoint: POST /v3/tags/{tag_id}/subscribe
            $response = $this->makeApiCall('POST', "/tags/{$tagId}/subscribe", [
                'email' => $email
            ]);

            if ($response['success']) {
                Log::info('Successfully added tag to subscriber via correct API endpoint', [
                    'email' => $email,
                    'tag_id' => $tagId
                ]);
                return true;
            } else {
                Log::warning('Failed to add tag to subscriber', [
                    'email' => $email,
                    'tag_id' => $tagId,
                    'error' => $response['error'] ?? 'Unknown error'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Kit API addTagToSubscriber failed', [
                'email' => $email,
                'tag_id' => $tagId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function createTag(string $tagName): array
    {
        try {
            $response = $this->makeApiCall('POST', '/tags', [
                'tag' => [
                    'name' => $tagName
                ]
            ]);

            if ($response['success']) {
                $tag = $response['data']['tag'];
                
                // Clear tags cache
                Cache::forget('kit_tags_' . hash('sha256', $this->apiKey));
                
                return [
                    'success' => true,
                    'id' => (string) $tag['id'],
                    'name' => $tag['name'],
                    'created_at' => $tag['created_at'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Unknown error'
            ];
        } catch (\Exception $e) {
            Log::error('Kit API createTag failed', [
                'tag_name' => $tagName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function removeTagFromSubscriber(string $email, string $tagId): bool
    {
        try {
            // Use the correct Kit API endpoint: POST /v3/tags/{tag_id}/unsubscribe
            $response = $this->makeApiCall('POST', "/tags/{$tagId}/unsubscribe", [
                'email' => $email
            ]);

            return $response['success'];
        } catch (\Exception $e) {
            Log::error('Kit API removeTagFromSubscriber failed', [
                'email' => $email,
                'tag_id' => $tagId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeApiCall('GET', '/account');
            return $response['success'];
        } catch (\Exception $e) {
            Log::error('Kit API testConnection failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function validateConfiguration(array $credentials): array
    {
        $errors = [];

        if (empty($credentials['api_key'])) {
            $errors[] = 'API key is required';
        }

        if (empty($credentials['api_secret'])) {
            $errors[] = 'API secret is required';
        }

        if (empty($errors)) {
            // Test the credentials by making a test call
            $tempProvider = new self($credentials);
            if (!$tempProvider->testConnection()) {
                $errors[] = 'Invalid API credentials or connection failed';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function getSubscriber(string $email): ?array
    {
        $result = $this->checkSubscriber($email);
        
        if ($result['is_subscribed']) {
            return [
                'id' => $result['subscriber_id'],
                'email' => $email,
                'tags' => $result['tags'],
                'created_at' => $result['created_at'] ?? null,
            ];
        }

        return null;
    }

    public function updateSubscriber(string $email, array $data): bool
    {
        try {
            $subscriber = $this->checkSubscriber($email);
            
            if (!$subscriber['is_subscribed']) {
                return false;
            }

            $response = $this->makeApiCall('PUT', "/subscribers/{$subscriber['subscriber_id']}", $data);

            return $response['success'];
        } catch (\Exception $e) {
            Log::error('Kit API updateSubscriber failed', [
                'email' => $email,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get tags for a specific subscriber by their ID
     * Note: Kit API v3 doesn't have a direct endpoint for this, so we need to check all tags
     */
    protected function getSubscriberTags(string $subscriberId): array
    {
        try {
            $allTags = $this->getTags();
            $subscriberTags = [];

            foreach ($allTags as $tag) {
                // Check if this subscriber is subscribed to this tag
                $response = $this->makeApiCall('GET', "/tags/{$tag['id']}/subscriptions", [
                    'subscriber_state' => 'active'
                ]);

                if ($response['success'] && !empty($response['data']['subscriptions'])) {
                    // Check if our subscriber is in the list
                    $isSubscribed = collect($response['data']['subscriptions'])
                        ->contains(function ($subscription) use ($subscriberId) {
                            return $subscription['subscriber']['id'] == $subscriberId;
                        });

                    if ($isSubscribed) {
                        $subscriberTags[] = [
                            'id' => $tag['id'],
                            'name' => $tag['name'],
                        ];
                    }
                }
            }

            return $subscriberTags;
        } catch (\Exception $e) {
            Log::error('Kit API getSubscriberTags failed', [
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Make an API call to Kit with retry logic and rate limiting
     */
    protected function makeApiCall(string $method, string $endpoint, array $data = []): array
    {
        $attempt = 0;
        
        while ($attempt < $this->retryAttempts) {
            try {
                // Rate limiting: add delay between requests
                if ($attempt > 0) {
                    usleep($this->retryDelay * 1000 * $attempt); // Exponential backoff
                }

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ]);

                // Add API credentials to the request data
                $requestData = array_merge($data ?? [], [
                    'api_key' => $this->apiKey,
                    'api_secret' => $this->apiSecret,
                ]);

                // Make the request based on method
                switch (strtoupper($method)) {
                    case 'GET':
                        $httpResponse = $response->get($this->baseUrl . $endpoint, $requestData);
                        break;
                    case 'POST':
                        $httpResponse = $response->post($this->baseUrl . $endpoint, $requestData);
                        break;
                    case 'PUT':
                        $httpResponse = $response->put($this->baseUrl . $endpoint, $requestData);
                        break;
                    case 'DELETE':
                        // For DELETE, add credentials as query parameters
                        $deleteUrl = $this->baseUrl . $endpoint . '?' . http_build_query([
                            'api_key' => $this->apiKey,
                            'api_secret' => $this->apiSecret,
                        ]);
                        $httpResponse = $response->delete($deleteUrl);
                        break;
                    default:
                        throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
                }

                // Handle rate limiting (429 status)
                if ($httpResponse->status() === 429) {
                    $attempt++;
                    if ($attempt < $this->retryAttempts) {
                        continue; // Retry after delay
                    }
                    throw new \Exception('Rate limit exceeded after ' . $this->retryAttempts . ' attempts');
                }

                // Parse response
                $responseData = $httpResponse->json();
                
                return [
                    'success' => $httpResponse->successful(),
                    'status' => $httpResponse->status(),
                    'data' => $responseData,
                    'error' => $httpResponse->successful() ? null : ($responseData['error'] ?? 'API request failed')
                ];

            } catch (\Exception $e) {
                $attempt++;
                
                if ($attempt >= $this->retryAttempts) {
                    throw $e; // Re-throw on final attempt
                }
                
                Log::warning('Kit API call failed, retrying', [
                    'attempt' => $attempt,
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);
            }
        }

        throw new \Exception('Maximum retry attempts exceeded');
    }
} 