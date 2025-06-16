<?php

namespace App\Providers\Filament;

use App\Filament\Billing\Pages\Billing;
use App\Filament\Billing\Resources\OrderResource;
use App\Filament\Billing\Resources\SubscriptionResource;
use App\Filament\Billing\Resources\TransactionResource;
use App\Filament\Resources\TokenResource;
use App\Http\Middleware\UpdateUserLastSeenAt;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;


class BillingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('billing')
            ->path('billing')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make(__('Details'))
                    ->icon('heroicon-o-currency-dollar'),
            ])
            ->widgets([
            ])
            ->resources([
                SubscriptionResource::class,
                TransactionResource::class,
                OrderResource::class,
            ])
            ->navigationItems([
                // Only show our specific resources in navigation
                NavigationItem::make('Subscriptions')
                    ->icon('heroicon-o-fire')
                    ->group(__('Details'))
                    ->url(SubscriptionResource::getUrl()),
                NavigationItem::make('Transactions')
                    ->icon('heroicon-o-currency-dollar')
                    ->group(__('Details'))
                    ->url(TransactionResource::getUrl()),
                NavigationItem::make('Orders')
                    ->icon('heroicon-o-shopping-cart')
                    ->group(__('Details'))
                    ->url(OrderResource::getUrl()),
            ])
            ->pages([
                Pages\Dashboard::class,
                Billing::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                UpdateUserLastSeenAt::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenant(Tenant::class)
            ->tenantMenu()
            ->discoverResources(in: app_path('Filament/Billing/Resources'), for: 'App\\Filament\\Billing\\Resources')
            ->discoverPages(in: app_path('Filament/Billing/Pages'), for: 'App\\Filament\\Billing\\Pages');
    }
}
