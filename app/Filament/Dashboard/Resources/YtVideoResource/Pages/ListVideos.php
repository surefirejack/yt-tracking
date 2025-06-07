<?php

namespace App\Filament\Dashboard\Resources\YtVideoResource\Pages;

use App\Filament\Dashboard\Resources\YtVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class ListVideos extends ListRecords
{
    protected static string $resource = YtVideoResource::class;

    public function getTitle(): string
    {
        $tenant = Filament::getTenant();
        
        // Check if we have a channel name using the YtChannel relationship
        if ($tenant->ytChannel && !empty($tenant->ytChannel->name)) {
            return "Videos on {$tenant->ytChannel->name}";
        }
        
        return "Your Channel's Videos";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addChannel')
                ->label('Add YouTube Channel')
                ->icon('heroicon-o-plus')
                ->visible(fn () => !Filament::getTenant()->ytChannel)
                ->modalSubmitActionLabel('Save')
                ->form([
                    Forms\Components\TextInput::make('channel')
                        ->label('YouTube Channel URL or Handle')
                        ->placeholder('https://youtube.com/@channel or @channelhandle')
                        ->required()
                        ->helperText('Enter either your channel URL or your channel handle (e.g., @yourchannelname)')
                ])
                ->action(function (array $data) {
                    // Here you would process the channel URL/handle
                    // For now, we'll just show a notification
                    Notification::make()
                        ->title('Channel Added')
                        ->body('Your YouTube channel has been added successfully!')
                        ->success()
                        ->send();
                })
        ];
    }

    protected function getEmptyStateActions(): array
    {
        return [
            Actions\Action::make('addChannel')
                ->label('Add Your YouTube Channel')
                ->icon('heroicon-o-plus')
                ->button()
                ->color('primary')
                ->modalSubmitActionLabel('Save')
                ->form([
                    Forms\Components\TextInput::make('channel')
                        ->label('YouTube Channel URL or Handle')
                        ->placeholder('https://youtube.com/@channel or @channelhandle')
                        ->required()
                        ->helperText('Enter either your channel URL or your channel handle (e.g., @yourchannelname)')
                ])
                ->action(function (array $data) {
                    // Here you would process the channel URL/handle
                    // For now, we'll just show a notification
                    Notification::make()
                        ->title('Channel Added')
                        ->body('Your YouTube channel has been added successfully!')
                        ->success()
                        ->send();
                })
        ];
    }
}
