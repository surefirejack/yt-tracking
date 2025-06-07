<?php

namespace App\Filament\Dashboard\Resources\YtVideoResource\Pages;

use App\Filament\Dashboard\Resources\YtVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideos extends ListRecords
{
    protected static string $resource = YtVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
