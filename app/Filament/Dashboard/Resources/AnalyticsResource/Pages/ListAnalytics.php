<?php

namespace App\Filament\Dashboard\Resources\AnalyticsResource\Pages;

use App\Filament\Dashboard\Resources\AnalyticsResource;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class ListAnalytics extends Page
{
    protected static string $resource = AnalyticsResource::class;

    protected static string $view = 'filament.analytics.dashboard';
    
    protected static ?string $title = 'Analytics Dashboard';
    
    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for now - will add refresh button later
        ];
    }
}
