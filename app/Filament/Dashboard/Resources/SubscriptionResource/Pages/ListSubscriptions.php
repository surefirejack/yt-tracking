<?php

namespace App\Filament\Dashboard\Resources\SubscriptionResource\Pages;

use App\Filament\Dashboard\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getView(): string
    {
        if ($this->getTableRecords()->count() === 0) {
            return 'filament.dashboard.resources.subscription-resource.pages.subscriptions';
        }

        return parent::getView();
    }
}
