<?php

namespace App\Filament\Dashboard\Pages;

use App\Services\TenantPermissionManager;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class Team extends Page
{
    protected static ?string $navigationGroup = 'Team';

    protected static string $view = 'filament.dashboard.pages.team';

    private static TenantPermissionManager $tenantPermissionManager;

    public function boot(TenantPermissionManager $tenantPermissionManager)
    {
        self::$tenantPermissionManager = $tenantPermissionManager;
    }

    public static function getNavigationLabel(): string
    {
        return __('Team Members');
    }

    public static function canAccess(): bool
    {
        $tenantPermissionManager = app(TenantPermissionManager::class); // a bit ugly, but this is the Filament way :/
        return $tenantPermissionManager->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            'tenancy: manage team'
        );
    }
}
