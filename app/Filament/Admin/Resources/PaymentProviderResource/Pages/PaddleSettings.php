<?php

namespace App\Filament\Admin\Resources\PaymentProviderResource\Pages;

use App\Filament\Admin\Resources\PaymentProviderResource;
use App\Services\ConfigService;
use Filament\Resources\Pages\Page;

class PaddleSettings extends Page
{
    protected static string $resource = PaymentProviderResource::class;

    protected static string $view = 'filament.admin.resources.payment-provider-resource.pages.paddle-settings';

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
