<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use App\Enums\AnalyticsInterval;
use Exception;

class DubAnalyticsService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $cacheTtl; // Cache TTL in minutes
    protected int $maxRetries;
    protected int $retryDelay; // Base delay in seconds
    protected int $timeoutSeconds;
    protected bool $testMode; // Test mode flag
    
    public function __construct()
    {
        $this->apiKey = config('services.dub.api_key');
        $this->baseUrl = 'https://api.dub.co';
        $this->cacheTtl = config('cache.analytics_ttl', 20); // Default 20 minutes, configurable
        $this->maxRetries = config('services.dub.max_retries', 3);
        $this->retryDelay = config('services.dub.retry_delay', 2); // 2 seconds base delay
        $this->timeoutSeconds = config('services.dub.timeout', 15);
        $this->testMode = config('services.dub.test_mode', false);
        
        if (!$this->testMode && !$this->apiKey) {
            throw new Exception('Dub API key is not configured. Please check DUB_API_KEY in your environment.');
        }
    }

    /**
     * Retrieve analytics data for a specific tenant with caching
     *
     * @param int $tenantId
     * @param array $params Additional parameters for the API call
     * @param bool $useCache Whether to use caching or fetch fresh data
     * @return array
     * @throws Exception
     */
    public function getAnalytics(int $tenantId, array $params = [], bool $useCache = true): array
    {
        // Check rate limiting first
        if (!$this->checkRateLimit($tenantId)) {
            throw new Exception('Rate limit exceeded for tenant. Please try again later.');
        }

        // Generate cache key based on tenant and parameters
        $cacheKey = $this->generateCacheKey($tenantId, 'analytics', $params);
        
        if ($useCache) {
            // Try to get from cache first
            $cachedData = Cache::get($cacheKey);
            if ($cachedData !== null) {
                Log::info('Retrieved analytics data from cache', [
                    'tenant_id' => $tenantId,
                    'cache_key' => $cacheKey,
                ]);
                return $cachedData;
            }
        }
        
        // Fetch fresh data from API with retry logic
        $data = $this->fetchAnalyticsFromApiWithRetry($tenantId, $params);
        
        // Cache the data
        Cache::put($cacheKey, $data, now()->addMinutes($this->cacheTtl));
        
        Log::info('Cached analytics data', [
            'tenant_id' => $tenantId,
            'cache_key' => $cacheKey,
            'ttl_minutes' => $this->cacheTtl,
        ]);
        
        return $data;
    }

    /**
     * Fetch analytics data from API with retry logic and error handling
     *
     * @param int $tenantId
     * @param array $params
     * @return array
     * @throws Exception
     */
    protected function fetchAnalyticsFromApiWithRetry(int $tenantId, array $params = []): array
    {
        // Return test data if test mode is enabled
        if ($this->testMode) {
            Log::info('Test mode enabled - returning test analytics data', [
                'tenant_id' => $tenantId,
                'params' => $params,
            ]);
            
            return $this->generateTestAnalyticsData($tenantId, $params);
        }
        
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $this->fetchAnalyticsFromApi($tenantId, $params);
            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('API request failed, attempt ' . $attempt, [
                    'tenant_id' => $tenantId,
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'error' => $e->getMessage(),
                ]);
                
                // Don't retry on authentication or client errors (4xx)
                if ($this->isNonRetryableError($e)) {
                    Log::error('Non-retryable error encountered', [
                        'tenant_id' => $tenantId,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
                
                // Don't wait after the last attempt
                if ($attempt < $this->maxRetries) {
                    $delay = $this->calculateBackoffDelay($attempt);
                    Log::info('Waiting before retry', [
                        'tenant_id' => $tenantId,
                        'delay_seconds' => $delay,
                        'next_attempt' => $attempt + 1,
                    ]);
                    sleep($delay);
                }
            }
        }
        
        // If we get here, all retries failed
        Log::error('All API retry attempts failed', [
            'tenant_id' => $tenantId,
            'max_retries' => $this->maxRetries,
            'final_error' => $lastException->getMessage(),
        ]);
        
        throw new Exception('Analytics API is temporarily unavailable. Please try again later. Last error: ' . $lastException->getMessage());
    }

    /**
     * Fetch analytics data directly from Dub API (single attempt)
     *
     * @param int $tenantId
     * @param array $params
     * @return array
     * @throws Exception
     */
    protected function fetchAnalyticsFromApi(int $tenantId, array $params = []): array
    {
        // Record API call for rate limiting
        $this->recordApiCall($tenantId);
        
        try {
            // Build the API URL
            $url = $this->baseUrl . '/analytics';
            
            // Prepare default parameters
            $defaultParams = [
                'tenantId' => (string) $tenantId,
                'interval' => AnalyticsInterval::default()->value, // Default to 30 days
                'event' => 'composite', // Get all events (clicks, leads, sales)
            ];
            
            // Merge with provided params
            $queryParams = array_merge($defaultParams, $params);
            
            Log::info('Fetching analytics data from Dub API', [
                'tenant_id' => $tenantId,
                'params' => $queryParams,
                'url' => $url,
            ]);

            // Make the API call with timeout
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'YouTubeTracker/1.0',
            ])->timeout($this->timeoutSeconds)->get($url, $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Successfully retrieved analytics data from API', [
                    'tenant_id' => $tenantId,
                    'data_count' => is_array($data) ? count($data) : 0,
                    'response_size' => strlen($response->body()),
                ]);
                
                return $data;
            } else {
                $statusCode = $response->status();
                $responseBody = $response->body();
                
                // Handle different HTTP status codes
                $errorMessage = $this->getErrorMessageFromResponse($statusCode, $responseBody);
                
                Log::error('Dub API request failed', [
                    'tenant_id' => $tenantId,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'error_message' => $errorMessage,
                ]);
                
                throw new Exception($errorMessage);
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error while fetching analytics', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Unable to connect to analytics service: ' . $e->getMessage());
        } catch (RequestException $e) {
            Log::error('Request error while fetching analytics', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Analytics request failed: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Unexpected error while fetching analytics', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if the error is non-retryable (4xx client errors)
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isNonRetryableError(Exception $exception): bool
    {
        $message = $exception->getMessage();
        
        // Check for HTTP 4xx status codes in the error message
        if (preg_match('/HTTP 4\d{2}/', $message)) {
            return true;
        }
        
        // Check for authentication errors
        if (str_contains($message, 'Unauthorized') || str_contains($message, 'Forbidden')) {
            return true;
        }
        
        // Check for bad request errors
        if (str_contains($message, 'Bad Request') || str_contains($message, 'Invalid parameter')) {
            return true;
        }
        
        return false;
    }

    /**
     * Calculate backoff delay with exponential backoff
     *
     * @param int $attempt
     * @return int Delay in seconds
     */
    protected function calculateBackoffDelay(int $attempt): int
    {
        // Exponential backoff: base_delay * (2 ^ (attempt - 1))
        // With jitter to avoid thundering herd
        $delay = $this->retryDelay * pow(2, $attempt - 1);
        
        // Add random jitter (±25%)
        $jitter = $delay * 0.25;
        $delay += random_int(-$jitter, $jitter);
        
        // Cap at 60 seconds
        return min($delay, 60);
    }

    /**
     * Get user-friendly error message from HTTP response
     *
     * @param int $statusCode
     * @param string $responseBody
     * @return string
     */
    protected function getErrorMessageFromResponse(int $statusCode, string $responseBody): string
    {
        switch ($statusCode) {
            case 401:
                return 'Authentication failed. Please check API credentials.';
            case 403:
                return 'Access forbidden. Insufficient permissions for this operation.';
            case 404:
                return 'Analytics endpoint not found. The requested resource may not exist.';
            case 429:
                return 'Rate limit exceeded. Please wait before making more requests.';
            case 500:
                return 'Analytics service is temporarily unavailable. Please try again later.';
            case 502:
            case 503:
            case 504:
                return 'Analytics service is experiencing issues. Please try again in a few minutes.';
            default:
                // Try to parse JSON error message
                $decoded = json_decode($responseBody, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message'])) {
                    return 'API Error: ' . $decoded['message'];
                }
                return 'API request failed with HTTP ' . $statusCode . ': ' . $responseBody;
        }
    }

    /**
     * Check rate limiting for a tenant
     *
     * @param int $tenantId
     * @return bool True if within rate limits
     */
    protected function checkRateLimit(int $tenantId): bool
    {
        $rateLimitKey = "dub_api_rate_limit:tenant_{$tenantId}";
        
        // Allow 100 requests per hour per tenant (conservative limit)
        $maxRequests = config('services.dub.rate_limit_requests', 100);
        $windowMinutes = config('services.dub.rate_limit_window', 60);
        
        $currentCount = Cache::get($rateLimitKey, 0);
        
        if ($currentCount >= $maxRequests) {
            Log::warning('Rate limit exceeded', [
                'tenant_id' => $tenantId,
                'current_count' => $currentCount,
                'max_requests' => $maxRequests,
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Record an API call for rate limiting
     *
     * @param int $tenantId
     */
    protected function recordApiCall(int $tenantId): void
    {
        $rateLimitKey = "dub_api_rate_limit:tenant_{$tenantId}";
        $windowMinutes = config('services.dub.rate_limit_window', 60);
        
        $currentCount = Cache::get($rateLimitKey, 0);
        Cache::put($rateLimitKey, $currentCount + 1, now()->addMinutes($windowMinutes));
    }

    /**
     * Get analytics data for a specific video using tag-based filtering with caching
     *
     * @param int $tenantId
     * @param int $videoId
     * @param array $params Additional parameters (interval, etc.)
     * @param bool $useCache Whether to use caching
     * @return array
     */
    public function getVideoAnalytics(int $tenantId, int $videoId, array $params = [], bool $useCache = true): array
    {
        // Use the video tag pattern for filtering
        $tagName = 'yt-video-' . $videoId;
        $params['tagName'] = $tagName;
        
        Log::info('Fetching video analytics', [
            'tenant_id' => $tenantId,
            'video_id' => $videoId,
            'tag_name' => $tagName,
            'use_cache' => $useCache,
        ]);
        
        return $this->getAnalytics($tenantId, $params, $useCache);
    }

    /**
     * Get analytics data grouped by destination URLs with caching
     *
     * @param int $tenantId
     * @param array $params Additional parameters
     * @param bool $useCache Whether to use caching
     * @return array
     */
    public function getUrlAnalytics(int $tenantId, array $params = [], bool $useCache = true): array
    {
        // Use top_urls groupBy parameter for destination URL analysis
        $params['groupBy'] = 'top_urls';
        
        Log::info('Fetching URL analytics', [
            'tenant_id' => $tenantId,
            'group_by' => 'top_urls',
            'use_cache' => $useCache,
        ]);
        
        return $this->getAnalytics($tenantId, $params, $useCache);
    }

    /**
     * Get analytics for a specific destination URL with caching
     *
     * @param int $tenantId
     * @param string $destinationUrl
     * @param array $params Additional parameters
     * @param bool $useCache Whether to use caching
     * @return array
     */
    public function getDestinationUrlAnalytics(int $tenantId, string $destinationUrl, array $params = [], bool $useCache = true): array
    {
        $params['url'] = $destinationUrl;
        
        Log::info('Fetching destination URL analytics', [
            'tenant_id' => $tenantId,
            'destination_url' => $destinationUrl,
            'use_cache' => $useCache,
        ]);
        
        return $this->getAnalytics($tenantId, $params, $useCache);
    }

    /**
     * Get analytics data with UTM parameter filtering and caching
     *
     * @param int $tenantId
     * @param array $utmParams UTM parameters to filter by
     * @param array $params Additional parameters
     * @param bool $useCache Whether to use caching
     * @return array
     */
    public function getAnalyticsWithUtmFilters(int $tenantId, array $utmParams = [], array $params = [], bool $useCache = true): array
    {
        // Add UTM parameters to the query
        foreach ($utmParams as $utmKey => $utmValue) {
            if (!empty($utmValue)) {
                $params[$utmKey] = $utmValue;
            }
        }
        
        Log::info('Fetching analytics with UTM filters', [
            'tenant_id' => $tenantId,
            'utm_params' => $utmParams,
            'use_cache' => $useCache,
        ]);
        
        return $this->getAnalytics($tenantId, $params, $useCache);
    }

    /**
     * Generate a tenant-specific cache key for analytics data
     *
     * @param int $tenantId
     * @param string $type Type of analytics (analytics, video, url, etc.)
     * @param array $params Parameters used for the request
     * @return string
     */
    protected function generateCacheKey(int $tenantId, string $type, array $params = []): string
    {
        // Sort parameters to ensure consistent cache keys
        ksort($params);
        
        // Create a hash of the parameters for a shorter cache key
        $paramsHash = md5(json_encode($params));
        
        return "dub_analytics:tenant_{$tenantId}:{$type}:{$paramsHash}";
    }

    /**
     * Invalidate cache for a specific tenant
     *
     * @param int $tenantId
     * @param string|null $type Optional type filter (analytics, video, url)
     * @return bool
     */
    public function invalidateCache(int $tenantId, ?string $type = null): bool
    {
        try {
            if ($type) {
                // Invalidate specific type for tenant
                $pattern = "dub_analytics:tenant_{$tenantId}:{$type}:*";
            } else {
                // Invalidate all analytics cache for tenant
                $pattern = "dub_analytics:tenant_{$tenantId}:*";
            }
            
            // Note: This approach works with Redis cache driver
            // For other cache drivers, you might need to track cache keys manually
            $keys = Cache::getStore()->getRedis()->keys($pattern);
            
            if (!empty($keys)) {
                Cache::getStore()->getRedis()->del($keys);
                
                Log::info('Invalidated analytics cache', [
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'keys_removed' => count($keys),
                ]);
                
                return true;
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to invalidate analytics cache', [
                'tenant_id' => $tenantId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Process analytics data to calculate aggregated metrics
     *
     * @param array $analyticsData Raw analytics data from API
     * @return array Processed metrics
     */
    public function processAnalyticsData(array $analyticsData): array
    {
        $totalClicks = 0;
        $totalLeads = 0;
        $totalSales = 0;
        $totalSaleAmount = 0;
        
        foreach ($analyticsData as $dataPoint) {
            $totalClicks += $dataPoint['clicks'] ?? 0;
            $totalLeads += $dataPoint['leads'] ?? 0;
            $totalSales += $dataPoint['sales'] ?? 0;
            $totalSaleAmount += $dataPoint['saleAmount'] ?? 0;
        }
        
        // Calculate conversion rates and revenue per click
        $leadConversionRate = $totalClicks > 0 ? round(($totalLeads / $totalClicks) * 100, 2) : 0;
        $salesConversionRate = $totalClicks > 0 ? round(($totalSales / $totalClicks) * 100, 2) : 0;
        $revenuePerClick = $totalClicks > 0 ? round($totalSaleAmount / $totalClicks, 2) : 0;
        
        return [
            'total_clicks' => $totalClicks,
            'total_leads' => $totalLeads,
            'total_sales' => $totalSales,
            'total_sale_amount' => $totalSaleAmount,
            'lead_conversion_rate' => $leadConversionRate,
            'sales_conversion_rate' => $salesConversionRate,
            'revenue_per_click' => $revenuePerClick,
            'raw_data' => $analyticsData,
        ];
    }

    /**
     * Get supported time intervals for analytics
     *
     * @return array
     */
    public function getSupportedIntervals(): array
    {
        return AnalyticsInterval::options();
    }
    
    /**
     * Generate realistic test analytics data for development and testing
     *
     * @param int $tenantId
     * @param array $params
     * @return array
     */
    protected function generateTestAnalyticsData(int $tenantId, array $params = []): array
    {
        // Base seed for consistent but varied data
        mt_srand($tenantId * 1000 + crc32(json_encode($params)));
        
        // Get the interval to determine how many data points to generate
        $interval = $params['interval'] ?? AnalyticsInterval::default()->value;
        $dataPoints = $this->getDataPointsForInterval($interval);
        
        // Determine base metrics based on parameters
        $baseMetrics = $this->getBaseMetricsForParams($params);
        
        $analyticsData = [];
        
        // Generate daily data points
        for ($i = 0; $i < $dataPoints; $i++) {
            $date = now()->subDays($dataPoints - 1 - $i)->format('Y-m-d');
            
            // Add some variation to metrics (±30% from base)
            $clickVariation = mt_rand(70, 130) / 100;
            $leadVariation = mt_rand(80, 120) / 100;
            $salesVariation = mt_rand(85, 115) / 100;
            $revenueVariation = mt_rand(90, 110) / 100;
            
            // Weekend effect - lower traffic on weekends
            $dayOfWeek = now()->subDays($dataPoints - 1 - $i)->dayOfWeek;
            $weekendMultiplier = in_array($dayOfWeek, [0, 6]) ? 0.6 : 1.0; // Saturday=6, Sunday=0
            
            $clicks = max(0, round($baseMetrics['clicks'] * $clickVariation * $weekendMultiplier));
            $leads = max(0, round($baseMetrics['leads'] * $leadVariation * $weekendMultiplier));
            $sales = max(0, round($baseMetrics['sales'] * $salesVariation * $weekendMultiplier));
            $saleAmount = max(0, round($baseMetrics['saleAmount'] * $revenueVariation * $weekendMultiplier, 2));
            
            $analyticsData[] = [
                'date' => $date,
                'clicks' => $clicks,
                'leads' => $leads,
                'sales' => $sales,
                'saleAmount' => $saleAmount,
                'country' => $this->getRandomCountry(),
                'device' => $this->getRandomDevice(),
                'browser' => $this->getRandomBrowser(),
                'referer' => $this->getRandomReferer(),
            ];
        }
        
        return $analyticsData;
    }
    
    /**
     * Get number of data points based on interval
     */
    protected function getDataPointsForInterval(string $interval): int
    {
        return match ($interval) {
            '24h' => 24, // Hourly data for 24 hours
            '7d' => 7,   // Daily data for 7 days
            '30d' => 30, // Daily data for 30 days
            '90d' => 30, // Show 30 days of data (simplified)
            '1y' => 30,  // Show monthly data (simplified to 30 points)
            'mtd' => date('j'), // Days in current month
            'qtd' => 30, // Simplified to 30 days
            'ytd' => 30, // Simplified to 30 days
            'all' => 30, // Simplified to 30 days
            default => 30,
        };
    }
    
    /**
     * Get base metrics based on parameters
     */
    protected function getBaseMetricsForParams(array $params): array
    {
        // Default base metrics
        $baseMetrics = [
            'clicks' => 45,
            'leads' => 8,
            'sales' => 2,
            'saleAmount' => 150.00,
        ];
        
        // Adjust based on video ID if present
        if (isset($params['tagName'])) {
            $videoId = str_replace('yt-video-', '', $params['tagName']);
            if (is_numeric($videoId)) {
                $multiplier = 1 + (($videoId % 5) * 0.3); // Different videos have different performance
                $baseMetrics['clicks'] = round($baseMetrics['clicks'] * $multiplier);
                $baseMetrics['leads'] = round($baseMetrics['leads'] * $multiplier);
                $baseMetrics['sales'] = round($baseMetrics['sales'] * $multiplier);
                $baseMetrics['saleAmount'] = round($baseMetrics['saleAmount'] * $multiplier, 2);
            }
        }
        
        // Adjust based on URL if present
        if (isset($params['url'])) {
            $urlHash = crc32($params['url']);
            $multiplier = 1 + (($urlHash % 10) * 0.2); // Different URLs have different performance
            $baseMetrics['clicks'] = round($baseMetrics['clicks'] * $multiplier);
            $baseMetrics['leads'] = round($baseMetrics['leads'] * $multiplier);
            $baseMetrics['sales'] = round($baseMetrics['sales'] * $multiplier);
            $baseMetrics['saleAmount'] = round($baseMetrics['saleAmount'] * $multiplier, 2);
        }
        
        // Adjust based on UTM parameters
        $utmMultiplier = 1.0;
        if (isset($params['utm_source'])) {
            $utmMultiplier *= match($params['utm_source']) {
                'youtube' => 1.5,
                'facebook' => 1.2,
                'google' => 1.3,
                'twitter' => 0.8,
                'email' => 0.9,
                default => 1.0,
            };
        }
        
        if (isset($params['utm_medium'])) {
            $utmMultiplier *= match($params['utm_medium']) {
                'video' => 1.4,
                'social' => 1.1,
                'email' => 0.9,
                'paid' => 1.6,
                'organic' => 1.2,
                default => 1.0,
            };
        }
        
        if (isset($params['utm_campaign'])) {
            // Different campaigns have different performance
            $campaignHash = crc32($params['utm_campaign']);
            $utmMultiplier *= 1 + (($campaignHash % 6) * 0.15);
        }
        
        // Apply UTM multiplier
        if ($utmMultiplier !== 1.0) {
            $baseMetrics['clicks'] = round($baseMetrics['clicks'] * $utmMultiplier);
            $baseMetrics['leads'] = round($baseMetrics['leads'] * $utmMultiplier);
            $baseMetrics['sales'] = round($baseMetrics['sales'] * $utmMultiplier);
            $baseMetrics['saleAmount'] = round($baseMetrics['saleAmount'] * $utmMultiplier, 2);
        }
        
        return $baseMetrics;
    }
    
    /**
     * Get random country for test data
     */
    protected function getRandomCountry(): string
    {
        $countries = ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'NL', 'JP', 'BR', 'IN'];
        return $countries[array_rand($countries)];
    }
    
    /**
     * Get random device for test data
     */
    protected function getRandomDevice(): string
    {
        $devices = ['Desktop', 'Mobile', 'Tablet'];
        $weights = [50, 40, 10]; // Desktop 50%, Mobile 40%, Tablet 10%
        
        $rand = mt_rand(1, 100);
        if ($rand <= $weights[0]) return $devices[0];
        if ($rand <= $weights[0] + $weights[1]) return $devices[1];
        return $devices[2];
    }
    
    /**
     * Get random browser for test data
     */
    protected function getRandomBrowser(): string
    {
        $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge', 'Opera'];
        $weights = [65, 20, 8, 5, 2];
        
        $rand = mt_rand(1, 100);
        $cumulative = 0;
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $browsers[$i];
            }
        }
        return $browsers[0];
    }
    
    /**
     * Get random referer for test data
     */
    protected function getRandomReferer(): string
    {
        $referers = [
            'youtube.com',
            'google.com',
            'facebook.com',
            'twitter.com',
            'direct',
            'linkedin.com',
            'reddit.com',
            'instagram.com'
        ];
        return $referers[array_rand($referers)];
    }
} 