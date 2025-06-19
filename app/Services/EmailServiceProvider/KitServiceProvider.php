<?php

namespace App\Services\EmailServiceProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KitServiceProvider implements EmailServiceProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.kit.com/v3';
    protected int $timeout = 30;
    protected int $retryAttempts = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function __construct(array $credentials)
    {
        $this->apiKey = $credentials['api_key'] ?? '';
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
            $response = $this->makeApiCall('GET', '/subscribers', [
                'email_address' => $email
            ]);

            if ($response['success'] && !empty($response['data']['subscribers'])) {
                $subscriber = $response['data']['subscribers'][0];
                return [
                    'is_subscribed' => $subscriber['state'] === 'active',
                    'subscriber_id' => $subscriber['id'],
                    'tags' => $this->getSubscriberTags($subscriber['id']),
                    'created_at' => $subscriber['created_at'] ?? null,
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

    public function addSubscriber(string $email, array $tags = []): array
    {
        try {
            $data = [
                'email_address' => $email,
                'state' => 'active'
            ];

            // Add tags if provided
            if (!empty($tags)) {
                $data['tags'] = $tags;
            }

            $response = $this->makeApiCall('POST', '/subscribers', $data);

            if ($response['success']) {
                $subscriber = $response['data']['subscriber'];
                return [
                    'success' => true,
                    'subscriber_id' => $subscriber['id'],
                    'email' => $subscriber['email_address'],
                    'state' => $subscriber['state'],
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

    public function addTagToSubscriber(string $email, string $tagId): bool
    {
        try {
            $subscriber = $this->checkSubscriber($email);
            
            if (!$subscriber['is_subscribed']) {
                // Add subscriber first if they don't exist
                $addResult = $this->addSubscriber($email, [$tagId]);
                return $addResult['success'] ?? false;
            }

            $response = $this->makeApiCall('POST', "/subscribers/{$subscriber['subscriber_id']}/tags", [
                'tag_id' => $tagId
            ]);

            return $response['success'];
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
            $subscriber = $this->checkSubscriber($email);
            
            if (!$subscriber['is_subscribed']) {
                return false; // Can't remove tag from non-existent subscriber
            }

            $response = $this->makeApiCall('DELETE', "/subscribers/{$subscriber['subscriber_id']}/tags/{$tagId}");

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

        if (empty($errors)) {
            // Test the API key by making a test call
            $tempProvider = new self($credentials);
            if (!$tempProvider->testConnection()) {
                $errors[] = 'Invalid API key or connection failed';
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
     */
    protected function getSubscriberTags(string $subscriberId): array
    {
        try {
            $response = $this->makeApiCall('GET', "/subscribers/{$subscriberId}/tags");

            if ($response['success']) {
                return collect($response['data']['tags'] ?? [])
                    ->map(function ($tag) {
                        return [
                            'id' => (string) $tag['id'],
                            'name' => $tag['name'],
                        ];
                    })
                    ->toArray();
            }

            return [];
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
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ]);

                // Make the request based on method
                switch (strtoupper($method)) {
                    case 'GET':
                        $httpResponse = empty($data) 
                            ? $response->get($this->baseUrl . $endpoint)
                            : $response->get($this->baseUrl . $endpoint, $data);
                        break;
                    case 'POST':
                        $httpResponse = $response->post($this->baseUrl . $endpoint, $data);
                        break;
                    case 'PUT':
                        $httpResponse = $response->put($this->baseUrl . $endpoint, $data);
                        break;
                    case 'DELETE':
                        $httpResponse = $response->delete($this->baseUrl . $endpoint);
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