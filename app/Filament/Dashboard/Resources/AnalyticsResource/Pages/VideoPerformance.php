<?php

namespace App\Filament\Dashboard\Resources\AnalyticsResource\Pages;

use App\Filament\Dashboard\Resources\AnalyticsResource;
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

class VideoPerformance extends Page
{
    protected static string $resource = AnalyticsResource::class;

    protected static string $view = 'filament.analytics.video-performance';
    
    protected static ?string $title = 'Video Performance Analytics';
    
    protected static ?string $navigationLabel = 'Video Performance';
    
    // Public properties for the selected values
    public ?int $selectedVideoId = null;
    public ?string $selectedInterval = null;
    public ?array $analyticsData = null;
    public ?array $processedMetrics = null;
    
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
        
        // If we have a selected video from the restored state, load analytics data automatically
        if ($this->selectedVideoId) {
            $this->loadAnalyticsData();
        }
    }
    
    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    protected function getVideoOptions(): array
    {
        try {
            $tenant = Filament::getTenant();
            
            if (!$tenant) {
                return [];
            }
            
            $videos = YtVideo::forTenant($tenant->id)
                ->orderBy('published_at', 'desc')
                ->get(['id', 'title', 'published_at']);
            
            if ($videos->isEmpty()) {
                return [];
            }
            
            return $videos->pluck('title', 'id')->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Error getting video options', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    protected function getUtmOptions(string $field): array
    {
        try {
            $tenant = Filament::getTenant();
            
            if (!$tenant) {
                return [];
            }
            
            $values = \App\Models\Link::where('tenant_id', $tenant->id)
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
        session()->put('video_analytics_filters', [
            'video_id' => $this->selectedVideoId,
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
        $filters = session()->get('video_analytics_filters', []);
        
        if (!empty($filters)) {
            $this->selectedVideoId = $filters['video_id'] ?? null;
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
        session()->forget('video_analytics_filters');
    }
    
    public function resetFilters(): void
    {
        $this->selectedVideoId = null;
        $this->selectedInterval = AnalyticsInterval::default()->value;
        $this->utmSource = null;
        $this->utmMedium = null;
        $this->utmCampaign = null;
        $this->utmTerm = null;
        $this->utmContent = null;
        
        $this->analyticsData = null;
        $this->processedMetrics = null;
        
        $this->clearFilterState();
        
        Notification::make()
            ->title('Filters reset')
            ->body('All filters have been reset to default values.')
            ->success()
            ->send();
    }
    
    public function loadAnalyticsData(): void
    {
        if (!$this->selectedVideoId) {
            $this->analyticsData = null;
            $this->processedMetrics = null;
            return;
        }
        
        try {
            $tenant = Filament::getTenant();
            
            if (!$tenant) {
                throw new \Exception('No tenant context available');
            }
            
            // Build UTM filters
            $utmFilters = [];
            if (!empty($this->utmSource)) $utmFilters['utm_source'] = $this->utmSource;
            if (!empty($this->utmMedium)) $utmFilters['utm_medium'] = $this->utmMedium;
            if (!empty($this->utmCampaign)) $utmFilters['utm_campaign'] = $this->utmCampaign;
            if (!empty($this->utmTerm)) $utmFilters['utm_term'] = $this->utmTerm;
            if (!empty($this->utmContent)) $utmFilters['utm_content'] = $this->utmContent;
            
            // Get analytics data for the selected video with UTM filters
            if (!empty($utmFilters)) {
                $this->analyticsData = $this->analyticsService->getAnalyticsWithUtmFilters(
                    $tenant->id,
                    $utmFilters,
                    [
                        'interval' => $this->selectedInterval,
                        'tagName' => 'yt-video-' . $this->selectedVideoId
                    ]
                );
            } else {
                $this->analyticsData = $this->analyticsService->getVideoAnalytics(
                    $tenant->id,
                    $this->selectedVideoId,
                    ['interval' => $this->selectedInterval]
                );
            }
            
            // Process the analytics data to get aggregated metrics
            $this->processedMetrics = $this->analyticsService->processAnalyticsData($this->analyticsData);
            
        } catch (\Exception $e) {
            \Log::error('Error loading analytics data', [
                'error' => $e->getMessage(),
                'video_id' => $this->selectedVideoId,
                'utm_filters' => $utmFilters ?? [],
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
        }
    }
    
    public function getSelectedVideo(): ?YtVideo
    {
        if (!$this->selectedVideoId) {
            return null;
        }
        
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return null;
        }
        
        return YtVideo::forTenant($tenant->id)
            ->where('id', $this->selectedVideoId)
            ->first();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('select_video')
                ->label('Select Video & Time Period')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('primary')
                ->modal()
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Apply Filters')
                ->form([
                    Select::make('video_id')
                        ->label('Select YouTube Video')
                        ->placeholder('Choose a video to analyze...')
                        ->options($this->getVideoOptions())
                        ->searchable()
                        ->required(),
                        
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
                    'video_id' => $this->selectedVideoId,
                    'interval' => $this->selectedInterval,
                    'utm_source' => $this->utmSource,
                    'utm_medium' => $this->utmMedium,
                    'utm_campaign' => $this->utmCampaign,
                    'utm_term' => $this->utmTerm,
                    'utm_content' => $this->utmContent,
                ])
                ->action(function (array $data): void {
                    $this->selectedVideoId = $data['video_id'];
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
                ->visible(fn () => $this->selectedVideoId !== null),
                
            Actions\Action::make('reset_filters')
                ->label('Reset Filters')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action('resetFilters')
                ->visible(fn () => $this->selectedVideoId !== null || $this->hasUtmFilters()),
        ];
    }
    
    protected function hasUtmFilters(): bool
    {
        return !empty($this->utmSource) || 
               !empty($this->utmMedium) || 
               !empty($this->utmCampaign) || 
               !empty($this->utmTerm) || 
               !empty($this->utmContent);
    }
    
    protected function getViewActions(): array
    {
        return [
            // Tab navigation between analytics views
            Actions\Action::make('video_performance_tab')
                ->label('Video Performance')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->badge('Current')
                ->disabled(),
                
            Actions\Action::make('url_performance_tab')
                ->label('URL Performance')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->url(AnalyticsResource::getUrl('url-performance')),
                
            Actions\Action::make('dashboard_tab')
                ->label('Overview')
                ->icon('heroicon-o-chart-bar-square')
                ->color('gray')
                ->url(AnalyticsResource::getUrl('index')),
        ];
    }
    
    public function refreshData(): void
    {
        if ($this->selectedVideoId) {
            try {
                $tenant = Filament::getTenant();
                
                if (!$tenant) {
                    throw new \Exception('No tenant context available');
                }
                
                // Invalidate cache for this video
                $this->analyticsService->invalidateCache($tenant->id, 'analytics');
                
                // Reload data without cache
                $this->analyticsData = $this->analyticsService->getVideoAnalytics(
                    $tenant->id,
                    $this->selectedVideoId,
                    ['interval' => $this->selectedInterval],
                    false // Skip cache
                );
                
                $this->processedMetrics = $this->analyticsService->processAnalyticsData($this->analyticsData);
                
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
} 