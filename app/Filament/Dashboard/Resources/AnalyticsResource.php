<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\AnalyticsResource\Pages;
use Filament\Resources\Resource;
use Filament\Navigation\NavigationItem;

class AnalyticsResource extends Resource
{
    // No model needed for this dashboard resource
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Analytics';
    
    protected static ?string $slug = 'analytics';
    
    protected static ?int $navigationSort = 3;

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
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getNavigationIcon())
                ->sort(static::getNavigationSort())
                ->url(static::getUrl('index'))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.*')),
        ];
    }
}
