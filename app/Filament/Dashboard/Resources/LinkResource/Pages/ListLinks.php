<?php

namespace App\Filament\Dashboard\Resources\LinkResource\Pages;

use App\Filament\Dashboard\Resources\LinkResource;
use App\Models\Link;
use App\Jobs\CreateLinkJob;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Link')
                ->icon('heroicon-o-plus')
                ->modal()
                ->form([
                    TextInput::make('original_url')
                        ->label('URL to Shorten')
                        ->url()
                        ->required()
                        ->maxLength(2048)
                        ->placeholder('https://example.com')
                        ->helperText('Enter the URL you want to create a short link for'),
                ])
                ->action(function (array $data): void {
                    // Create the link record with pending status
                    $link = Link::create([
                        'tenant_id' => auth()->user()->current_tenant_id,
                        'original_url' => $data['original_url'],
                        'status' => 'pending',
                    ]);

                    // Dispatch the job to create the short link
                    CreateLinkJob::dispatch($link);

                    Notification::make()
                        ->title('Link creation started')
                        ->body('Your link is being processed. It will appear in the table once completed.')
                        ->success()
                        ->send();
                })
                ->successNotification(null), // Disable default notification since we're using custom one
        ];
    }
}
