<?php

namespace App\Livewire;

use App\Models\Link;
use App\Jobs\CreateLinkJob;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Facades\Filament;
use Livewire\Component;
use App\Filament\Dashboard\Resources\LinkResource;

class QuickCreateLink extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public function quickCreateAction(): Action
    {
        return Action::make('quickCreate')
            ->label('Create a Link')
            ->icon('heroicon-o-link')
            ->color('info')
            ->size('sm')
            ->button()
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
                // Check if we have a tenant
                $tenant = Filament::getTenant();
                if (!$tenant) {
                    Notification::make()
                        ->title('Error')
                        ->body('No tenant context available.')
                        ->danger()
                        ->send();
                    return;
                }

                // Create the link record with pending status and title
                $link = Link::create([
                    'tenant_id' => $tenant->id,
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
            });
    }

    public function render()
    {
        return view('livewire.quick-create-link');
    }
} 