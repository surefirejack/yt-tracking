<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\YtVideoResource\Pages;
use App\Filament\Dashboard\Resources\YtVideoResource\RelationManagers;
use App\Models\YtVideo;
use App\Models\YtChannel;
use App\Events\YouTubeChannelAdded;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class YtVideoResource extends Resource
{
    protected static ?string $model = YtVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Your Videos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
