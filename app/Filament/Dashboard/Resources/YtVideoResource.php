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
use App\Jobs\UpdateLinkJob;
use App\Services\TagService;
use App\Models\Tag;

class YtVideoResource extends Resource
{
    protected static ?string $model = YtVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Your Videos';

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->whereHas('ytChannel', function (Builder $query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // This form is not used since we use slideOver in the table action
                // All link management is handled in the EditAction slideover
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('1.5s') // Live updates every 1.5 seconds
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->width(75),
                    // ->height(60),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('likes')
                    ->label('Likes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('links_found')
                    ->label('Untracked Links')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state <= 1 => 'success',
                        $state <= 5 => 'info', 
                        $state <= 10 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('manageLinks')
                    ->label('Convert Links')
                    ->icon('heroicon-o-link')
                    ->slideOver()
                    ->modalSubmitActionLabel('Convert These Links')
                    ->modalSubmitAction(fn ($action) => $action->icon('heroicon-o-arrow-path-rounded-square'))
                    ->form([
                        Forms\Components\Section::make('Choose Links to Update')
                            ->description('Select the links you want to create short links for')
                            ->icon('heroicon-o-check-circle')
                            ->schema([
                                Forms\Components\CheckboxList::make('allowed_links')
                                    ->label('')
                                    ->options(function ($record) {
                                        if (!$record) return [];
                                        $categorized = $record->getCategorizedUrls();
                                        $existing = $record->getExistingLinkUrls();
                                        
                                        $options = [];
                                        foreach ($categorized['allowed'] as $url) {
                                            $label = $url;
                                            if (in_array($url, $existing)) {
                                                $label .= ' ✓ (Already created)';
                                            }
                                            $options[$url] = $label;
                                        }
                                        return $options;
                                    })
                                    ->columns(1)
                                    ->default(function ($record) {
                                        if (!$record) return [];
                                        return $record->getExistingLinkUrls();
                                    })
                            ])
                            ->visible(function ($record) {
                                if (!$record) return false;
                                $categorized = $record->getCategorizedUrls();
                                return !empty($categorized['allowed']);
                            }),
                            
                        Forms\Components\Section::make('Excluded Links')
                            ->description('These links contain social media or YouTube domains and are excluded')
                            ->icon('heroicon-o-x-circle')
                            ->schema([
                                Forms\Components\Placeholder::make('excluded_links_list')
                                    ->label('')
                                    ->content(function ($record) {
                                        if (!$record) return '';
                                        $categorized = $record->getCategorizedUrls();
                                        if (empty($categorized['excluded'])) {
                                            return 'No excluded links found.';
                                        }
                                        
                                        $html = '<div class="space-y-2">';
                                        foreach ($categorized['excluded'] as $url) {
                                            $html .= '<div class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">';
                                            $html .= '<span class="text-red-500">×</span>';
                                            $html .= '<span class="break-all">' . htmlspecialchars($url) . '</span>';
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                            ])
                            ->visible(function ($record) {
                                if (!$record) return false;
                                $categorized = $record->getCategorizedUrls();
                                return !empty($categorized['excluded']);
                            }),
                    ])
                    ->action(function (array $data, $record) {
                        $selectedLinks = $data['allowed_links'] ?? [];
                        $existing = $record->getExistingLinkUrls();
                        $tenantId = \Filament\Facades\Filament::getTenant()->id;
                        $videoId = $record->id;
                        $videoTagName = 'yt-video-' . $videoId;
                        $tagService = new TagService();
                        $newLinksCount = 0;
                        $updatedLinksCount = 0;

                        foreach ($selectedLinks as $url) {
                            if (!in_array($url, $existing)) {
                                // Create new link as before
                                $link = \App\Models\Link::create([
                                    'tenant_id' => $tenantId,
                                    'original_url' => $url,
                                    'yt_video_id' => $videoId,
                                    'title' => 'From ' . $record->title,
                                    'status' => 'pending',
                                ]);
                                \App\Jobs\CreateLinkJob::dispatch($link);
                                $newLinksCount++;
                            } else {
                                // Existing link: update relationships and tags
                                $link = \App\Models\Link::where('tenant_id', $tenantId)
                                    ->where('original_url', $url)
                                    ->first();
                                if ($link) {
                                    // 1. Attach link to video (many-to-many)
                                    $link->ytVideos()->syncWithoutDetaching([$videoId]);

                                    // 2. Ensure tag exists and attach to link
                                    $tag = $tagService->createTag($videoTagName, $tenantId);
                                    if ($tag && !$link->tagModels()->where('tags.id', $tag->id)->exists()) {
                                        $link->tagModels()->attach($tag->id);
                                    }

                                    // 3. Update Dub.co with the new tag
                                    // Merge all tag names for this link
                                    $allTagNames = $link->tagModels()->pluck('name')->toArray();
                                    if (!in_array($videoTagName, $allTagNames)) {
                                        $allTagNames[] = $videoTagName;
                                    }
                                    $updateData = [
                                        'tagNames' => $allTagNames,
                                    ];
                                    UpdateLinkJob::dispatch($link, $updateData);
                                    $updatedLinksCount++;
                                }
                            }
                        }

                        $messages = [];
                        if ($newLinksCount > 0) {
                            $messages[] = "$newLinksCount new links have been queued for processing.";
                        }
                        if ($updatedLinksCount > 0) {
                            $messages[] = "$updatedLinksCount existing links have been updated with this video and tag.";
                        }
                        if (empty($messages)) {
                            $messages[] = 'All selected links were already associated.';
                        }
                        Notification::make()
                            ->title('Link Conversion Results')
                            ->body(implode("\n", $messages))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
