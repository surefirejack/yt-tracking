<?php

namespace App\Filament\Dashboard\Resources\EmailSubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\EmailSubscriberContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;

class ListEmailSubscriberContent extends ListRecords
{
    protected static string $resource = EmailSubscriberContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Email-Gated Content')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('settings')
                ->label('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(fn() => EmailSubscriberContentResource::getUrl('settings')),
                
            Actions\Action::make('analytics')
                ->label('Analytics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->url(fn() => EmailSubscriberContentResource::getUrl('analytics')),
        ];
    }

    public function getTitle(): string
    {
        return 'Email-Gated Content';
    }

    public function getSubheading(): ?string
    {
        $tenant = Filament::getTenant();
        $channelname = $tenant->getChannelName() ?? 'your-channel';
        
        return "Manage content that requires email verification to access. Content will be available at /p/{$channelname}/{slug}";
    }
} 