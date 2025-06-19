<?php

namespace App\Filament\Dashboard\Resources\EmailSubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\EmailSubscriberContentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateEmailSubscriberContent extends CreateRecord
{
    protected static string $resource = EmailSubscriberContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set the tenant_id
        $data['tenant_id'] = Filament::getTenant()->id;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the content list after creation
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Create Email-Gated Content';
    }

    public function getSubheading(): ?string
    {
        return 'Create content that requires email verification to access';
    }
} 