<?php

namespace App\Filament\Dashboard\Pages;

use App\Constants\TenancyPermissionConstants;
use App\Services\TenantPermissionService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class TenantSettings extends Page
{
    protected static string $view = 'filament.dashboard.pages.tenant-settings';

    public function getHeading(): string|Htmlable
    {
        return __('Workspace Settings');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Workspace Settings');
    }

    public static function canAccess(): bool
    {
        $tenantPermissionService = app(TenantPermissionService::class); // a bit ugly, but this is the Filament way :/

        return $tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            TenancyPermissionConstants::PERMISSION_UPDATE_TENANT_SETTINGS
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
