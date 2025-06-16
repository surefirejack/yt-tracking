<?php

namespace App\Providers\Filament;

use App\Constants\AnnouncementPlacement;
use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Pages\TenantSettings;
use App\Filament\Dashboard\Pages\TwoFactorAuth\TwoFactorAuth;
use App\Http\Middleware\UpdateUserLastSeenAt;
use App\Models\Tenant;
use App\Services\TenantPermissionService;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use App\Filament\Dashboard\Pages\Billing;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dashboard')
            ->path('dashboard')
            ->brandLogo(asset('images/video-bolt-dark-text.png'))
            ->darkModeBrandLogo(asset('images/video-bolt-light-text.png'))
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('Admin Panel'))
                    ->visible(
                        fn () => auth()->user()->isAdmin()
                    )
                    ->url(fn () => route('filament.admin.pages.dashboard'))
                    ->icon('heroicon-s-cog-8-tooth'),
                MenuItem::make()
                    ->label(__('Settings'))
                    ->url(fn () => \App\Filament\Dashboard\Pages\Settings::getUrl())
                    ->icon('heroicon-s-cog-6-tooth'),
                MenuItem::make()
                    ->label(__('Workspace Settings'))
                    ->visible(
                        function () {
                            $tenantPermissionService = app(TenantPermissionService::class);

                            return $tenantPermissionService->tenantUserHasPermissionTo(
                                Filament::getTenant(),
                                auth()->user(),
                                TenancyPermissionConstants::PERMISSION_UPDATE_TENANT_SETTINGS
                            );
                        }
                    )
                    ->icon('heroicon-s-cog-8-tooth')
                    ->url(fn () => TenantSettings::getUrl()),
                    MenuItem::make()
                        ->label(__('Billing'))
                        ->icon('heroicon-s-credit-card')
                        ->visible(true)
                        ->url(fn () => Billing::getUrl(['tenant' => Filament::getTenant()])),
                    MenuItem::make()
                    ->label(__('2-Factor Authentication'))
                    ->visible(
                        fn () => config('app.two_factor_auth_enabled')
                    )
                    ->url(fn () => TwoFactorAuth::getUrl())
                    ->icon('heroicon-s-cog-8-tooth'),
            ])
            ->discoverResources(in: app_path('Filament/Dashboard/Resources'), for: 'App\\Filament\\Dashboard\\Resources')
            ->discoverPages(in: app_path('Filament/Dashboard/Pages'), for: 'App\\Filament\\Dashboard\\Pages')
            ->pages([
                Pages\Dashboard::class,
                // Billing::class,
            ])
            ->viteTheme('resources/css/filament/dashboard/theme.css')
            ->discoverWidgets(in: app_path('Filament/Dashboard/Widgets'), for: 'App\\Filament\\Dashboard\\Widgets')
            ->widgets([
                \App\Filament\Dashboard\Widgets\DashboardStatsWidget::class,
                \App\Filament\Dashboard\Widgets\SubscribersChartWidget::class,
                \App\Filament\Dashboard\Widgets\ClicksChartWidget::class,
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
            ->renderHook('panels::head.start', function () {
                return view('components.layouts.partials.analytics');
            })
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Analytics')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make()
                    ->label('YouTube Subscribers')
                    ->icon('heroicon-o-folder'),
                NavigationGroup::make()
                    ->label(__('Team'))
                    ->icon('heroicon-s-users')
                    ->collapsed(),
            ])
            ->renderHook(PanelsRenderHook::BODY_START,
                fn (): string => Blade::render("@livewire('announcement.view', ['placement' => '".AnnouncementPlacement::USER_DASHBOARD->value."'])")
            )
            ->authMiddleware([
                Authenticate::class,
            ])->plugins([
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        hasAvatars: false, // Enables the avatar upload form component (default = false)
                        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->myProfileComponents([
                        \App\Livewire\AddressForm::class,
                    ]),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, function () {
                return <<<'HTML'
                <style>
                    .fi-main.is-sticky .fi-header {
                        position: fixed;
                        top: 0;
                        backdrop-filter: blur(12px);
                        z-index: 1000;
                        /* transition: all 0.2s ease-in-out; */
                    }
                    
                    .fi-main.is-sticky .fi-header .fi-header-heading {
                        padding-bottom: 0.5rem;
                        font-size: 1.25rem;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        overflow: hidden;
                        line-height: 1;
                    }
                    
                    .is-sticky.sticky-theme-floating-colored .fi-header {
                        margin: 1rem;
                        padding: 0.75rem 1rem;
                        background-color: rgba(20, 184, 166, 0.9);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        border-radius: 0.5rem;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                        backdrop-filter: blur(12px);
                    }
                    
                    .is-sticky.sticky-theme-floating-colored .fi-header .fi-header-heading {
                        color: white;
                    }
                    
                    .is-sticky.sticky-theme-floating-colored .fi-header .fi-breadcrumbs a {
                        color: rgba(255, 255, 255, 0.8);
                    }
                    
                    .is-sticky.sticky-theme-floating-colored .fi-header .fi-breadcrumbs a:hover,
                    .is-sticky.sticky-theme-floating-colored .fi-header .fi-breadcrumbs a:focus {
                        color: white;
                    }
                    
                    .is-sticky.sticky-theme-floating-colored .fi-header .fi-breadcrumbs svg {
                        color: rgba(255, 255, 255, 0.7);
                    }
                    
                    /* Add padding to body to prevent content jump when header becomes fixed */
                    .fi-main.is-sticky-active {
                        padding-top: var(--sticky-header-height, 80px);
                    }
                </style>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        initializeStickyHeader();
                    });
                    
                    document.addEventListener('livewire:navigated', () => {
                        initializeStickyHeader();
                    });

                    function initializeStickyHeader() {
                        console.log('Sticky Header Plugin - Current URL:', window.location.pathname);
                        
                        const shouldActivate = window.location.pathname.includes('/subscriber-contents/settings');
                        console.log('Sticky Header Plugin - Should activate:', shouldActivate);
                        
                        if (!shouldActivate) {
                            console.log('Sticky Header Plugin - Not activated for this page');
                            return;
                        }

                        const filamentTopbar = document.querySelector(".fi-topbar");
                        const filamentMainContent = document.querySelector(".fi-main");
                        const filamentHeader = document.querySelector(".fi-header");

                        if (filamentTopbar && filamentMainContent && filamentHeader) {
                            console.log('Sticky Header Plugin - Setting up IntersectionObserver...');
                            
                            let trigger = document.querySelector('.filament-sticky-trigger');

                            if (!trigger) {
                                trigger = document.createElement("div");
                                trigger.classList.add("filament-sticky-trigger");
                                filamentMainContent.prepend(trigger);
                            }

                            const theme = 'floating-colored';
                            filamentMainContent.classList.add(`sticky-theme-${theme}`);

                            let offsetHeight = filamentTopbar ? filamentTopbar.offsetHeight : 0;
                            let intersectingTime = null;

                            // Function to calculate and set header positioning
                            const updateHeaderPosition = () => {
                                const mainRect = filamentMainContent.getBoundingClientRect();
                                const marginOffset = 32; // 1rem margin on each side (16px each)
                                
                                filamentHeader.style.left = (mainRect.left + 1) + 'px';
                                filamentHeader.style.width = (mainRect.width - marginOffset) + 'px';
                                filamentHeader.style.top = (offsetHeight + 16) + 'px';
                            };

                            const observer = new IntersectionObserver(
                                ([e]) => {
                                    if (e.isIntersecting) {
                                        if (intersectingTime && (e.time - intersectingTime) < 1000) {
                                            return;
                                        }
                                        intersectingTime = e.time;
                                        console.log('Sticky Header Plugin - Deactivating sticky mode');
                                        filamentMainContent.classList.remove("is-sticky");
                                        return;
                                    }

                                    updateHeaderPosition();
                                    filamentHeader.setAttribute("wire:ignore.self", "true");
                                    console.log('Sticky Header Plugin - Activating sticky mode');
                                    filamentMainContent.classList.add("is-sticky");
                                },
                                {
                                    rootMargin: `-${offsetHeight}px`,
                                    threshold: [0],
                                }
                            );

                            // Update position on window resize
                            window.addEventListener('resize', () => {
                                if (filamentMainContent.classList.contains('is-sticky')) {
                                    updateHeaderPosition();
                                }
                            });

                            observer.observe(trigger);
                            console.log('Sticky Header Plugin - IntersectionObserver setup complete!');
                        } else {
                            console.log('Sticky Header Plugin - Could not find required elements:', { 
                                topbar: !!filamentTopbar, 
                                main: !!filamentMainContent, 
                                header: !!filamentHeader 
                            });
                        }
                    }
                </script>
                HTML;
            })
            ->tenantMenu()
            ->tenant(Tenant::class, 'uuid');
    }
}
