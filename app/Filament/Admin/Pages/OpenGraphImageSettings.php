<?php

namespace App\Filament\Admin\Pages;

use App\Services\ConfigService;
use Filament\Pages\Page;

class OpenGraphImageSettings extends Page
{
    protected static string $view = 'filament.admin.pages.open-graph-image-settings';

    protected static ?string $navigationGroup = 'Settings';

    public static function canAccess(): bool
    {
        $configService = app()->make(ConfigService::class);

        return $configService->isAdminSettingsEnabled()
            && auth()->user()
            && auth()->user()->hasPermissionTo('update settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Open Graph Images');
    }
}
