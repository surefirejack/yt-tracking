<?php

namespace App\Filament\Admin\Resources\OauthLoginProviderResource\Pages;

use App\Filament\Admin\Resources\OauthLoginProviderResource;
use App\Services\ConfigService;
use Filament\Resources\Pages\Page;

class GoogleSettings extends Page
{
    protected static string $resource = OauthLoginProviderResource::class;

    protected static string $view = 'filament.admin.resources.oauth-login-provider-resource.pages.google-settings';

    public function mount(): void
    {
        static::authorizeResourceAccess();
    }

    public static function canAccess(array $parameters = []): bool
    {
        $configService = app()->make(ConfigService::class);

        return $configService->isAdminSettingsEnabled();
    }
}
