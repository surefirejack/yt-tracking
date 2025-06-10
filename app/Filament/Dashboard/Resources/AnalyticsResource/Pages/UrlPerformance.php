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
    
    protected DubAnalyticsService $analyticsService;

    public function boot(): void
    {
        $this->analyticsService = new DubAnalyticsService();
    }
    
    public function mount(): void
    {
        $this->selectedInterval = AnalyticsInterval::default()->value;
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
                ->get(['id', 'dub_id', 'title', 'yt_video_id']);
            
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
                    $associatedVideo = null;
                    if ($link->yt_video_id) {
                        $associatedVideo = YtVideo::find($link->yt_video_id);
                    }
                    
                    $linkBreakdown[] = [
                        'link_id' => $link->id,
                        'link_title' => $link->title,
                        'dub_id' => $link->dub_id,
                        'video_title' => $associatedVideo ? $associatedVideo->title : 'None',
                        'video_id' => $associatedVideo ? $associatedVideo->id : null,
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
                
                // Build breakdown per link with video association
                $associatedVideo = null;
                if ($link->yt_video_id) {
                    $associatedVideo = YtVideo::find($link->yt_video_id);
                }
                
                $linkBreakdown[] = [
                    'link_id' => $link->id,
                    'link_title' => $link->title,
                    'dub_id' => $link->dub_id,
                    'video_title' => $associatedVideo ? $associatedVideo->title : 'None',
                    'video_id' => $associatedVideo ? $associatedVideo->id : null,
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
                ->modalSubmitActionLabel('Apply Selection')
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
                ])
                ->fillForm([
                    'destination_url' => $this->selectedDestinationUrl,
                    'interval' => $this->selectedInterval,
                ])
                ->action(function (array $data): void {
                    $this->selectedDestinationUrl = $data['destination_url'];
                    $this->selectedInterval = $data['interval'];
                    $this->loadAnalyticsData();
                    
                    Notification::make()
                        ->title('Selection updated')
                        ->body('Analytics data loaded for selected destination URL and time period.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshData')
                ->visible(fn () => $this->selectedDestinationUrl !== null),
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
} 