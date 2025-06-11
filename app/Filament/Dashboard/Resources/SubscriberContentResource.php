<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\SubscriberContentResource\Pages;
use App\Models\SubscriberContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SubscriberContentResource extends Resource
{
    protected static ?string $model = SubscriberContent::class;

    protected static ?string $tenantRelationshipName = 'subscriberContent';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Subscriber Content';

    protected static ?string $navigationGroup = 'YouTube Subscribers';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->where('tenant_id', $tenant->id)
            ->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Content Information')
                    ->description('Basic information about your subscriber content')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Content Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $context, $state, Forms\Set $set) {
                                if ($context === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->helperText('This will be shown as the page title and used for the URL slug'),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(SubscriberContent::class, 'slug', ignoreRecord: true)
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        // Check if slug conflicts with reserved routes
                                        $reservedSlugs = ['community', 'login', 'logout', 'auth', 'callback'];
                                        if (in_array(strtolower($value), $reservedSlugs)) {
                                            $fail('This slug is reserved and cannot be used.');
                                        }
                                    };
                                },
                            ])
                            ->helperText('URL-friendly version of the title. Will be used in /s/{channelname}/{slug}'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Content Body')
                    ->description('The main content that subscribers will see')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Content')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'heading',
                                'subheading',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                            ])
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('subscriber-content/attachments')
                            ->helperText('Rich text content that will be displayed to your subscribers'),
                    ]),

                Forms\Components\Section::make('YouTube Video')
                    ->description('Optional YouTube video to embed with this content')
                    ->icon('heroicon-o-video-camera')
                    ->schema([
                        Forms\Components\Select::make('youtube_video_url')
                            ->label('YouTube Video')
                            ->options(function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant || !$tenant->ytChannel) {
                                    return [];
                                }

                                return $tenant->ytChannel->ytVideos()
                                    ->latest('published_at')
                                    ->limit(50)
                                    ->get()
                                    ->pluck('title', 'url')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a video from your channel')
                            ->helperText('Choose a video from your YouTube channel to embed with this content')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('File Downloads')
                    ->description('Upload files that subscribers can download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        Forms\Components\FileUpload::make('file_paths')
                            ->label('Downloadable Files')
                            ->multiple()
                            ->directory('subscriber-content')
                            ->disk('local')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/jpg', 
                                'image/png',
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->maxSize(50 * 1024) // 50MB in KB
                            ->maxFiles(10)
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Upload files (PDF, JPG, JPEG, PNG, ZIP) up to 50MB each. Subscribers will be able to download these files.')
                            ->rule([
                                File::types(['pdf', 'jpg', 'jpeg', 'png', 'zip'])->max(50 * 1024), // 50MB
                            ])
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // When files are uploaded, store their original names for display
                                if (is_array($state)) {
                                    $currentFileNames = $get('file_names') ?? [];
                                    $newFileNames = [];
                                    
                                    foreach ($state as $index => $filePath) {
                                        if (isset($currentFileNames[$index])) {
                                            // Keep existing name if it exists
                                            $newFileNames[] = $currentFileNames[$index];
                                        } else {
                                            // Extract original filename from the stored path
                                            $filename = basename($filePath);
                                            // Remove timestamp prefix (YmdHis_) if present
                                            $cleanName = preg_replace('/^\d{14}_/', '', $filename);
                                            $newFileNames[] = $cleanName;
                                        }
                                    }
                                    
                                    $set('file_names', $newFileNames);
                                }
                            })
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => now()->format('YmdHis') . '_' . $file->getClientOriginalName()
                            ),

                        Forms\Components\Hidden::make('file_names')
                            ->dehydrated(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Publishing Options')
                    ->description('Control when and how this content is published')
                    ->icon('heroicon-o-eye')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(true)
                            ->helperText('Only published content will be visible to subscribers'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->default(now())
                            ->helperText('When this content should become available to subscribers'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->size('sm'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('has_video')
                    ->label('Video')
                    ->getStateUsing(fn (SubscriberContent $record): bool => !empty($record->youtube_video_url))
                    ->boolean()
                    ->trueIcon('heroicon-o-video-camera')
                    ->falseIcon('heroicon-o-video-camera-slash')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('file_count')
                    ->label('Files')
                    ->getStateUsing(fn (SubscriberContent $record): int => count($record->file_paths ?? []))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state <= 2 => 'success',
                        $state <= 5 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published Status')
                    ->placeholder('All content')
                    ->trueLabel('Published only')
                    ->falseLabel('Unpublished only'),

                Tables\Filters\Filter::make('has_video')
                    ->label('Has Video')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('youtube_video_url'))
                    ->toggle(),

                Tables\Filters\Filter::make('has_files')
                    ->label('Has Files')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('file_paths'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(function (SubscriberContent $record) {
                        $tenant = Filament::getTenant();
                        $channelname = $tenant->getChannelName() ?? 'channel';
                        return route('subscriber.content', [
                            'channelname' => $channelname,
                            'slug' => $record->slug
                        ]);
                    })
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_published' => true,
                                    'published_at' => now(),
                                ]);
                            });

                            Notification::make()
                                ->title('Content Published')
                                ->body(count($records) . ' content items have been published.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_published' => false]);
                            });

                            Notification::make()
                                ->title('Content Unpublished')
                                ->body(count($records) . ' content items have been unpublished.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No subscriber content yet')
            ->emptyStateDescription('Create your first piece of exclusive content for your YouTube subscribers.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Content')
                    ->icon('heroicon-o-plus'),
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
            'index' => Pages\ListSubscriberContent::route('/'),
            'create' => Pages\CreateSubscriberContent::route('/create'),
            'edit' => Pages\EditSubscriberContent::route('/{record}/edit'),
            'settings' => Pages\SubscriberSettings::route('/settings'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return null;
        }

        return static::getModel()::where('tenant_id', $tenant->id)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationItems(): array
    {
        return [
            // Main Content Management
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn (): bool => 
                    request()->routeIs(static::getRouteBaseName() . '.index', static::getRouteBaseName() . '.create', static::getRouteBaseName() . '.edit')
                )
                ->sort(static::getNavigationSort())
                ->url(static::getUrl('index'))
                ->group(static::getNavigationGroup()),
                
            // Settings Page
            \Filament\Navigation\NavigationItem::make('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.settings'))
                ->sort(10)
                ->url(static::getUrl('settings'))
                ->group(static::getNavigationGroup()),
        ];
    }
} 