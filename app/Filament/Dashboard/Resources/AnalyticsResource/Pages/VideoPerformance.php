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
            
            // Get analytics data for the selected video
            $this->analyticsData = $this->analyticsService->getVideoAnalytics(
                $tenant->id,
                $this->selectedVideoId,
                ['interval' => $this->selectedInterval]
            );
            
            // Process the analytics data to get aggregated metrics
            $this->processedMetrics = $this->analyticsService->processAnalyticsData($this->analyticsData);
            
        } catch (\Exception $e) {
            \Log::error('Error loading analytics data', [
                'error' => $e->getMessage(),
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
                ->modalSubmitActionLabel('Apply Selection')
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
                ])
                ->fillForm([
                    'video_id' => $this->selectedVideoId,
                    'interval' => $this->selectedInterval,
                ])
                ->action(function (array $data): void {
                    $this->selectedVideoId = $data['video_id'];
                    $this->selectedInterval = $data['interval'];
                    $this->loadAnalyticsData();
                    
                    Notification::make()
                        ->title('Selection updated')
                        ->body('Analytics data loaded for selected video and time period.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshData')
                ->visible(fn () => $this->selectedVideoId !== null),
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