<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\YtVideoResource\Pages;
use App\Filament\Dashboard\Resources\YtVideoResource\RelationManagers;
use App\Models\YtVideo;
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
                    ])
                    ->action(function (array $data) {
                        // Here you would process the channel URL/handle
                        // For now, we'll just show a notification
                        Notification::make()
                            ->title('Channel Added')
                            ->body('Your YouTube channel has been added successfully!')
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
