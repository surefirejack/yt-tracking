<?php

namespace App\Filament\Dashboard\Resources\AnalyticsResource\Pages;

use App\Filament\Dashboard\Resources\AnalyticsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnalytics extends EditRecord
{
    protected static string $resource = AnalyticsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
