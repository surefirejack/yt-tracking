<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Link;
use App\Models\YtVideo;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        
        // Count videos through the tenant's channel
        $videoCount = 0;
        if ($tenant && $tenant->ytChannel) {
            $videoCount = $tenant->ytChannel->ytVideos()->count();
        }
        
        // Count links for this tenant
        $linkCount = 0;
        if ($tenant) {
            $linkCount = Link::where('tenant_id', $tenant->id)->count();
        }
        
        // Hardcoded leads count as requested
        $leadsCount = 75;

        return [
            Stat::make('Videos on Your Channel', $videoCount)
                ->description('Total videos in your channel')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('primary'),
            
            Stat::make('Tracking Links', $linkCount)
                ->description('Total tracking links created')
                ->descriptionIcon('heroicon-m-link')
                ->color('success'),
            
            Stat::make('Leads (Past 30 days)', $leadsCount)
                ->description('New leads this month')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
} 