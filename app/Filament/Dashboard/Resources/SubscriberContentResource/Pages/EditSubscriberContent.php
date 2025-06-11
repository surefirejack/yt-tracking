<?php

namespace App\Filament\Dashboard\Resources\SubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\SubscriberContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;

class EditSubscriberContent extends EditRecord
{
    protected static string $resource = SubscriberContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Content')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(function () {
                    $tenant = Filament::getTenant();
                    $channelname = strtolower($tenant->ytChannel?->channel_name ?? 'channel');
                    return route('subscriber.content', [
                        'channelname' => $channelname,
                        'slug' => $this->record->slug
                    ]);
                })
                ->openUrlInNewTab(),

            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the content list after saving
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return "Edit Content: {$this->record->title}";
    }

    public function getSubheading(): ?string
    {
        $tenant = Filament::getTenant();
        $channelname = strtolower($tenant->ytChannel?->channel_name ?? 'channel');
        
        return "URL: /s/{$channelname}/{$this->record->slug}";
    }
} 