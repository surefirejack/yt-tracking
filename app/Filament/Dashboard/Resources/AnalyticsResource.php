<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\AnalyticsResource\Pages;
use Filament\Resources\Resource;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;

class AnalyticsResource extends Resource
{
    // No model needed for this dashboard resource
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Analytics';
    
    protected static ?string $slug = 'analytics';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationGroup = 'Analytics';

    public static function canCreate(): bool
    {
        return false; // No create functionality needed
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnalytics::route('/'),
            'video-performance' => Pages\VideoPerformance::route('/video-performance'),
            'url-performance' => Pages\UrlPerformance::route('/url-performance'),
            // Future custom pages will be added here:
        ];
    }
    
    public static function getNavigationItems(): array
    {
        return [
            // Main Analytics Dashboard
            NavigationItem::make('Analytics Dashboard')
                ->icon('heroicon-o-chart-bar-square')
                ->sort(1)
                ->url(static::getUrl('index'))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.index'))
                ->group('Analytics'),
                
            // Video Performance Analytics
            NavigationItem::make('Video Performance')
                ->icon('heroicon-o-play')
                ->sort(2)
                ->url(static::getUrl('video-performance'))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.video-performance'))
                ->group('Analytics'),
                
            // URL Performance Analytics
            NavigationItem::make('URL Performance')
                ->icon('heroicon-o-link')
                ->sort(3)
                ->url(static::getUrl('url-performance'))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.url-performance'))
                ->group('Analytics'),
        ];
    }
    
    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }
}
