<?php

namespace App\Filament\Dashboard\Resources\SubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\SubscriberContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;

class ListSubscriberContent extends ListRecords
{
    protected static string $resource = SubscriberContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Content')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Could add widgets here for analytics, stats, etc.
        ];
    }

    public function getTitle(): string
    {
        return 'Subscriber Content';
    }

    public function getSubheading(): ?string
    {
        $tenant = Filament::getTenant();
        $channelname = $tenant->ytChannel?->channel_name ?? 'your channel';
        
        return "Manage exclusive content for your YouTube subscribers. Content will be available at /s/{$channelname}/{slug}";
    }
} 