<?php

namespace App\Filament\Billing\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;

class Billing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-credit-card';

    protected static string $view = 'filament.billing.pages.billing';

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Billing';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'billing';

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

    public static function getRoutes(): \Closure
    {
        return function () {
            Route::get('/', static::class)
                ->name(static::getSlug())
                ->middleware([
                    'auth',
                    'web',
                ]);
        };
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return route('filament.billing.pages.billing', $parameters);
    }
} 