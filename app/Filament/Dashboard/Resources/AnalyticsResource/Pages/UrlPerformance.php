<?php

namespace App\Filament\Dashboard\Resources\AnalyticsResource\Pages;

use App\Filament\Dashboard\Resources\AnalyticsResource;
use App\Models\Link;
use App\Models\YtVideo;
use App\Services\DubAnalyticsService;
use App\Enums\AnalyticsInterval;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Filament\Actions;
use Illuminate\Contracts\View\View;

class UrlPerformance extends Page
{
    protected static string $resource = AnalyticsResource::class;

    protected static string $view = 'filament.analytics.url-performance';
    
    protected static ?string $title = 'URL Performance Analytics';
    
    protected static ?string $navigationLabel = 'URL Performance';
    
    // Public properties for the selected values
    public ?string $selectedDestinationUrl = null;
    public ?string $selectedInterval = null;
    public ?array $analyticsData = null;
    public ?array $processedMetrics = null;
    public ?array $linkBreakdown = null;
    
    // UTM filter properties
    public ?string $utmSource = null;
    public ?string $utmMedium = null;
    public ?string $utmCampaign = null;
    public ?string $utmTerm = null;
    public ?string $utmContent = null;
    
    protected DubAnalyticsService $analyticsService;

    public function boot(): void
    {
        $this->analyticsService = new DubAnalyticsService();
    }
    
