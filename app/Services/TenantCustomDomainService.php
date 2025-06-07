<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantCustomDomain;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TenantCustomDomainService
{
    /**
     * Add a custom domain to a tenant
     */
    public function addCustomDomain(Tenant $tenant, string $domain, bool $isPrimary = false): TenantCustomDomain
    {
        $this->validateDomain($domain);

        // If setting as primary, ensure no other domain is primary for this tenant
        if ($isPrimary) {
            $tenant->customDomains()->update(['is_primary' => false]);
        }

        return $tenant->customDomains()->create([
            'domain' => strtolower(trim($domain)),
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * Verify a custom domain
     */
    public function verifyDomain(TenantCustomDomain $customDomain): bool
    {
        // Here you would implement actual domain verification logic
        // For now, we'll just mark it as verified
        return $customDomain->markAsVerified();
    }

    /**
     * Set a domain as primary for a tenant
     */
    public function setPrimaryDomain(TenantCustomDomain $customDomain): bool
    {
        if (!$customDomain->isVerified()) {
            throw new \Exception('Domain must be verified before setting as primary');
        }

        return $customDomain->setAsPrimary();
    }

    /**
     * Remove a custom domain
     */
    public function removeDomain(TenantCustomDomain $customDomain): bool
    {
        if ($customDomain->isPrimary()) {
            throw new \Exception('Cannot remove primary domain. Set another domain as primary first.');
        }

        return $customDomain->delete();
    }

    /**
     * Get all domains for a tenant
     */
    public function getTenantDomains(Tenant $tenant): Collection
    {
        return $tenant->customDomains()->active()->orderBy('is_primary', 'desc')->get();
    }

    /**
     * Find tenant by custom domain
     */
    public function findTenantByDomain(string $domain): ?Tenant
    {
        $customDomain = TenantCustomDomain::where('domain', strtolower(trim($domain)))
            ->verified()
            ->active()
            ->first();

        return $customDomain?->tenant;
    }

    /**
     * Check if domain is available (not already taken)
     */
    public function isDomainAvailable(string $domain): bool
    {
        return !TenantCustomDomain::where('domain', strtolower(trim($domain)))->exists();
    }

    /**
     * Validate domain format
     */
    private function validateDomain(string $domain): void
    {
        $validator = Validator::make(['domain' => $domain], [
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z]{2,})+$/']
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (!$this->isDomainAvailable($domain)) {
            throw new \Exception('Domain is already taken by another tenant');
        }
    }

    /**
     * Generate domain verification instructions
     */
    public function getVerificationInstructions(TenantCustomDomain $customDomain): array
    {
        return [
            'domain' => $customDomain->domain,
            'verification_token' => $customDomain->verification_token,
            'instructions' => [
                'dns' => [
                    'type' => 'TXT',
                    'name' => '_saasykit-verification',
                    'value' => $customDomain->verification_token,
                    'description' => 'Add this TXT record to your DNS settings'
                ],
                'file' => [
                    'path' => '/.well-known/saasykit-verification.txt',
                    'content' => $customDomain->verification_token,
                    'description' => 'Upload this file to your domain root'
                ]
            ]
        ];
    }
} 