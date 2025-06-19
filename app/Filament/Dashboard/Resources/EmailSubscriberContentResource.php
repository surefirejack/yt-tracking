<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\EmailSubscriberContentResource\Pages;
use App\Models\EmailSubscriberContent;
use App\Services\EmailServiceProvider\EmailServiceProviderManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EmailSubscriberContentResource extends Resource
{
    protected static ?string $model = EmailSubscriberContent::class;

    protected static ?string $tenantRelationshipName = 'emailSubscriberContents';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Email Gated Content';

    protected static ?string $navigationGroup = 'Email Subscribers';

    protected static ?int $navigationSort = 2;

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
                    ->description('Basic information about your email-gated content')
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
                            ->unique(EmailSubscriberContent::class, 'slug', ignoreRecord: true)
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
                            ->helperText('URL-friendly version of the title. Will be used in /p/{channelname}/{slug}'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Email Subscriber Settings')
                    ->description('Configure which subscribers can access this content')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\Select::make('required_tag_id')
                            ->label('Required Tag')
                            ->placeholder('Select a tag from your email service provider')
                            ->options(function () {
                                $tenant = Filament::getTenant();
                                $espManager = app(EmailServiceProviderManager::class);
                                $provider = $espManager->getProviderForTenant($tenant);
                                
                                if (!$provider) {
                                    return [];
                                }
                                
                                try {
                                    $tags = $provider->getTags();
                                    return collect($tags)->pluck('name', 'id')->toArray();
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Only subscribers with this tag will be able to access the content')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('create_tag')
                                    ->label('Create New Tag')
                                    ->icon('heroicon-o-plus')
                                    ->modal()
                                    ->form([
                                        Forms\Components\TextInput::make('new_tag_name')
                                            ->label('Tag Name')
                                            ->required()
                                            ->maxLength(100)
                                            ->helperText('Enter the name for the new tag'),
                                    ])
                                    ->action(function (array $data, Forms\Set $set) {
                                        $tenant = Filament::getTenant();
                                        $espManager = app(EmailServiceProviderManager::class);
                                        $provider = $espManager->getProviderForTenant($tenant);
                                        
                                        if (!$provider) {
                                            Notification::make()
                                                ->title('ESP Not Configured')
                                                ->body('Please configure your email service provider first.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            $result = $provider->createTag($data['new_tag_name']);
                                            
                                            if ($result['success'] ?? false) {
                                                $set('required_tag_id', $result['id']);
                                                
                                                Notification::make()
                                                    ->title('Tag Created')
                                                    ->body("Tag '{$data['new_tag_name']}' has been created and selected.")
                                                    ->success()
                                                    ->send();
                                            } else {
                                                Notification::make()
                                                    ->title('Failed to Create Tag')
                                                    ->body($result['error'] ?? 'Unknown error occurred.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error Creating Tag')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            )
                            ->required(),

                        Forms\Components\Placeholder::make('esp_status')
                            ->label('Email Service Provider Status')
                            ->content(function () {
                                $tenant = Filament::getTenant();
                                $espManager = app(EmailServiceProviderManager::class);
                                $provider = $espManager->getProviderForTenant($tenant);
                                
                                if (!$provider) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<span class="text-red-600 font-medium">❌ Not configured</span><br>
                                        <span class="text-sm text-gray-500">Configure your ESP in settings to manage tags</span>'
                                    );
                                }
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<span class="text-green-600 font-medium">✅ ' . $provider->getName() . ' connected</span><br>
                                    <span class="text-sm text-gray-500">Tags are being loaded from your ESP</span>'
                                );
                            }),
                    ]),

                Forms\Components\Section::make('Content Body')
                    ->description('The main content that subscribers will see after email verification')
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
                            ->fileAttachmentsDirectory('email-subscriber-content/attachments')
                            ->helperText('Rich text content that will be displayed to verified email subscribers'),
                    ]),
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

                Tables\Columns\TextColumn::make('required_tag_id')
                    ->label('Required Tag')
                    ->getStateUsing(function (EmailSubscriberContent $record) {
                        if (!$record->required_tag_id) {
                            return 'No tag required';
                        }
                        
                        $tenant = Filament::getTenant();
                        $espManager = app(EmailServiceProviderManager::class);
                        $provider = $espManager->getProviderForTenant($tenant);
                        
                        if (!$provider) {
                            return $record->required_tag_id;
                        }
                        
                        try {
                            $tags = $provider->getTags();
                            $tag = collect($tags)->firstWhere('id', $record->required_tag_id);
                            return $tag['name'] ?? $record->required_tag_id;
                        } catch (\Exception $e) {
                            return $record->required_tag_id;
                        }
                    })
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('verification_count')
                    ->label('Verifications')
                    ->getStateUsing(fn (EmailSubscriberContent $record): int => 
                        $record->verificationRequests()->where('verified_at', '!=', null)->count()
                    )
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state <= 10 => 'success',
                        $state <= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('required_tag_id')
                    ->label('Required Tag')
                    ->placeholder('All tags')
                    ->options(function () {
                        $tenant = Filament::getTenant();
                        $espManager = app(EmailServiceProviderManager::class);
                        $provider = $espManager->getProviderForTenant($tenant);
                        
                        if (!$provider) {
                            return [];
                        }
                        
                        try {
                            $tags = $provider->getTags();
                            return collect($tags)->pluck('name', 'id')->toArray();
                        } catch (\Exception $e) {
                            return [];
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview Access Form')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modal()
                    ->modalWidth('4xl')
                    ->modalContent(function (EmailSubscriberContent $record) {
                        $tenant = Filament::getTenant();
                        $channelname = $tenant->getChannelName() ?? 'channel';
                        $contentUrl = "/p/{$channelname}/{$record->slug}";
                        
                        // Get required tag name
                        $tagName = 'Unknown Tag';
                        if ($record->required_tag_id) {
                            $espManager = app(EmailServiceProviderManager::class);
                            $provider = $espManager->getProviderForTenant($tenant);
                            if ($provider) {
                                try {
                                    $tags = $provider->getTags();
                                    $tag = collect($tags)->firstWhere('id', $record->required_tag_id);
                                    $tagName = $tag['name'] ?? $record->required_tag_id;
                                } catch (\Exception $e) {
                                    $tagName = $record->required_tag_id;
                                }
                            }
                        }
                        
                        return view('components.email-content-preview', [
                            'content' => $record,
                            'tenant' => $tenant,
                            'channelname' => $channelname,
                            'contentUrl' => $contentUrl,
                            'tagName' => $tagName,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn() => 
                        Actions\Modal\Actions\Action::make('visit_page')
                            ->label('Visit Actual Page')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('primary')
                            ->url(function (EmailSubscriberContent $record) {
                                $tenant = Filament::getTenant();
                                $channelname = $tenant->getChannelName() ?? 'channel';
                                return route('email-gated-content.show', [
                                    'channelname' => $channelname,
                                    'slug' => $record->slug
                                ]);
                            })
                            ->openUrlInNewTab()
                    ),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    
                    Tables\Actions\BulkAction::make('duplicate')
                        ->label('Duplicate Selected')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $tenant = Filament::getTenant();
                            $duplicatedCount = 0;
                            
                            foreach ($records as $record) {
                                $newTitle = $record->title . ' (Copy)';
                                $newSlug = Str::slug($newTitle);
                                
                                // Ensure slug uniqueness
                                $counter = 1;
                                while (EmailSubscriberContent::where('slug', $newSlug)->exists()) {
                                    $newSlug = Str::slug($newTitle . ' ' . $counter);
                                    $counter++;
                                }
                                
                                EmailSubscriberContent::create([
                                    'tenant_id' => $tenant->id,
                                    'title' => $newTitle,
                                    'slug' => $newSlug,
                                    'content' => $record->content,
                                    'required_tag_id' => $record->required_tag_id,
                                ]);
                                
                                $duplicatedCount++;
                            }
                            
                            Notification::make()
                                ->title('Content Duplicated')
                                ->body("Successfully duplicated {$duplicatedCount} content item(s).")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will create copies of the selected content items with "(Copy)" added to their titles.'),
                        
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export as JSON')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $exportData = $records->map(function ($record) {
                                return [
                                    'title' => $record->title,
                                    'slug' => $record->slug,
                                    'content' => $record->content,
                                    'required_tag_id' => $record->required_tag_id,
                                    'created_at' => $record->created_at->toISOString(),
                                ];
                            });
                            
                            $fileName = 'email-gated-content-' . now()->format('Y-m-d-H-i-s') . '.json';
                            $filePath = storage_path('app/temp/' . $fileName);
                            
                            // Create temp directory if it doesn't exist
                            if (!file_exists(dirname($filePath))) {
                                mkdir(dirname($filePath), 0755, true);
                            }
                            
                            file_put_contents($filePath, json_encode($exportData, JSON_PRETTY_PRINT));
                            
                            Notification::make()
                                ->title('Export Ready')
                                ->body("Exported {$records->count()} content item(s) to {$fileName}")
                                ->success()
                                ->send();
                            
                            return response()->download($filePath)->deleteFileAfterSend();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No email-gated content yet')
            ->emptyStateDescription('Create your first piece of content that requires email verification to access.')
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Email-Gated Content')
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
            'index' => Pages\ListEmailSubscriberContent::route('/'),
            'create' => Pages\CreateEmailSubscriberContent::route('/create'),
            'edit' => Pages\EditEmailSubscriberContent::route('/{record}/edit'),
            'analytics' => Pages\EmailContentAnalytics::route('/analytics'),
            'settings' => Pages\EmailSubscriberSettings::route('/settings'),
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
        return 'success';
    }
} 