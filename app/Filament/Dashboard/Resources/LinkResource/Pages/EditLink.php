<?php

namespace App\Filament\Dashboard\Resources\LinkResource\Pages;

use App\Filament\Dashboard\Resources\LinkResource;
use App\Services\TagService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Filament\Support\Enums\ActionSize;

class EditLink extends EditRecord
{
    protected static string $resource = LinkResource::class;

    protected array $tagIds = [];

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save Your Changes')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->extraAttributes([
                    'wire:dirty.class' => 'block',
                    'wire:dirty.class.remove' => 'hidden',
                    'class' => 'hidden',
                ])
                ->color('primary'),
            
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->extraAttributes([
                    'wire:dirty.class' => 'block',
                    'wire:dirty.class.remove' => 'hidden',
                    'class' => 'hidden',
                ])
                ->action(function () {
                    // Reset form to original record data
                    $this->fillForm();
                    
                    // Optional: You can also redirect to refresh the page
                    // return redirect()->to($this->getResource()::getUrl('edit', ['record' => $this->record]));
                })
                ->requiresConfirmation()
                ->modalHeading('Discard Changes?')
                ->modalDescription('Are you sure you want to discard your unsaved changes?')
                ->modalSubmitActionLabel('Yes, discard changes'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing tags from the relationship
        if ($this->record) {
            $data['tags'] = $this->record->tagModels()->pluck('name')->toArray();
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle tag creation and relationship management
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagService = new TagService();
            $tags = $tagService->getOrCreateTags($data['tags'], Filament::getTenant()->id);
            
            // Store tag IDs for later relationship sync
            $this->tagIds = collect($tags)->pluck('id')->toArray();
        } else {
            $this->tagIds = [];
        }
        
        // Remove tags from the data as it will be handled via relationships
        unset($data['tags']);
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync the tag relationship
        if (isset($this->tagIds)) {
            $this->record->tagModels()->sync($this->tagIds);
        } else {
            $this->record->tagModels()->sync([]);
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);
        
        // Only make API call if the link has a dub_id (meaning it was successfully created)
        if ($record->dub_id) {
            $this->updateLinkViaDubAPI($record, $data);
        }
        
        return $record;
    }

    protected function updateLinkViaDubAPI(Model $record, array $data): void
    {
        try {
            // Get Dub API configuration
            $apiKey = config('services.dub.api_key');
            $baseUrl = config('services.dub.update_link_url', 'https://api.dub.co/links');
            
            if (!$apiKey) {
                throw new \Exception('Dub API key is not configured');
            }

            // Build the API URL with the link ID
            $apiUrl = rtrim($baseUrl, '/') . '/' . $record->dub_id;

            // Build the payload from form data
            $payload = $this->buildPayload($data, $record);

            Log::info('Updating link via Dub API', [
                'link_id' => $record->id,
                'dub_id' => $record->dub_id,
                'tenant_id' => $record->tenant_id,
            ]);

            // Make the PATCH request to Dub API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->patch($apiUrl, $payload);

            if ($response->successful()) {
                // Update local record with response data
                $responseData = $response->json();
                $this->updateRecordFromResponse($record, $responseData);

                Notification::make()
                    ->title('Link updated successfully!')
                    ->body('Your link has been updated')
                    ->success()
                    ->send();

                Log::info('Link updated via Dub API', [
                    'link_id' => $record->id,
                    'dub_id' => $record->dub_id,
                    'tenant_id' => $record->tenant_id,
                ]);
            } else {
                throw new \Exception('Dub API request failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to update link via Dub API', [
                'link_id' => $record->id,
                'dub_id' => $record->dub_id,
                'tenant_id' => $record->tenant_id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Link update failed')
                ->body('Failed to update link in Dub: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function buildPayload(array $data, Model $record = null): array
    {
        $payload = [];

        // Map form fields to API fields
        $fieldMapping = [
            'original_url' => 'url',
            'title' => 'title',
            'description' => 'description',
            'external_id' => 'externalId',
            'folder_id' => 'folderId',
            'comments' => 'comments',
            'expires_at' => 'expiresAt',
            'expired_url' => 'expiredUrl',
            'password' => 'password',
            'image' => 'image',
            'video' => 'video',
            'ios' => 'ios',
            'android' => 'android',
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'utm_term' => 'utm_term',
            'utm_content' => 'utm_content',
        ];

        // Always include tenantId and externalId for identification
        if ($record) {
            $payload['tenantId'] = (string) $record->tenant_id;
            $payload['externalId'] = (string) $record->id;
        }

        // Add basic fields
        foreach ($fieldMapping as $localField => $apiField) {
            if (isset($data[$localField]) && !empty($data[$localField])) {
                // Handle datetime fields - convert to UTC for API
                if (in_array($localField, ['expires_at'])) {
                    if ($data[$localField] instanceof \DateTime) {
                        // Convert user's local time to UTC properly
                        $dateTime = \Carbon\Carbon::parse($data[$localField]);
                        
                        // Convert to UTC for API
                        $utcDateTime = $dateTime->utc();
                        
                        // Log the conversion process
                        Log::info('DateTime conversion debug', [
                            'original_input' => $data[$localField],
                            'parsed_datetime' => $dateTime->toString(),
                            'user_timezone' => $dateTime->timezone->getName(),
                            'utc_datetime' => $utcDateTime->toString(),
                            'sending_to_dub' => $utcDateTime->toISOString(),
                        ]);
                        
                        // Send proper UTC time with Z suffix
                        $payload[$apiField] = $utcDateTime->toISOString();
                    } elseif (is_string($data[$localField])) {
                        // If it's a string, parse it in the application timezone and convert to UTC
                        $dateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $data[$localField]);
                        
                        // Convert to UTC for API
                        $utcDateTime = $dateTime->utc();
                        
                        Log::info('String DateTime conversion debug', [
                            'original_string' => $data[$localField],
                            'parsed_datetime' => $dateTime->toString(),
                            'user_timezone' => $dateTime->timezone->getName(),
                            'utc_datetime' => $utcDateTime->toString(),
                            'sending_to_dub' => $utcDateTime->toISOString(),
                        ]);
                        
                        // Send proper UTC time with Z suffix
                        $payload[$apiField] = $utcDateTime->toISOString();
                    }
                } else {
                    $payload[$apiField] = $data[$localField];
                }
            }
        }

        // Handle boolean fields
        $booleanFields = [
            'track_conversion' => 'trackConversion',
            'archived' => 'archived',
            'public_stats' => 'publicStats',
            'proxy' => 'proxy',
            'rewrite' => 'rewrite',
            'do_index' => 'doIndex',
        ];

        foreach ($booleanFields as $localField => $apiField) {
            if (isset($data[$localField])) {
                $payload[$apiField] = (bool) $data[$localField];
            }
        }

        // Handle tags - use the stored tagIds instead of relationship data since relationship hasn't been synced yet
        if (isset($this->tagIds) && !empty($this->tagIds)) {
            // Get tag names from the stored tag IDs
            $tagNames = \App\Models\Tag::whereIn('id', $this->tagIds)->pluck('name')->toArray();
            if (!empty($tagNames)) {
                $payload['tagNames'] = $tagNames;
            }
        } else {
            // Fallback to relationship data (for cases where tags weren't modified)
            if ($record && $record->tagModels()->exists()) {
                $tagNames = $record->tagModels()->pluck('name')->toArray();
                if (!empty($tagNames)) {
                    $payload['tagNames'] = $tagNames;
                }
            }
        }

        if (isset($data['webhook_ids']) && !empty($data['webhook_ids'])) {
            // Try to decode JSON, fallback to string
            $webhookIds = is_string($data['webhook_ids']) ? json_decode($data['webhook_ids'], true) : $data['webhook_ids'];
            if (is_array($webhookIds)) {
                $payload['webhookIds'] = $webhookIds;
            }
        }

        if (isset($data['geo']) && !empty($data['geo'])) {
            // Try to decode JSON for geo data
            $geo = is_string($data['geo']) ? json_decode($data['geo'], true) : $data['geo'];
            if (is_array($geo)) {
                $payload['geo'] = $geo;
            }
        }

        return $payload;
    }

    protected function updateRecordFromResponse(Model $record, array $responseData): void
    {
        $updateData = [];

        // Map response fields back to local fields
        $responseMapping = [
            'title' => 'title',
            'description' => 'description',
            'url' => 'url',
            'shortLink' => 'short_link',
            'trackConversion' => 'track_conversion',
            'archived' => 'archived',
            'publicStats' => 'public_stats',
            'proxy' => 'proxy',
            'rewrite' => 'rewrite',
            'doIndex' => 'do_index',
            'image' => 'image',
            'video' => 'video',
            'ios' => 'ios',
            'android' => 'android',
            'qrCode' => 'qr_code',
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'utm_term' => 'utm_term',
            'utm_content' => 'utm_content',
            'clicks' => 'clicks',
            'leads' => 'leads',
            'sales' => 'sales',
            'saleAmount' => 'sale_amount',
            'externalId' => 'external_id',
            'tenantId' => 'tenant_id_dub',
        ];

        foreach ($responseMapping as $apiField => $localField) {
            if (isset($responseData[$apiField])) {
                $updateData[$localField] = $responseData[$apiField];
            }
        }

        // Handle datetime fields
        if (isset($responseData['expiresAt']) && !empty($responseData['expiresAt'])) {
            $updateData['expires_at'] = \Carbon\Carbon::parse($responseData['expiresAt']);
        }

        if (isset($responseData['lastClicked']) && !empty($responseData['lastClicked'])) {
            $updateData['last_clicked'] = \Carbon\Carbon::parse($responseData['lastClicked']);
        }

        // Handle tags from Dub API response - sync with relationship and update tags column
        if (isset($responseData['tags']) && is_array($responseData['tags'])) {
            $tagNames = array_column($responseData['tags'], 'name');
            $updateData['tags'] = $tagNames; // Keep the tags column for legacy compatibility
            
            // Sync tag relationship
            if (!empty($tagNames)) {
                $tagService = new TagService();
                $tags = $tagService->getOrCreateTags($tagNames, $record->tenant_id);
                $tagIds = collect($tags)->pluck('id')->toArray();
                $record->tagModels()->sync($tagIds);
            } else {
                $record->tagModels()->sync([]);
            }
        }

        if (isset($responseData['folderId'])) {
            $updateData['folder_id'] = $responseData['folderId'];
        }

        if (isset($responseData['webhookIds']) && is_array($responseData['webhookIds'])) {
            $updateData['webhook_ids'] = $responseData['webhookIds'];
        }

        if (isset($responseData['geo']) && is_array($responseData['geo'])) {
            $updateData['geo'] = $responseData['geo'];
        }

        if (!empty($updateData)) {
            $record->update($updateData);
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null; // We handle notifications in updateLinkViaDubAPI
    }
}
