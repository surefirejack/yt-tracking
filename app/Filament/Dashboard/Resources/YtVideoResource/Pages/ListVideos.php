<?php

namespace App\Filament\Dashboard\Resources\YtVideoResource\Pages;

use App\Filament\Dashboard\Resources\YtVideoResource;
use App\Models\YtChannel;
use App\Events\YouTubeChannelAdded;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables;

class ListVideos extends ListRecords
{
    protected static string $resource = YtVideoResource::class;

    public function getTitle(): string
    {
        $tenant = Filament::getTenant();
        
        // Check if we have a channel name using the YtChannel relationship
        if ($tenant->ytChannel && !empty($tenant->ytChannel->name)) {
            return "Videos on {$tenant->ytChannel->name}";
        }
        
        return "Your Channel's Videos";
    }


    public function table(Table $table): Table
    {
        $table = parent::table($table);
        

        if ($this->shouldShowSkeleton()) {
            return $table
                ->emptyStateHeading('Loading your videos...')
                ->emptyStateDescription('We\'re fetching your latest videos from YouTube. This may take a few moments.')
                ->emptyStateIcon('heroicon-o-arrow-path')
                ->emptyState(view('components.video-table-skeleton'))
                ->emptyStateActions([]);
        }
        
        return $table
            ->emptyStateHeading('No videos found')
            ->emptyStateDescription('Add your YouTube channel to start importing your videos')
            ->emptyStateIcon('heroicon-o-video-camera')
            ->emptyStateActions([
                Tables\Actions\Action::make('addChannel')
                    ->label('Add Your YouTube Channel')
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->color('primary')
                    ->modalSubmitActionLabel('Save')
                    ->form([
                        Forms\Components\TextInput::make('channel')
                            ->label('YouTube Channel URL or Handle')
                            ->placeholder('https://youtube.com/@channel or @channelhandle')
                            ->required()
                            ->helperText('Enter either your channel URL or your channel handle (e.g., @yourchannelname)')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $isYouTubeUrl = str_starts_with($value, 'https://youtube.com/') || 
                                                       str_starts_with($value, 'https://www.youtube.com/') ||
                                                       str_starts_with($value, 'http://youtube.com/') || 
                                                       str_starts_with($value, 'http://www.youtube.com/');
                                        
                                        $isHandle = str_starts_with($value, '@') && !str_contains($value, ' ');
                                        
                                        if (!$isYouTubeUrl && !$isHandle) {
                                            $fail('Please enter either a valid YouTube URL or a channel handle starting with @');
                                        }
                                    };
                                }
                            ])
                    ])
                    ->action(function (array $data) {
                        $tenant = Filament::getTenant();
                        $channelInput = $data['channel'];
                        
                        // Determine if it's a URL or handle
                        $isUrl = str_starts_with($channelInput, 'http');
                        
                        // Create the channel record
                        $ytChannel = YtChannel::create([
                            'tenant_id' => $tenant->id,
                            'name' => $isUrl ? 'YouTube Channel' : ltrim($channelInput, '@'), // Temporary name, will be updated by API
                            'type' => 'youtube',
                            'handle' => $isUrl ? null : $channelInput,
                            'url' => $isUrl ? $channelInput : null,
                            'channel_id' => 'pending', // Will be updated by API call
                        ]);
                        
                        // Fire the event
                        YouTubeChannelAdded::dispatch($ytChannel);
                        
                        Notification::make()
                            ->title('Channel Added Successfully')
                            ->body('Your YouTube channel has been added and is being processed!')
                            ->success()
                            ->send();
                    })
            ]);
    }

    protected function getHeaderActions(): array
    {
        $tenant = Filament::getTenant();
        $hasChannel = $tenant && $tenant->ytChannel;
        
        return [
            Actions\Action::make('addChannel')
                ->label('Add YouTube Channel')
                ->icon('heroicon-o-plus')
                ->visible(!$hasChannel)
                ->modalSubmitActionLabel('Save')
                ->form([
                    Forms\Components\TextInput::make('channel')
                        ->label('YouTube Channel URL or Handle')
                        ->placeholder('https://youtube.com/@channel or @channelhandle')
                        ->required()
                        ->helperText('Enter either your channel URL or your channel handle (e.g., @yourchannelname)')
                        ->rules([
                            function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $isYouTubeUrl = str_starts_with($value, 'https://youtube.com/') || 
                                                   str_starts_with($value, 'https://www.youtube.com/') ||
                                                   str_starts_with($value, 'http://youtube.com/') || 
                                                   str_starts_with($value, 'http://www.youtube.com/');
                                    
                                    $isHandle = str_starts_with($value, '@') && !str_contains($value, ' ');
                                    
                                    if (!$isYouTubeUrl && !$isHandle) {
                                        $fail('Please enter either a valid YouTube URL or a channel handle starting with @');
                                    }
                                };
                            }
                        ])
                ])
                ->action(function (array $data) {
                    $tenant = Filament::getTenant();
                    $channelInput = $data['channel'];
                    
                    // Determine if it's a URL or handle
                    $isUrl = str_starts_with($channelInput, 'http');
                    
                    // Create the channel record
                    $ytChannel = YtChannel::create([
                        'tenant_id' => $tenant->id,
                        'name' => $isUrl ? 'YouTube Channel' : ltrim($channelInput, '@'), // Temporary name, will be updated by API
                        'type' => 'youtube',
                        'handle' => $isUrl ? null : $channelInput,
                        'url' => $isUrl ? $channelInput : null,
                        'channel_id' => 'pending', // Will be updated by API call
                    ]);
                    
                    // Fire the event
                    YouTubeChannelAdded::dispatch($ytChannel);
                    
                    Notification::make()
                        ->title('Channel Added Successfully')
                        ->body('Your YouTube channel has been added and is being processed!')
                        ->success()
                        ->send();
                })
        ];
    }

    private function shouldShowSkeleton(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant || !$tenant->ytChannel) {
            return false;
        }
        
        // Check if we have any videos for this channel
        $hasVideos = $tenant->ytChannel->ytVideos()->count() > 0;
        
        return !$hasVideos;
    }
}
