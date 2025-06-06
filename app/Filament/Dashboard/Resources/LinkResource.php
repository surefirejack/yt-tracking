<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\LinkResource\Pages;
use App\Models\Link;
use App\Jobs\CreateLinkJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Colors\Color;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Links';

    protected static ?string $modelLabel = 'Link';

    protected static ?string $pluralModelLabel = 'Links';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Link Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('original_url')
                                    ->label('Original URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(2048)
                                    ->placeholder('https://example.com')
                                    ->columnSpanFull(),
                                
                                TextInput::make('title')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->placeholder('Optional title for the link'),
                                
                                TextInput::make('description')
                                    ->label('Description')
                                    ->maxLength(500)
                                    ->placeholder('Optional description'),
                            ]),
                    ]),

                Section::make('UTM Parameters')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('utm_source')
                                    ->label('UTM Source')
                                    ->maxLength(255),
                                
                                TextInput::make('utm_medium')
                                    ->label('UTM Medium')
                                    ->maxLength(255),
                                
                                TextInput::make('utm_campaign')
                                    ->label('UTM Campaign')
                                    ->maxLength(255),
                                
                                TextInput::make('utm_term')
                                    ->label('UTM Term')
                                    ->maxLength(255),
                                
                                TextInput::make('utm_content')
                                    ->label('UTM Content')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Link Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('short_link')
                                    ->label('Short Link')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                TextInput::make('status')
                                    ->label('Status')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                TextInput::make('clicks')
                                    ->label('Clicks')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric(),
                                
                                TextInput::make('leads')
                                    ->label('Leads')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric(),
                            ]),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_url')
                    ->label('Original URL')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->copyable()
                    ->copyMessage('URL copied!')
                    ->searchable(),

                TextColumn::make('short_link')
                    ->label('Short Link')
                    ->limit(30)
                    ->copyable()
                    ->copyMessage('Short link copied!')
                    ->url(fn ($record) => $record->short_link)
                    ->openUrlInNewTab()
                    ->placeholder('Processing...')
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->limit(30)
                    ->placeholder('No title')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'completed' => 'heroicon-m-check-circle',
                        'processing' => 'heroicon-m-arrow-path',
                        'pending' => 'heroicon-m-clock',
                        'failed' => 'heroicon-m-x-circle',
                    }),

                TextColumn::make('clicks')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('leads')
                    ->label('Leads')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_clicked')
                    ->label('Last Clicked')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->current_tenant_id);
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
            'index' => Pages\ListLinks::route('/'),
            'create' => Pages\CreateLink::route('/create'),
            'edit' => Pages\EditLink::route('/{record}/edit'),
        ];
    }
}