    public function mount(): void
    {
        $this->selectedInterval = AnalyticsInterval::default()->value;
        
        // Restore filter state from session
        $this->restoreFilterState();
        
        // If we have a selected destination URL from the restored state, load analytics data automatically
        if ($this->selectedDestinationUrl) {
            $this->loadAnalyticsData();
        }
    }
    
    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    protected function getDestinationUrlOptions(): array
    {
        try {
            $tenant = Filament::getTenant();
            
            if (!$tenant) {
                return [];
            }
            
            // Get unique destination URLs from links table
            $destinationUrls = Link::where('tenant_id', $tenant->id)
                ->whereNotNull('original_url')
                ->select('original_url')
                ->distinct()
                ->orderBy('original_url')
                ->pluck('original_url')
                ->filter()
                ->mapWithKeys(function ($url) {
                    // Use URL as both key and display value, but limit display length
                    $displayUrl = strlen($url) > 60 ? substr($url, 0, 57) . '...' : $url;
                    return [$url => $displayUrl];
                })
                ->toArray();
            
            return $destinationUrls;
            
        } catch (\Exception $e) {
            \Log::error('Error getting destination URL options', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function loadAnalyticsData(): void
    {
        if (!$this->selectedDestinationUrl) {
            $this->analyticsData = null;
            $this->processedMetrics = null;
            $this->linkBreakdown = null;
            return;
        }
        
        try {
            $tenant = Filament::getTenant();
            
            if (!$tenant) {
                throw new \Exception('No tenant context available');
            }
            
            // Get all links that point to this destination URL
            $links = Link::where('tenant_id', $tenant->id)
                ->where('original_url', $this->selectedDestinationUrl)
                ->get(['id', 'dub_id', 'title']);
            
            if ($links->isEmpty()) {
                throw new \Exception('No links found for this destination URL');
            }
            
            // Get analytics data for the destination URL (aggregated from all links)
            $this->analyticsData = $this->analyticsService->getDestinationUrlAnalytics(
                $tenant->id,
                $this->selectedDestinationUrl,
                ['interval' => $this->selectedInterval]
            );
            
            // Process the analytics data to get aggregated metrics
            $this->processedMetrics = $this->analyticsService->processAnalyticsData($this->analyticsData);
            
            // Build breakdown per link with video association and individual analytics
            $linkBreakdown = [];
            
            foreach ($links as $link) {
                if (!$link->dub_id) {
                    // Skip links without dub_id, but include them with zero metrics
                    $associatedVideos = $link->ytVideos; // many-to-many
                    $videoTitles = $associatedVideos->pluck('title')->toArray();
                    $videoIds = $associatedVideos->pluck('id')->toArray();
                    
                    $linkBreakdown[] = [
                        'link_id' => $link->id,
                        'link_title' => $link->title,
                        'dub_id' => $link->dub_id,
                        'video_title' => !empty($videoTitles) ? implode(', ', $videoTitles) : 'None',
                        'video_id' => !empty($videoIds) ? implode(', ', $videoIds) : null,
                        'clicks' => 0,
                        'leads' => 0,
                        'sales' => 0,
                        'revenue' => 0,
                        'conversion_rate' => 0,
                    ];
                    continue;
                }
                
                try {
                    // Get analytics for this specific link using the general analytics method
                    // filtered by the link's dub_id
                    $linkAnalytics = $this->analyticsService->getAnalytics(
                        $tenant->id,
                        [
                            'interval' => $this->selectedInterval,
                            'linkId' => $link->dub_id, // Filter by specific link
                        ]
                    );
                    
                    // Process the analytics for this link
                    $linkMetrics = $this->analyticsService->processAnalyticsData($linkAnalytics);
                } catch (\Exception $e) {
                    // If individual link analytics fail, use zero metrics
                    \Log::warning('Failed to get analytics for individual link', [
                        'link_id' => $link->id,
                        'dub_id' => $link->dub_id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $linkMetrics = [
                        'total_clicks' => 0,
                        'total_leads' => 0,
                        'total_sales' => 0,
                        'total_sale_amount' => 0,
                        'sales_conversion_rate' => 0,
                    ];
                }
                
                // Build breakdown per link with video association (many-to-many)
                $associatedVideos = $link->ytVideos; // many-to-many
                $videoTitles = $associatedVideos->pluck('title')->toArray();
                $videoIds = $associatedVideos->pluck('id')->toArray();
                
                $linkBreakdown[] = [
                    'link_id' => $link->id,
                    'link_title' => $link->title,
                    'dub_id' => $link->dub_id,
                    'video_title' => !empty($videoTitles) ? implode(', ', $videoTitles) : 'None',
                    'video_id' => !empty($videoIds) ? implode(', ', $videoIds) : null,
                    'clicks' => $linkMetrics['total_clicks'],
                    'leads' => $linkMetrics['total_leads'],
                    'sales' => $linkMetrics['total_sales'],
                    'revenue' => $linkMetrics['total_sale_amount'],
                    'conversion_rate' => $linkMetrics['sales_conversion_rate'],
                ];
            }
            
            // Sort link breakdown by clicks (descending)
            usort($linkBreakdown, function ($a, $b) {
                return $b['clicks'] <=> $a['clicks'];
            });
            
            $this->linkBreakdown = $linkBreakdown;
            
        } catch (\Exception $e) {
            \Log::error('Error loading URL analytics data', [
                'error' => $e->getMessage(),
                'destination_url' => $this->selectedDestinationUrl,
            ]);
            
            Notification::make()
                ->title('Error loading analytics data')
                ->body('Unable to load analytics data: ' . $e->getMessage())
                ->danger()
                ->send();
                
            $this->analyticsData = [];
            $this->processedMetrics = [
                'total_clicks' => 0,
                'total_leads' => 0,
                'total_sales' => 0,
                'total_sale_amount' => 0,
                'lead_conversion_rate' => 0,
                'sales_conversion_rate' => 0,
                'revenue_per_click' => 0,
            ];
            $this->linkBreakdown = [];
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('select_url')
                ->label('Select Destination URL & Time Period')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('primary')
                ->modal()
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Apply Filters')
                ->form([
                    Select::make('destination_url')
                        ->label('Select Destination URL')
                        ->placeholder('Choose a destination URL to analyze...')
                        ->options($this->getDestinationUrlOptions())
                        ->searchable()
                        ->required()
                        ->helperText('Select the destination URL to see which links and videos are driving traffic to it'),
                        
                    Select::make('interval')
                        ->label('Time Period')
                        ->options(AnalyticsInterval::options())
                        ->default(AnalyticsInterval::default()->value)
                        ->required(),
                        
                    \Filament\Forms\Components\Section::make('UTM Parameter Filters')
                        ->description('Filter analytics by UTM parameters (optional)')
                        ->schema([
                            Select::make('utm_source')
                                ->label('UTM Source')
                                ->placeholder('All sources...')
                                ->options(fn () => $this->getUtmOptions('utm_source'))
                                ->searchable()
                                ->nullable(),
                                
                            Select::make('utm_medium')
                                ->label('UTM Medium')
                                ->placeholder('All mediums...')
                                ->options(fn () => $this->getUtmOptions('utm_medium'))
                                ->searchable()
                                ->nullable(),
                                
                            Select::make('utm_campaign')
                                ->label('UTM Campaign')
                                ->placeholder('All campaigns...')
                                ->options(fn () => $this->getUtmOptions('utm_campaign'))
                                ->searchable()
                                ->nullable(),
                                
                            Select::make('utm_term')
                                ->label('UTM Term')
                                ->placeholder('All terms...')
                                ->options(fn () => $this->getUtmOptions('utm_term'))
                                ->searchable()
                                ->nullable(),
                                
                            Select::make('utm_content')
                                ->label('UTM Content')
                                ->placeholder('All content...')
                                ->options(fn () => $this->getUtmOptions('utm_content'))
                                ->searchable()
                                ->nullable(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed(true),
                ])
                ->fillForm([
                    'destination_url' => $this->selectedDestinationUrl,
                    'interval' => $this->selectedInterval,
                    'utm_source' => $this->utmSource,
                    'utm_medium' => $this->utmMedium,
                    'utm_campaign' => $this->utmCampaign,
                    'utm_term' => $this->utmTerm,
                    'utm_content' => $this->utmContent,
                ])
                ->action(function (array $data): void {
                    $this->selectedDestinationUrl = $data['destination_url'];
                    $this->selectedInterval = $data['interval'];
                    $this->utmSource = $data['utm_source'];
                    $this->utmMedium = $data['utm_medium'];
                    $this->utmCampaign = $data['utm_campaign'];
                    $this->utmTerm = $data['utm_term'];
                    $this->utmContent = $data['utm_content'];
                    
                    // Save filter state to session
                    $this->saveFilterState();
                    
                    $this->loadAnalyticsData();
                    
                    Notification::make()
                        ->title('Filters applied')
                        ->body('Analytics data loaded with selected filters.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshData')
                ->visible(fn () => $this->selectedDestinationUrl !== null),
                
            Actions\Action::make('reset_filters')
                ->label('Reset Filters')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action('resetFilters')
                ->visible(fn () => $this->selectedDestinationUrl !== null || $this->hasUtmFilters()),
        ];
    }
    
    protected function getViewActions(): array
    {
        return [
            // Tab navigation between analytics views
            Actions\Action::make('dashboard_tab')
                ->label('Overview')
                ->icon('heroicon-o-chart-bar-square')
                ->color('gray')
                ->url(AnalyticsResource::getUrl('index')),
                
            Actions\Action::make('video_performance_tab')
                ->label('Video Performance')
                ->icon('heroicon-o-play')
                ->color('gray')
                ->url(AnalyticsResource::getUrl('video-performance')),
                
            Actions\Action::make('url_performance_tab')
                ->label('URL Performance')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->badge('Current')
                ->disabled(),
        ];
    }
    
    public function refreshData(): void
    {
        if ($this->selectedDestinationUrl) {
            try {
                $tenant = Filament::getTenant();
                
                if (!$tenant) {
                    throw new \Exception('No tenant context available');
                }
                
                // Invalidate cache for URL analytics
                $this->analyticsService->invalidateCache($tenant->id, 'analytics');
                
                // Reload data without cache
                $this->loadAnalyticsData();
                
                Notification::make()
                    ->title('Data refreshed successfully')
                    ->body('Analytics data has been updated.')
                    ->success()
                    ->send();
                    
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error refreshing data')
                    ->body('Unable to refresh analytics data: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
    
    protected function getUtmOptions(string $field): array
    {
        try {
            $tenant = Filament::getTenant();
            
            if (!$tenant) {
                return [];
            }
            
            $values = Link::where('tenant_id', $tenant->id)
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->distinct()
                ->orderBy($field)
                ->pluck($field)
                ->toArray();
            
            return array_combine($values, $values);
            
        } catch (\Exception $e) {
            \Log::error('Error getting UTM options', [
                'field' => $field,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    protected function saveFilterState(): void
    {
        session()->put('url_analytics_filters', [
            'destination_url' => $this->selectedDestinationUrl,
            'interval' => $this->selectedInterval,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'utm_term' => $this->utmTerm,
            'utm_content' => $this->utmContent,
        ]);
    }
    
    protected function restoreFilterState(): void
    {
        $filters = session()->get('url_analytics_filters', []);
        
        if (!empty($filters)) {
            $this->selectedDestinationUrl = $filters['destination_url'] ?? null;
            $this->selectedInterval = $filters['interval'] ?? AnalyticsInterval::default()->value;
            $this->utmSource = $filters['utm_source'] ?? null;
            $this->utmMedium = $filters['utm_medium'] ?? null;
            $this->utmCampaign = $filters['utm_campaign'] ?? null;
            $this->utmTerm = $filters['utm_term'] ?? null;
            $this->utmContent = $filters['utm_content'] ?? null;
        }
    }
    
    protected function clearFilterState(): void
    {
        session()->forget('url_analytics_filters');
    }
    
    public function resetFilters(): void
    {
        $this->selectedDestinationUrl = null;
        $this->selectedInterval = AnalyticsInterval::default()->value;
        $this->utmSource = null;
        $this->utmMedium = null;
        $this->utmCampaign = null;
        $this->utmTerm = null;
        $this->utmContent = null;
        
        $this->analyticsData = null;
        $this->processedMetrics = null;
        $this->linkBreakdown = null;
        
        $this->clearFilterState();
        
        Notification::make()
            ->title('Filters reset')
            ->body('All filters have been reset to default values.')
            ->success()
            ->send();
    }
    
    protected function hasUtmFilters(): bool
    {
        return !empty($this->utmSource) || 
               !empty($this->utmMedium) || 
               !empty($this->utmCampaign) || 
               !empty($this->utmTerm) || 
               !empty($this->utmContent);
    }
} 