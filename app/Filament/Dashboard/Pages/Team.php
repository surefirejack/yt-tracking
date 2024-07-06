<?php

namespace App\Filament\Dashboard\Pages;

use Filament\Pages\Page;

class Team extends Page
{
    protected static ?string $navigationGroup = 'Members';

    protected static string $view = 'filament.dashboard.pages.team';
}
