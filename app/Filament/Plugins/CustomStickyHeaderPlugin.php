<?php

namespace App\Filament\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;

class CustomStickyHeaderPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool | Closure | null $isColored = null;

    protected bool | Closure | null $isFloating = null;

    protected bool | Closure | null $stickOnListPages = null;

    protected array | Closure | null $enabledOnRoutes = null;

    public function boot(Panel $panel): void
    {
        //
    }

    public function register(Panel $panel): void
    {
        // Add JavaScript to handle conditional activation
        $panel->renderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => $this->getStickyHeaderScript()
        );
    }

    public function colored(bool | Closure $condition = true): static
    {
        $this->isColored = $condition;
        return $this;
    }

    public function floating(bool | Closure $condition = true): static
    {
        $this->isFloating = $condition;
        return $this;
    }

    public function stickOnListPages(bool | Closure $condition = true): static
    {
        $this->stickOnListPages = $condition;
        return $this;
    }

    public function enabledOnRoutes(array | Closure $routes): static
    {
        $this->enabledOnRoutes = $routes;
        return $this;
    }

    public function getId(): string
    {
        return 'custom-sticky-header';
    }

    public function isColored(): bool
    {
        return $this->evaluate($this->isColored) ?? false;
    }

    public function isFloating(): bool
    {
        return $this->evaluate($this->isFloating) ?? false;
    }

    public function getTheme(): string
    {
        if ($this->isFloating()) {
            if ($this->isColored()) {
                return 'floating-colored';
            }
            return 'floating';
        }
        return 'default';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function shouldStickOnListPages(): bool
    {
        return $this->evaluate($this->stickOnListPages) ?? true;
    }

    protected function getStickyHeaderScript(): string
    {
        $enabledRoutes = $this->evaluate($this->enabledOnRoutes);
        $theme = $this->getTheme();
        
        if ($enabledRoutes === null) {
            // If no route restrictions, enable globally
            $routeCheck = 'true';
        } else {
            // Check for the actual settings URL structure
            $routeCheck = "window.location.pathname.includes('/subscriber-contents/settings')";
        }

        return <<<HTML
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Debug: Log current URL and condition
                console.log('Current URL:', window.location.pathname);
                console.log('Route check condition:', {$routeCheck});
                console.log('Should activate:', {$routeCheck});
                
                const shouldActivate = {$routeCheck};
                
                if (shouldActivate) {
                    console.log('Activating sticky header...');
                    
                    // Try multiple header selectors
                    const headerSelectors = [
                        '.fi-header',
                        '.fi-topbar',
                        'header',
                        '[data-fi-header]',
                        '.fi-page-header'
                    ];
                    
                    let header = null;
                    for (const selector of headerSelectors) {
                        header = document.querySelector(selector);
                        if (header) {
                            console.log('Found header with selector:', selector);
                            break;
                        }
                    }
                    
                    if (header) {
                        console.log('Applying sticky header styles...');
                        header.classList.add('fi-sticky-header');
                        header.classList.add('fi-sticky-header-{$theme}');
                        
                        // Apply styles immediately
                        header.style.position = 'fixed';
                        header.style.top = '0';
                        header.style.left = '0';
                        header.style.right = '0';
                        header.style.zIndex = '50';
                        header.style.transition = 'transform 0.3s ease-in-out';
                        header.style.background = 'rgb(20 184 166)';
                        header.style.color = 'white';
                        header.style.margin = '0.5rem';
                        header.style.borderRadius = '0.5rem';
                        header.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
                        
                        // Add sticky behavior
                        let lastScrollTop = 0;
                        window.addEventListener('scroll', function() {
                            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                            
                            if (scrollTop > lastScrollTop && scrollTop > 100) {
                                // Scrolling down
                                header.style.transform = 'translateY(-100%)';
                            } else {
                                // Scrolling up
                                header.style.transform = 'translateY(0)';
                            }
                            lastScrollTop = scrollTop;
                        });
                        
                        console.log('Sticky header activated successfully!');
                    } else {
                        console.log('No header element found with any selector');
                    }
                } else {
                    console.log('Sticky header not activated for this page');
                }
            });
        </script>
        HTML;
    }
} 