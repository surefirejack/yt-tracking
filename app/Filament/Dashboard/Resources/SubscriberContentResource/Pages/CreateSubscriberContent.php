<?php

namespace App\Filament\Dashboard\Resources\SubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\SubscriberContentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateSubscriberContent extends CreateRecord
{
    protected static string $resource = SubscriberContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set the tenant_id
        $data['tenant_id'] = Filament::getTenant()->id;
        
        // Set default published_at if not provided
        if (empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the content list after creation
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Create Subscriber Content';
    }

    public function getSubheading(): ?string
    {
        return 'Create exclusive content for your YouTube subscribers';
    }
} 