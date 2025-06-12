<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantReferral;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class ReferralController extends Controller
{
    /**
     * Handle referral link clicks from "Powered by" links.
     * Tracks the click and sets a referral cookie for attribution.
     */
    public function trackReferral(Request $request, string $tenantUuid): RedirectResponse
    {
        try {
            // Find the tenant by UUID
            $tenant = Tenant::where('uuid', $tenantUuid)->first();

            if ($tenant) {
                // Increment the referral click count
                $referral = TenantReferral::firstOrCreate(
                    ['tenant_id' => $tenant->id],
                    ['clicks' => 0, 'conversions' => 0]
                );
                
                $referral->increment('clicks');

                // Log the referral click
                \Log::info('Referral click tracked', [
                    'tenant_id' => $tenant->id,
                    'tenant_uuid' => $tenantUuid,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer'),
                ]);
            }

            // Set a referral attribution cookie (valid for 30 days)
            $cookie = Cookie::make(
                'referral_tenant',
                $tenantUuid,
                30 * 24 * 60, // 30 days in minutes
                '/',
                null,
                false,
                false
            );

            // Redirect to the main landing page or signup page
            $redirectUrl = $request->query('redirect', config('app.url'));
            
            return redirect($redirectUrl)->withCookie($cookie);

        } catch (\Exception $e) {
            \Log::error('Error tracking referral', [
                'tenant_uuid' => $tenantUuid,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            // Still redirect to avoid broken links
            $redirectUrl = $request->query('redirect', config('app.url'));
            return redirect($redirectUrl);
        }
    }

    /**
     * Track a successful conversion (new tenant signup) from a referral.
     * This should be called when a new tenant signs up and has a referral cookie.
     */
    public static function trackConversion(Request $request): void
    {
        try {
            $referralTenantUuid = $request->cookie('referral_tenant');

            if ($referralTenantUuid) {
                $referralTenant = Tenant::where('uuid', $referralTenantUuid)->first();

                if ($referralTenant) {
                    $referral = TenantReferral::firstOrCreate(
                        ['tenant_id' => $referralTenant->id],
                        ['clicks' => 0, 'conversions' => 0]
                    );
                    
                    $referral->increment('conversions');

                    \Log::info('Referral conversion tracked', [
                        'referring_tenant_id' => $referralTenant->id,
                        'referring_tenant_uuid' => $referralTenantUuid,
                        'ip' => $request->ip(),
                    ]);

                    // Clear the referral cookie after conversion
                    Cookie::queue(Cookie::forget('referral_tenant'));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error tracking referral conversion', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
        }
    }

    /**
     * Get referral analytics for a tenant (for internal use or API).
     */
    public function analytics(Request $request, Tenant $tenant)
    {
        $referral = TenantReferral::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'tenant_id' => $tenant->id,
            'clicks' => $referral?->clicks ?? 0,
            'conversions' => $referral?->conversions ?? 0,
            'conversion_rate' => $referral && $referral->clicks > 0 
                ? round(($referral->conversions / $referral->clicks) * 100, 2) 
                : 0,
            'last_updated' => $referral?->updated_at,
        ]);
    }

    /**
     * Generate a "Powered by" referral URL for a tenant.
     */
    public static function generateReferralUrl(string $tenantUuid, string $redirectUrl = null): string
    {
        $baseUrl = config('app.url');
        $url = "{$baseUrl}/referral/{$tenantUuid}";
        
        if ($redirectUrl) {
            $url .= '?redirect=' . urlencode($redirectUrl);
        }
        
        return $url;
    }
}
