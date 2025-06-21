<?php

namespace App\Services\EmailServiceProvider;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class EmailServiceProviderManager
{
    private array $providers = [];
    
    public function __construct()
    {
        // Register available providers
        $this->registerProviders();
    }
    
    /**
     * Get ESP provider for a specific tenant
     */
    public function getProviderForTenant(Tenant $tenant): ?EmailServiceProviderInterface
    {
        try {
            $providerSlug = $tenant->email_service_provider ?? 'kit';
            $credentials = $tenant->esp_api_credentials ?? [];
            
            if (empty($credentials)) {
                Log::warning('No ESP credentials configured for tenant', [
                    'tenant_id' => $tenant->id,
                    'provider' => $providerSlug
                ]);
                return null;
            }
            
            return $this->createProvider($providerSlug, $credentials);
        } catch (\Exception $e) {
            Log::error('Failed to get ESP provider for tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get available provider instances
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }
    
    /**
     * Get provider by slug
     */
    public function getProvider(string $slug, array $credentials = []): ?EmailServiceProviderInterface
    {
        try {
            return $this->createProvider($slug, $credentials);
        } catch (\Exception $e) {
            Log::error('Failed to get ESP provider', [
                'provider' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Test ESP configuration for a tenant
     */
    public function testTenantConfiguration(Tenant $tenant): array
    {
        try {
            $provider = $this->getProviderForTenant($tenant);
            
            if (!$provider) {
                return [
                    'success' => false,
                    'error' => 'Provider not configured or credentials missing'
                ];
            }
            
            $connectionTest = $provider->testConnection();
            
            return [
                'success' => $connectionTest,
                'provider' => $provider->getName(),
                'error' => $connectionTest ? null : 'Connection test failed'
            ];
        } catch (\Exception $e) {
            Log::error('ESP configuration test failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate ESP configuration before saving
     */
    public function validateConfiguration(string $providerSlug, array $credentials): array
    {
        try {
            if (!isset($this->providers[$providerSlug])) {
                return [
                    'valid' => false,
                    'errors' => ['Invalid provider: ' . $providerSlug]
                ];
            }
            
            $providerClass = $this->providers[$providerSlug];
            $provider = new $providerClass($credentials);
            
            return $provider->validateConfiguration($credentials);
        } catch (\Exception $e) {
            Log::error('ESP configuration validation failed', [
                'provider' => $providerSlug,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Get metadata for a provider
     */
    public function getProviderMetadata(string $slug): array
    {
        if (!isset($this->providers[$slug])) {
            return [];
        }
        
        try {
            $providerClass = $this->providers[$slug];
            $tempProvider = new $providerClass([]);
            
            return [
                'slug' => $tempProvider->getSlug(),
                'name' => $tempProvider->getName(),
                'required_credentials' => $this->getRequiredCredentials($slug),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get provider metadata', [
                'provider' => $slug,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get all available providers with metadata
     */
    public function getAllProvidersMetadata(): array
    {
        $metadata = [];
        
        foreach (array_keys($this->providers) as $slug) {
            $metadata[$slug] = $this->getProviderMetadata($slug);
        }
        
        return $metadata;
    }
    
    /**
     * Register available ESP providers
     */
    protected function registerProviders(): void
    {
        $this->providers = [
            'kit' => KitServiceProvider::class,
            // Add other providers here as they're implemented
            // 'mailchimp' => MailchimpServiceProvider::class,
            // 'convertkit' => ConvertKitServiceProvider::class,
        ];
    }
    
    /**
     * Create provider instance
     */
    protected function createProvider(string $slug, array $credentials): EmailServiceProviderInterface
    {
        if (!isset($this->providers[$slug])) {
            throw new \InvalidArgumentException("Unknown provider: {$slug}");
        }
        
        $providerClass = $this->providers[$slug];
        
        if (!class_exists($providerClass)) {
            throw new \RuntimeException("Provider class not found: {$providerClass}");
        }
        
        return new $providerClass($credentials);
    }
    
    /**
     * Get required credentials for a provider
     */
    protected function getRequiredCredentials(string $slug): array
    {
        switch ($slug) {
            case 'kit':
                return [
                    'api_key' => [
                        'label' => 'API Key',
                        'type' => 'password',
                        'required' => true,
                        'help' => 'Your Kit API key from your account settings'
                    ]
                ];
            default:
                return [];
        }
    }
} 