<?php

namespace App\Filament\Dashboard\Pages;

use Filament\Pages\Page;

class Billing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-credit-card';

    protected static string $view = 'filament.billing.pages.billing';

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Billing';

    protected static ?int $navigationSort = 0;

    protected function getHeaderWidgets(): array
    {
        return [
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Billing');
    }

    public function getTitle(): string
    {
        return __('Billing');
    }
} 