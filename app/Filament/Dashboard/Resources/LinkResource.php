<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\LinkResource\Pages;
use App\Models\Link;
use App\Models\Tag;
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
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Colors\Color;
use Filament\Facades\Filament;

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
                Tabs::make('Link Management')
                    ->tabs([
                        Tabs\Tab::make('Main')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        // Left column (1/2 width)
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('short_link')
                                                    ->label('Short Link')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Will be generated')
                                                    ->visible(fn ($record) => $record !== null),

                                                TextInput::make('original_url')
                                                    ->label('Destination URL')
                                                    ->url()
                                                    ->required()
                                                    ->maxLength(2048)
                                                    ->placeholder('https://example.com')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        // Sync with split testing main URL if split testing is enabled
                                                        $splitTestingEnabled = $get('split_testing_enabled');
                                                        if ($splitTestingEnabled) {
                                                            $set('main_url', $state);
                                                            
                                                            // Update test_variants array
                                                            $variants = $get('test_variants') ?? [];
                                                            if (isset($variants[0])) {
                                                                $variants[0]['url'] = $state;
                                                                $set('test_variants', $variants);
                                                            }
                                                        }
                                                    }),
                                                
                                                Textarea::make('description')
                                                    ->label('Description')
                                                    ->maxLength(500)
                                                    ->rows(4)
                                                    ->placeholder('Optional description'),
                                            ])
                                            ->columnSpan(2),
                                        
                                        // Right column (1/2 width)
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Title')
                                                    ->maxLength(255)
                                                    ->placeholder('Optional title for the link'),
                                                
                                                TagsInput::make('tags')
                                                    ->label('Tags')
                                                    ->placeholder('Add tags to organize your links')
                                                    ->helperText('Press Enter to add tags')
                                                    ->suggestions(function () {
                                                        return Tag::forTenant(Filament::getTenant()->id)
                                                            ->pluck('name')
                                                            ->toArray();
                                                    }),
                                                
                                                TextInput::make('folder_id')
                                                    ->label('Folder')
                                                    ->maxLength(255)
                                                    ->placeholder('Folder ID or name')
                                                    ->helperText('Organize links into folders'),
                                            ])
                                            ->columnSpan(2),
                                    ]),
                            ]),

                        Tabs\Tab::make('UTMs')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('utm_source')
                                            ->label('UTM Source')
                                            ->maxLength(255)
                                            ->placeholder('e.g., google, newsletter'),
                                        
                                        TextInput::make('utm_medium')
                                            ->label('UTM Medium')
                                            ->maxLength(255)
                                            ->placeholder('e.g., email, social, cpc'),
                                        
                                        TextInput::make('utm_campaign')
                                            ->label('UTM Campaign')
                                            ->maxLength(255)
                                            ->placeholder('e.g., spring_sale'),
                                        
                                        TextInput::make('utm_term')
                                            ->label('UTM Term')
                                            ->maxLength(255)
                                            ->placeholder('e.g., running shoes'),
                                        
                                        TextInput::make('utm_content')
                                            ->label('UTM Content')
                                            ->maxLength(255)
                                            ->placeholder('e.g., logolink, textlink')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Split Testing')
                            ->icon('heroicon-o-beaker')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        Toggle::make('split_testing_enabled')
                                            ->label('Enable Split Testing')
                                            ->helperText('Enable A/B testing between two URLs')
                                            ->live()
                                            ->dehydrated(false)
                                            ->default(function ($record) {
                                                if (!$record || !$record->test_variants) {
                                                    return false;
                                                }
                                                
                                                // Check if test_variants is an array with actual data
                                                $variants = is_array($record->test_variants) ? $record->test_variants : [];
                                                return !empty($variants) && count($variants) > 0;
                                            })
                                            ->afterStateHydrated(function ($component, $state, $record) {
                                                if ($record && $record->test_variants) {
                                                    $variants = is_array($record->test_variants) ? $record->test_variants : [];
                                                    $hasVariants = !empty($variants) && count($variants) > 0;
                                                    $component->state($hasVariants);
                                                }
                                            })
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                if (!$state) {
                                                    // When toggle is turned off, clear test_variants
                                                    $set('test_variants', null);
                                                    $set('main_url', '');
                                                    $set('test_url', '');
                                                } else {
                                                    // When toggle is turned on, initialize or maintain test_variants
                                                    $existingVariants = $get('test_variants') ?? [];
                                                    $originalUrl = $get('original_url') ?? '';
                                                    
                                                    if (empty($existingVariants)) {
                                                        $set('test_variants', [
                                                            [
                                                                'url' => $originalUrl,
                                                                'percentage' => 50
                                                            ],
                                                            [
                                                                'url' => '',
                                                                'percentage' => 50
                                                            ]
                                                        ]);
                                                        $set('main_url', $originalUrl);
                                                        $set('test_url', '');
                                                    } else {
                                                        // Populate form fields from existing data, sync main_url with original_url
                                                        $set('main_url', $originalUrl);
                                                        $set('test_url', $existingVariants[1]['url'] ?? '');
                                                        $set('traffic_percentage', $existingVariants[0]['percentage'] ?? 50);
                                                        
                                                        // Update the variants array to reflect the synced original_url
                                                        $existingVariants[0]['url'] = $originalUrl;
                                                        $set('test_variants', $existingVariants);
                                                    }
                                                }
                                            }),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('main_url')
                                                    ->label('Main URL (Variant A)')
                                                    ->maxLength(2048)
                                                    ->placeholder('Synced with Destination URL')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->default(function ($record, $get) {
                                                        // Always sync with original_url
                                                        $originalUrl = $get('original_url') ?? '';
                                                        return $originalUrl ?: ($record && $record->test_variants ? ($record->test_variants[0]['url'] ?? '') : '');
                                                    })
                                                    ->helperText('This URL is automatically synced with your Destination URL'),

                                                TextInput::make('test_url')
                                                    ->label('Test URL (Variant B)')
                                                    ->url()
                                                    ->maxLength(2048)
                                                    ->placeholder('https://example.com/variant-b')
                                                    ->dehydrated(false)
                                                    ->default(fn ($record) => $record && $record->test_variants ? ($record->test_variants[1]['url'] ?? '') : '')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $variants = $get('test_variants') ?? [];
                                                        if (isset($variants[1])) {
                                                            $variants[1]['url'] = $state;
                                                            $set('test_variants', $variants);
                                                        }
                                                    }),
                                            ])
                                            ->visible(fn ($get) => $get('split_testing_enabled')),

                                        Forms\Components\ViewField::make('traffic_split')
                                            ->label('Traffic Split')
                                            ->view('filament.forms.components.traffic-split-slider')
                                            ->viewData(fn ($get, $record) => [
                                                'percentage' => $record && $record->test_variants ? ($record->test_variants[0]['percentage'] ?? 50) : ($get('test_variants.0.percentage') ?? 50),
                                                'field_name' => 'traffic_percentage'
                                            ])
                                            ->visible(fn ($get) => $get('split_testing_enabled')),

                                        Forms\Components\Hidden::make('traffic_percentage')
                                            ->default(fn ($record) => $record && $record->test_variants ? ($record->test_variants[0]['percentage'] ?? 50) : 50)
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $percentage = (int) $state;
                                                $variants = $get('test_variants') ?? [];
                                                if (count($variants) >= 2) {
                                                    $variants[0]['percentage'] = $percentage;
                                                    $variants[1]['percentage'] = 100 - $percentage;
                                                    $set('test_variants', $variants);
                                                }
                                            }),

                                        Forms\Components\Placeholder::make('split_preview')
                                            ->label('Traffic Distribution')
                                            ->content(function ($get, $record) {
                                                $trafficPercentage = $get('traffic_percentage') ?? 50;
                                                $variantA = (int) $trafficPercentage;
                                                $variantB = 100 - $variantA;
                                                return "Variant A: {$variantA}% • Variant B: {$variantB}%";
                                            })
                                            ->live()
                                            ->visible(fn ($get) => $get('split_testing_enabled')),

                                        Forms\Components\Hidden::make('test_variants')
                                            ->afterStateHydrated(function ($component, $state, $get, $set) {
                                                // Sync main URL with original_url on load
                                                if ($state && is_array($state) && isset($state[0])) {
                                                    $originalUrl = $get('original_url') ?? '';
                                                    if ($originalUrl && $originalUrl !== $state[0]['url']) {
                                                        $state[0]['url'] = $originalUrl;
                                                        $component->state($state);
                                                    }
                                                    $set('main_url', $originalUrl);
                                                }
                                            }),
                                    ]),
                            ]),

                        Tabs\Tab::make('Targeting')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('ios')
                                            ->label('iOS Redirect URL')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('URL for iOS users'),
                                        
                                        TextInput::make('android')
                                            ->label('Android Redirect URL')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('URL for Android users'),
                                        
                                        Textarea::make('geo')
                                            ->label('Geographic Targeting')
                                            ->placeholder('JSON object for geo targeting')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->helperText('Advanced geographic targeting settings (JSON format)'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Password')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('password')
                                            ->label('Password Protection')
                                            ->password()
                                            ->maxLength(255)
                                            ->placeholder('Set a password to protect this link')
                                            ->helperText('Users will need to enter this password to access the link'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Expiration')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        DateTimePicker::make('expires_at')
                                            ->label('Expiration Date')
                                            ->placeholder('Select expiration date and time')
                                            ->helperText('Link will expire on this date'),
                                        
                                        TextInput::make('expired_url')
                                            ->label('Expired URL')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('Where to redirect after expiration')
                                            ->helperText('URL to redirect users when link has expired'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Link Preview')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('image')
                                            ->label('Preview Image URL')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('Image for social media previews'),
                                        
                                        TextInput::make('video')
                                            ->label('Preview Video URL')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('Video for social media previews'),
                                    ]),
                            ]),

                        Tabs\Tab::make('QR Code')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        Section::make('QR Code Preview')
                                            ->schema([
                                                Forms\Components\ViewField::make('qr_preview')
                                                    ->view('filament.forms.components.qr-preview')
                                                    ->viewData(fn ($record) => [
                                                        'qr_url' => $record?->qr_code,
                                                        'short_link' => $record?->short_link,
                                                    ])
                                                    ->visible(fn ($record) => $record !== null && $record->qr_code),
                                            ])
                                            ->visible(fn ($record) => $record !== null && $record->qr_code),

                                        Forms\Components\Placeholder::make('qr_info')
                                            ->label('')
                                            ->content('QR code will be available once the link is successfully created through the Dub API.')
                                            ->visible(fn ($record) => $record === null || !$record->qr_code),
                                    ]),
                            ]),

                        Tabs\Tab::make('Advanced')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Textarea::make('webhook_ids')
                                            ->label('Webhook IDs')
                                            ->placeholder('JSON array of webhook IDs')
                                            ->rows(4)
                                            ->helperText('Webhook IDs to trigger on link events (JSON format)')
                                            ->columnSpanFull(),
                                        
                                        Toggle::make('track_conversion')
                                            ->label('Track Conversions')
                                            ->helperText('Enable conversion tracking for this link')
                                            ->default(true),
                                        
                                        Toggle::make('public_stats')
                                            ->label('Public Statistics')
                                            ->helperText('Make link statistics publicly viewable'),

                                        Toggle::make('rewrite')
                                            ->label('Rewrite URL')
                                            ->helperText('Rewrite the destination URL'),
                                        
                                        Toggle::make('do_index')
                                            ->label('Allow Indexing')
                                            ->helperText('Allow search engines to index this link'),
                                        
                                        Toggle::make('proxy')
                                            ->label('Proxy Mode')
                                            ->helperText('Enable proxy mode for this link'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
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
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('clicks')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('leads')
                    ->label('Leads')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

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
            ->where('tenant_id', Filament::getTenant()->id);
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
