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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn() => $this->refreshLinks()),

            Actions\Action::make('quick_create')
                ->label('Create a Link')
                ->icon('heroicon-o-link')
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

    public function refreshLinks(): void
    {
        try {
            $tenant = Filament::getTenant();
            $tenantId = $tenant->id;

            // Call Dub API to get fresh link data
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.dub.api_key'),
                'Content-Type' => 'application/json',
            ])->get("https://api.dub.co/links", [
                'tenantId' => $tenantId
            ]);

            if (!$response->successful()) {
                throw new \Exception('API request failed: ' . $response->body());
            }

            $apiLinks = $response->json();
            $updatedCount = 0;

            // Update existing links with fresh API data
            foreach ($apiLinks as $apiLink) {
                $dubId = $apiLink['id'] ?? null;
                
                if (!$dubId) {
                    continue;
                }

                // Find link by dub_id
                $existingLink = Link::where('tenant_id', $tenantId)
                    ->where('dub_id', $dubId)
                    ->first();

                if ($existingLink) {
                    // Update with fresh data from API
                    $existingLink->update([
                        'clicks' => $apiLink['clicks'] ?? $existingLink->clicks,
                        'leads' => $apiLink['leads'] ?? $existingLink->leads,
                        'last_clicked' => isset($apiLink['lastClicked']) ? 
                            \Carbon\Carbon::parse($apiLink['lastClicked']) : 
                            $existingLink->last_clicked,
                        'updated_at' => now(),
                    ]);
                    
                    $updatedCount++;
                }
            }

            // Show success notification
            Notification::make()
                ->title('Links refreshed successfully!')
                ->body($updatedCount . ' links updated with fresh data from Dub API.')
                ->success()
                ->send();

            Log::info('Links refreshed successfully', [
                'tenant_id' => $tenantId,
                'updated_count' => $updatedCount,
                'total_api_links' => count($apiLinks)
            ]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to refresh links', [
                'tenant_id' => Filament::getTenant()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Show error notification
            Notification::make()
                ->title('Failed to refresh links')
                ->body('There was an error fetching fresh data from Dub API. Please try again.')
                ->danger()
                ->send();
        }
    }
}
