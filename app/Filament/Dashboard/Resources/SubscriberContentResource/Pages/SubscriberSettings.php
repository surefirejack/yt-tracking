<?php

namespace App\Filament\Dashboard\Resources\SubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\SubscriberContentResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use WallaceMaxters\FilamentImageColorPicker\ImageColorPicker;

class SubscriberSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SubscriberContentResource::class;

    protected static string $view = 'filament.dashboard.resources.subscriber-content-resource.pages.subscriber-settings';

    protected ?string $heading = 'YouTube Subscribers Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        
        $this->form->fill([
            'subscriber_only_lms_status' => $tenant->subscriber_only_lms_status ?? false,
            'subscription_cache_days' => $tenant->subscription_cache_days ?? 7,
            'member_login_text' => $tenant->member_login_text ?? '',
            'member_profile_image' => $tenant->member_profile_image ?? '',
            'logout_redirect_url' => $tenant->logout_redirect_url ?? '',
            'subscriber_accent_color' => $tenant->subscriber_accent_color ?? '#3b82f6',
        ]);
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        
        return $form
            ->schema([
                Forms\Components\Section::make('Feature Configuration')
                    ->description('Control the YouTube Subscribers feature for your account')
                    ->schema([
                        Forms\Components\Toggle::make('subscriber_only_lms_status')
                            ->label('Enable YouTube Subscribers Feature')
                            ->helperText('Allow subscribers to access exclusive content after verifying their YouTube subscription')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    // When disabling, show confirmation
                                    $this->dispatch('show-disable-confirmation');
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Branding & Appearance')
                    ->description('Customize the look and feel of your subscriber area')
                    ->schema([
                        // Banner Preview
                        Forms\Components\Placeholder::make('banner_preview')
                            ->label('Your Channel Banner')
                            ->content(function () use ($tenant) {
                                if ($tenant->ytChannel?->banner_image_url) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="max-w-2xl">
                                            <img src="' . $tenant->ytChannel->banner_image_url . '" 
                                                 alt="Channel Banner" 
                                                 class="w-full h-32 object-cover rounded-lg border border-gray-200 shadow-sm">
                                            <p class="text-xs text-gray-500 mt-2">This banner will be displayed on your subscriber pages. Use the color picker below to match your banner colors.</p>
                                        </div>'
                                    );
                                } else {
                                    return 'No banner found. Connect your YouTube channel to display your banner here.';
                                }
                            }),

                        // Color picker - use image picker if banner exists, otherwise regular picker
                        Forms\Components\Group::make([
                            ImageColorPicker::make('subscriber_accent_color')
                                ->label('Accent Color')
                                ->helperText('Click on your banner image above to pick a color that matches your brand.')
                                ->image(function () {
                                    $tenant = Filament::getTenant();
                                    return $tenant->ytChannel?->banner_image_url;
                                })
                                ->columnSpanFull()
                                ->default('#3b82f6')
                                ->live()
                                ->visible(function () {
                                    $tenant = Filament::getTenant();
                                    return !empty($tenant->ytChannel?->banner_image_url);
                                }),

                            Forms\Components\ColorPicker::make('subscriber_accent_color')
                                ->label('Accent Color')
                                ->helperText('Choose a color that matches your brand. Connect your YouTube channel to pick colors from your banner image.')
                                ->default('#3b82f6')
                                ->live()
                                ->visible(function () {
                                    $tenant = Filament::getTenant();
                                    return empty($tenant->ytChannel?->banner_image_url);
                                }),
                        ]),

                        Forms\Components\Placeholder::make('color_preview')
                            ->label('Color Preview')
                            ->content(function (Forms\Get $get) {
                                $color = $get('subscriber_accent_color') ?? '#3b82f6';
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full border-2 border-gray-200" style="background-color: ' . $color . ';"></div>
                                        <div class="text-sm">
                                            <span class="px-3 py-1 rounded-md text-white font-medium" style="background-color: ' . $color . ';">Sample Button</span>
                                        </div>
                                    </div>'
                                );
                            })
                            ->live(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('subscriber_only_lms_status')),

                Forms\Components\Section::make('Subscriber Experience')
                    ->description('Configure how subscribers interact with your content')
                    ->schema([
                        Forms\Components\TextInput::make('subscription_cache_days')
                            ->label('Keep subscribers logged in this many days')
                            ->helperText('How many days to remember a subscriber\'s verification before checking again')
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(90)
                            ->suffix('days')
                            ->required(),

                        Forms\Components\Textarea::make('member_login_text')
                            ->label('Custom Login Message')
                            ->helperText('Optional message shown to subscribers on the login page')
                            ->placeholder('Unlock exclusive content by verifying your YouTube subscription!')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\FileUpload::make('member_profile_image')
                            ->label('Profile Image for Login Page')
                            ->helperText('Upload a profile image to display on the login page (builds trust with subscribers)')
                            ->image()
                            ->imageEditor()
                            ->directory('subscriber-profile-images')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                        Forms\Components\TextInput::make('logout_redirect_url')
                            ->label('When a subscriber logs out, redirect them here:')
                            ->helperText('Optional custom URL to redirect subscribers after logout (leave empty for default)')
                            ->url()
                            ->placeholder('https://your-website.com')
                            ->maxLength(500),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('subscriber_only_lms_status')),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        $tenant = Filament::getTenant();
        $actions = [];

        // Save Action
        $actions[] = Actions\Action::make('save')
            ->label('Save Settings')
            ->icon('heroicon-o-check')
            ->action('save');

        // Preview Login Page Action
        if ($tenant->subscriber_only_lms_status && $tenant->ytChannel) {
            $channelname = strtolower(str_replace('@', '', $tenant->ytChannel->handle ?? ''));
            if ($channelname) {
                $actions[] = Actions\Action::make('preview_login')
                    ->label('Preview Login Page')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url("/s/{$channelname}/login?preview=1")
                    ->openUrlInNewTab();
            }
        }

        return $actions;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $tenant = Filament::getTenant();

        // Check if the feature is being disabled
        if (!$data['subscriber_only_lms_status'] && $tenant->subscriber_only_lms_status) {
            // Feature is being disabled - show confirmation modal handled by JavaScript
            $this->js('
                if (confirm("⚠️ WARNING: Disabling this feature will make all subscriber content inaccessible to your subscribers. Are you sure you want to continue?")) {
                    $wire.confirmDisable();
                } else {
                    $wire.set("data.subscriber_only_lms_status", true);
                }
            ');
            return;
        }

        $this->updateTenantSettings($data);
    }

    public function confirmDisable(): void
    {
        $data = $this->form->getState();
        $this->updateTenantSettings($data);

        Notification::make()
            ->warning()
            ->title('YouTube Subscribers Feature Disabled')
            ->body('Your subscriber content is now inaccessible to subscribers.')
            ->send();
    }

    private function updateTenantSettings(array $data): void
    {
        $tenant = Filament::getTenant();

        $tenant->update([
            'subscriber_only_lms_status' => $data['subscriber_only_lms_status'],
            'subscription_cache_days' => $data['subscription_cache_days'],
            'member_login_text' => $data['member_login_text'],
            'member_profile_image' => $data['member_profile_image'],
            'logout_redirect_url' => $data['logout_redirect_url'],
            'subscriber_accent_color' => $data['subscriber_accent_color'] ?? '#3b82f6',
        ]);

        Notification::make()
            ->success()
            ->title('Settings Updated')
            ->body('Your YouTube Subscribers settings have been saved successfully.')
            ->send();

        // Refresh the form
        $this->mount();
    }

    public function getTitle(): string
    {
        return 'YouTube Subscribers Settings';
    }
} 