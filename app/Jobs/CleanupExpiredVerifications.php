<?php

namespace App\Jobs;

use App\Models\EmailVerificationRequest;
use App\Models\SubscriberAccessRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupExpiredVerifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    public function handle(): void
    {
        try {
            Log::info('Starting cleanup of expired verification requests');

            // Clean up expired verification requests
            $expiredCount = $this->cleanupExpiredVerificationRequests();
            
            // Clean up old access records based on tenant settings
            $accessRecordsCount = $this->cleanupExpiredAccessRecords();

            Log::info('Cleanup completed', [
                'expired_verifications_deleted' => $expiredCount,
                'expired_access_records_cleaned' => $accessRecordsCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error during verification cleanup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up expired verification requests
     */
    private function cleanupExpiredVerificationRequests(): int
    {
        try {
            // Delete verification requests that expired more than 24 hours ago
            // This gives a grace period in case of clock skew or delays
            $expiredRequestsCount = EmailVerificationRequest::where('expires_at', '<', now()->subHours(24))
                ->whereNull('verified_at')
                ->count();

            if ($expiredRequestsCount > 0) {
                $deletedCount = EmailVerificationRequest::where('expires_at', '<', now()->subHours(24))
                    ->whereNull('verified_at')
                    ->delete();

                Log::info('Deleted expired verification requests', [
                    'count' => $deletedCount,
                    'threshold' => now()->subHours(24)->toDateTimeString()
                ]);

                return $deletedCount;
            } else {
                Log::info('No expired verification requests to clean up');
                return 0;
            }

        } catch (\Exception $e) {
            Log::error('Error cleaning up expired verification requests', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up expired access records based on tenant-specific cookie durations
     */
    private function cleanupExpiredAccessRecords(): int
    {
        try {
            $totalCleaned = 0;

            // Get all unique tenant IDs from access records
            $tenantIds = SubscriberAccessRecord::distinct('tenant_id')
                ->pluck('tenant_id')
                ->toArray();

            foreach ($tenantIds as $tenantId) {
                try {
                    // Get the tenant's cookie duration setting
                    $tenant = \App\Models\Tenant::find($tenantId);
                    
                    if (!$tenant) {
                        Log::warning('Tenant not found during cleanup', ['tenant_id' => $tenantId]);
                        continue;
                    }

                    // Use tenant-specific cookie duration, default to 30 days
                    $cookieDurationDays = $tenant->email_verification_cookie_duration_days ?? 30;
                    
                    // Add extra buffer (e.g., 7 days) before actually deleting records
                    $bufferDays = 7;
                    $totalDays = $cookieDurationDays + $bufferDays;
                    $expirationThreshold = now()->subDays($totalDays);

                    // Count records that would be deleted
                    $expiredCount = SubscriberAccessRecord::where('tenant_id', $tenantId)
                        ->where('last_verified_at', '<', $expirationThreshold)
                        ->count();

                    if ($expiredCount > 0) {
                        // Delete expired access records for this tenant
                        $deletedCount = SubscriberAccessRecord::where('tenant_id', $tenantId)
                            ->where('last_verified_at', '<', $expirationThreshold)
                            ->delete();

                        $totalCleaned += $deletedCount;

                        Log::info('Cleaned up expired access records for tenant', [
                            'tenant_id' => $tenantId,
                            'cookie_duration_days' => $cookieDurationDays,
                            'buffer_days' => $bufferDays,
                            'total_retention_days' => $totalDays,
                            'deleted_count' => $deletedCount,
                            'threshold' => $expirationThreshold->toDateTimeString()
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Error cleaning up access records for tenant', [
                        'tenant_id' => $tenantId,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other tenants
                }
            }

            if ($totalCleaned === 0) {
                Log::info('No expired access records to clean up');
            }

            return $totalCleaned;

        } catch (\Exception $e) {
            Log::error('Error cleaning up expired access records', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up completed verification requests older than specified days
     * This is a separate method that could be called less frequently
     */
    public function cleanupOldCompletedVerifications(int $daysOld = 90): int
    {
        try {
            $threshold = now()->subDays($daysOld);
            
            $deletedCount = EmailVerificationRequest::where('verified_at', '<', $threshold)
                ->delete();

            Log::info('Cleaned up old completed verification requests', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld,
                'threshold' => $threshold->toDateTimeString()
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Error cleaning up old completed verifications', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get statistics about verification requests and access records
     */
    public function getCleanupStatistics(): array
    {
        try {
            $stats = [
                'verification_requests' => [
                    'total' => EmailVerificationRequest::count(),
                    'pending' => EmailVerificationRequest::whereNull('verified_at')->count(),
                    'verified' => EmailVerificationRequest::whereNotNull('verified_at')->count(),
                    'expired_pending' => EmailVerificationRequest::where('expires_at', '<', now())
                        ->whereNull('verified_at')->count(),
                ],
                'access_records' => [
                    'total' => SubscriberAccessRecord::count(),
                    'active_30_days' => SubscriberAccessRecord::where('last_verified_at', '>=', now()->subDays(30))->count(),
                    'active_7_days' => SubscriberAccessRecord::where('last_verified_at', '>=', now()->subDays(7))->count(),
                ],
                'by_tenant' => []
            ];

            // Get per-tenant statistics
            $tenantStats = SubscriberAccessRecord::selectRaw('
                tenant_id,
                COUNT(*) as total_records,
                COUNT(CASE WHEN last_verified_at >= ? THEN 1 END) as active_records,
                MIN(last_verified_at) as oldest_record,
                MAX(last_verified_at) as newest_record
            ', [now()->subDays(30)])
                ->groupBy('tenant_id')
                ->get()
                ->toArray();

            $stats['by_tenant'] = $tenantStats;

            return $stats;

        } catch (\Exception $e) {
            Log::error('Error getting cleanup statistics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
} 