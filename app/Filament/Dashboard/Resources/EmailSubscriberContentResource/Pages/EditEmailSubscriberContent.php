<?php

namespace App\Filament\Dashboard\Resources\EmailSubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\EmailSubscriberContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;

class EditEmailSubscriberContent extends EditRecord
{
    protected static string $resource = EmailSubscriberContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Content')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(function () {
                    $tenant = Filament::getTenant();
                    $channelname = $tenant->getChannelName() ?? 'channel';
                    return route('email-gated-content.show', [
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
        $channelname = $tenant->getChannelName() ?? 'channel';
        
        return "URL: /p/{$channelname}/{$this->record->slug}";
    }
} 