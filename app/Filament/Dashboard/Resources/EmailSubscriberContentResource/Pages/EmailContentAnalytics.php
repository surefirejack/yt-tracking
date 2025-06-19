<?php

namespace App\Filament\Dashboard\Resources\EmailSubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\EmailSubscriberContentResource;
use App\Models\EmailSubscriberContent;
use App\Models\EmailVerificationRequest;
use App\Models\SubscriberAccessRecord;
use Filament\Resources\Pages\Page;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EmailContentAnalytics extends Page
{
    protected static string $resource = EmailSubscriberContentResource::class;

    protected static string $view = 'filament.analytics.email-content-analytics';

    protected ?string $heading = 'Email Content Analytics';

    protected ?string $subheading = 'Track email conversion metrics for your gated content';

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        
        // Get content with analytics data
        $contentAnalytics = EmailSubscriberContent::where('tenant_id', $tenant->id)
            ->withCount([
                'verificationRequests as total_requests',
                'verificationRequests as verified_requests' => function ($query) {
                    $query->whereNotNull('verified_at');
                }
            ])
            ->with(['verificationRequests' => function ($query) {
                $query->whereNotNull('verified_at')
                    ->orderBy('verified_at', 'desc')
                    ->take(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($content) {
                $conversionRate = $content->total_requests > 0 
                    ? round(($content->verified_requests / $content->total_requests) * 100, 1)
                    : 0;
                
                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'slug' => $content->slug,
                    'required_tag_id' => $content->required_tag_id,
                    'total_requests' => $content->total_requests,
                    'verified_requests' => $content->verified_requests,
                    'conversion_rate' => $conversionRate,
                    'recent_verifications' => $content->verificationRequests,
                    'created_at' => $content->created_at,
                ];
            });

        // Overall analytics
        $totalContent = EmailSubscriberContent::where('tenant_id', $tenant->id)->count();
        $totalRequests = EmailVerificationRequest::where('tenant_id', $tenant->id)->count();
        $totalVerified = EmailVerificationRequest::where('tenant_id', $tenant->id)
            ->whereNotNull('verified_at')
            ->count();
        $overallConversionRate = $totalRequests > 0 ? round(($totalVerified / $totalRequests) * 100, 1) : 0;

        // Daily verification trend (last 30 days)
        $dailyVerifications = EmailVerificationRequest::where('tenant_id', $tenant->id)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(verified_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill in missing days with 0
        $verificationTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $verificationTrend[$date] = $dailyVerifications->get($date)?->count ?? 0;
        }

        // Tag performance
        $tagPerformance = EmailSubscriberContent::where('tenant_id', $tenant->id)
            ->whereNotNull('required_tag_id')
            ->select('required_tag_id')
            ->selectRaw('COUNT(*) as content_count')
            ->selectRaw('SUM(
                (SELECT COUNT(*) FROM email_verification_requests evr 
                 WHERE evr.content_id = email_subscriber_contents.id 
                 AND evr.verified_at IS NOT NULL)
            ) as total_verifications')
            ->groupBy('required_tag_id')
            ->get();

        return [
            'contentAnalytics' => $contentAnalytics,
            'totalContent' => $totalContent,
            'totalRequests' => $totalRequests,
            'totalVerified' => $totalVerified,
            'overallConversionRate' => $overallConversionRate,
            'verificationTrend' => $verificationTrend,
            'tagPerformance' => $tagPerformance,
        ];
    }

    public function getWidgets(): array
    {
        return [
            EmailAnalyticsOverview::class,
            EmailVerificationTrendChart::class,
        ];
    }
}

class EmailAnalyticsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        
        $totalContent = EmailSubscriberContent::where('tenant_id', $tenant->id)->count();
        $totalRequests = EmailVerificationRequest::where('tenant_id', $tenant->id)->count();
        $totalVerified = EmailVerificationRequest::where('tenant_id', $tenant->id)
            ->whereNotNull('verified_at')
            ->count();
        
        $recentVerified = EmailVerificationRequest::where('tenant_id', $tenant->id)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subDays(7))
            ->count();
        
        $previousWeekVerified = EmailVerificationRequest::where('tenant_id', $tenant->id)
            ->whereNotNull('verified_at')
            ->whereBetween('verified_at', [now()->subDays(14), now()->subDays(7)])
            ->count();
        
        $weeklyGrowth = $previousWeekVerified > 0 
            ? round((($recentVerified - $previousWeekVerified) / $previousWeekVerified) * 100, 1)
            : ($recentVerified > 0 ? 100 : 0);
        
        $overallConversionRate = $totalRequests > 0 ? round(($totalVerified / $totalRequests) * 100, 1) : 0;

        return [
            StatsOverviewWidget\Stat::make('Total Content', $totalContent)
                ->description('Email-gated content pieces')
                ->icon('heroicon-o-document-text'),
            
            StatsOverviewWidget\Stat::make('Email Verifications', $totalVerified)
                ->description($recentVerified . ' this week')
                ->descriptionIcon($weeklyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($weeklyGrowth >= 0 ? 'success' : 'danger'),
            
            StatsOverviewWidget\Stat::make('Conversion Rate', $overallConversionRate . '%')
                ->description('Email requests to verifications')
                ->icon('heroicon-o-chart-bar'),
            
            StatsOverviewWidget\Stat::make('Total Requests', $totalRequests)
                ->description('All email verification requests')
                ->icon('heroicon-o-envelope'),
        ];
    }
}

class EmailVerificationTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Email Verifications (Last 30 Days)';

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        
        $dailyVerifications = EmailVerificationRequest::where('tenant_id', $tenant->id)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(verified_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            $data[] = $dailyVerifications->get($date->format('Y-m-d'))?->count ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Email Verifications',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b98120',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
} 