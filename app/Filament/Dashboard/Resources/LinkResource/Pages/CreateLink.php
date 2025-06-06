<?php

namespace App\Filament\Dashboard\Resources\LinkResource\Pages;

use App\Filament\Dashboard\Resources\LinkResource;
use App\Jobs\CreateLinkJob;
use App\Services\TagService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;

class CreateLink extends CreateRecord
{
    protected static string $resource = LinkResource::class;

    protected array $tagIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = Filament::getTenant()->id;
        $data['status'] = 'pending';
        
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

    protected function afterCreate(): void
    {
        // Sync the tag relationship
        if (isset($this->tagIds)) {
            $this->record->tagModels()->sync($this->tagIds);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);
        
        // Dispatch the job to create the short link
        CreateLinkJob::dispatch($record);
        
        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Link creation started! Your link is being processed.';
    }
}
