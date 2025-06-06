<?php

namespace App\Filament\Admin\Pages;

use App\Services\ConfigService;
use Filament\Pages\Page;

class InvoiceSettings extends Page
{
    protected static string $view = 'filament.admin.pages.invoice-settings';

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
        return __('Invoices');
    }
}
