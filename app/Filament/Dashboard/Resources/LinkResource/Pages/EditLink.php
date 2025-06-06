<?php

namespace App\Filament\Dashboard\Resources\LinkResource\Pages;

use App\Filament\Dashboard\Resources\LinkResource;
use App\Jobs\UpdateLinkJob;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLink extends EditRecord
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $originalData = $record->toArray();
        $record = parent::handleRecordUpdate($record, $data);
        
        // Check if we need to update the link via API
        $fieldsToSync = [
            'original_url' => 'url',
            'title' => 'title',
            'description' => 'description',
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'utm_term' => 'utm_term',
            'utm_content' => 'utm_content',
        ];

        $updateData = [];
        $hasChanges = false;

        foreach ($fieldsToSync as $localField => $apiField) {
            if (isset($data[$localField]) && $originalData[$localField] !== $data[$localField]) {
                $updateData[$apiField] = $data[$localField];
                $hasChanges = true;
            }
        }

        // Only dispatch update job if there are changes and the link has a dub_id
        if ($hasChanges && $record->dub_id && $record->status === 'completed') {
            UpdateLinkJob::dispatch($record, $updateData);
            
            // Update status to processing
            $record->update(['status' => 'processing']);
        }
        
        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Link updated successfully!';
    }
}
