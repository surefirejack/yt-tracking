<?php

namespace App\Filament\Dashboard\Resources\YtVideoResource\Pages;

use App\Filament\Dashboard\Resources\YtVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideo extends EditRecord
{
    protected static string $resource = YtVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
