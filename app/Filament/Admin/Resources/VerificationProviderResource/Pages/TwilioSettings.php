<?php

namespace App\Filament\Admin\Resources\VerificationProviderResource\Pages;

use App\Filament\Admin\Resources\VerificationProviderResource;
use Filament\Resources\Pages\Page;

class TwilioSettings extends Page
{
    protected static string $resource = VerificationProviderResource::class;

    protected static string $view = 'filament.admin.resources.verification-provider-resource.pages.twilio-settings';

    public function mount(): void
    {
        static::authorizeResourceAccess();
    }
}
