<?php

namespace App\Filament\Dashboard\Resources\LinkResource\Pages;

use App\Filament\Dashboard\Resources\LinkResource;
use App\Models\Link;
use App\Jobs\CreateLinkJob;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Facades\Filament;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_create')
                ->label('Quick Create')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->modal()
                ->modalSubmitActionLabel('Save')
                ->form([
                    TextInput::make('original_url')
                        ->label('URL to Shorten')
                        ->url()
                        ->required()
                        ->maxLength(2048)
                        ->placeholder('https://example.com')
                        ->helperText('Enter the URL you want to create a short link for'),
                    
                    TextInput::make('title')
                        ->label('Name/Title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Give your link a memorable name')
                        ->helperText('This will help you identify the link later'),
                ])
                ->action(function (array $data): void {
                    // Create the link record with pending status and title
                    $link = Link::create([
                        'tenant_id' => Filament::getTenant()->id,
                        'original_url' => $data['original_url'],
                        'title' => $data['title'],
                        'domain' => config('services.dub.main_domain'),
                        'status' => 'pending',
                    ]);

                    // Dispatch the job to create the short link
                    CreateLinkJob::dispatch($link);

                    Notification::make()
                        ->title('Quick link created!')
                        ->body('Do you want to configure this link?')
                        ->success()
                        ->persistent()
                        ->actions([
                            NotificationAction::make('yes')
                                ->label('Yes')
                                ->button()
                                ->color('primary')
                                ->url(fn () => LinkResource::getUrl('edit', ['record' => $link])),
                            NotificationAction::make('no')
                                ->label('No')
                                ->button()
                                ->color('gray')
                                ->close(),
                        ])
                        ->send();
                }),
        ];
    }
}
