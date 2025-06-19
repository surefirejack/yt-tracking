<?php

namespace App\Filament\Dashboard\Resources\EmailSubscriberContentResource\Pages;

use App\Filament\Dashboard\Resources\EmailSubscriberContentResource;
use App\Services\EmailServiceProvider\EmailServiceProviderManager;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class EmailSubscriberSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = EmailSubscriberContentResource::class;

    protected static string $view = 'filament.analytics.email-subscriber-settings';

    protected ?string $heading = 'Email Subscriber Settings';

    protected ?string $subheading = 'Configure your email service provider and verification settings';

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        
        $this->form->fill([
            'email_service_provider' => $tenant->email_service_provider ?? 'kit',
            'esp_api_credentials' => $tenant->esp_api_credentials ?? [],
            'email_verification_cookie_duration_days' => $tenant->email_verification_cookie_duration_days ?? 30,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Service Provider Configuration')
                    ->description('Connect your email service provider to manage subscriber tags and verification')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\Select::make('email_service_provider')
                            ->label('Email Service Provider')
                            ->options([
                                'kit' => 'Kit (ConvertKit)',
                                // Future providers can be added here
                                // 'mailchimp' => 'Mailchimp',
                                // 'activecampaign' => 'ActiveCampaign',
                            ])
                            ->default('kit')
                            ->required()
                            ->live()
                            ->helperText('Select your email service provider'),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('esp_api_credentials.api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable()
                                ->required()
                                ->maxLength(255)
                                ->helperText('Your Kit API key from your account settings')
                                ->placeholder('Enter your Kit API key')
                                ->visible(fn (Forms\Get $get) => $get('email_service_provider') === 'kit'),
                        ])
                        ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('test_connection')
                                ->label('Test Connection')
                                ->icon('heroicon-o-wifi')
                                ->color('info')
                                ->action(function (Forms\Get $get) {
                                    $provider = $get('email_service_provider');
                                    $credentials = $get('esp_api_credentials') ?? [];

                                    if (empty($credentials['api_key'])) {
                                        Notification::make()
                                            ->title('API Key Required')
                                            ->body('Please enter your API key before testing the connection.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $espManager = app(EmailServiceProviderManager::class);
                                    $result = $espManager->validateConfiguration($provider, $credentials);

                                    if ($result['valid']) {
                                        Notification::make()
                                            ->title('Connection Successful')
                                            ->body('Successfully connected to your email service provider!')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Connection Failed')
                                            ->body(implode(', ', $result['errors']))
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])
                        ->columnSpanFull(),

                        Forms\Components\Placeholder::make('connection_status')
                            ->label('Connection Status')
                            ->content(function () {
                                $tenant = Filament::getTenant();
                                $espManager = app(EmailServiceProviderManager::class);
                                $testResult = $espManager->testTenantConfiguration($tenant);

                                if ($testResult['success']) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-2">
                                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                            <span class="text-green-700 font-medium">Connected to ' . $testResult['provider'] . '</span>
                                        </div>'
                                    );
                                } else {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-2">
                                            <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                            <span class="text-red-700 font-medium">Not connected: ' . ($testResult['error'] ?? 'Unknown error') . '</span>
                                        </div>'
                                    );
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Email Verification Settings')
                    ->description('Configure how email verification works for your content')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('email_verification_cookie_duration_days')
                            ->label('Verification Cookie Duration')
                            ->helperText('How many days to remember a verified email before requiring re-verification')
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix('days')
                            ->required(),

                        Forms\Components\Placeholder::make('verification_info')
                            ->label('How Email Verification Works')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-sm text-gray-600 space-y-2">
                                    <p><strong>1. Visitor Request:</strong> User enters email to access gated content</p>
                                    <p><strong>2. ESP Check:</strong> System checks if email exists in your ESP with required tag</p>
                                    <p><strong>3. Verification Email:</strong> If not found, sends verification email</p>
                                    <p><strong>4. Subscriber Addition:</strong> Adds email to ESP with required tag after verification</p>
                                    <p><strong>5. Access Granted:</strong> User gets immediate access and secure cookie for future visits</p>
                                </div>'
                            )),
                    ]),

                Forms\Components\Section::make('Tag Management')
                    ->description('Manage your email service provider tags')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\Placeholder::make('available_tags')
                            ->label('Available Tags')
                            ->content(function () {
                                $tenant = Filament::getTenant();
                                $espManager = app(EmailServiceProviderManager::class);
                                $provider = $espManager->getProviderForTenant($tenant);

                                if (!$provider) {
                                    return 'Configure your ESP connection first to see available tags.';
                                }

                                try {
                                    $tags = $provider->getTags();
                                    
                                    if (empty($tags)) {
                                        return 'No tags found. Create tags in your ESP or use the content creation form to create new ones.';
                                    }

                                    $tagList = collect($tags)
                                        ->map(fn($tag) => '• ' . $tag['name'] . ' (ID: ' . $tag['id'] . ')')
                                        ->join('<br>');

                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm text-gray-700">
                                            <p class="font-medium mb-2">Found ' . count($tags) . ' tags:</p>
                                            <div class="space-y-1">' . $tagList . '</div>
                                        </div>'
                                    );
                                } catch (\Exception $e) {
                                    return 'Error loading tags: ' . $e->getMessage();
                                }
                            }),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('refresh_tags')
                                ->label('Refresh Tags')
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->action(function () {
                                    // Clear the tag cache
                                    $tenant = Filament::getTenant();
                                    if ($tenant->esp_api_credentials) {
                                        $cacheKey = 'kit_tags_' . hash('sha256', $tenant->esp_api_credentials['api_key'] ?? '');
                                        cache()->forget($cacheKey);
                                    }

                                    Notification::make()
                                        ->title('Tags Refreshed')
                                        ->body('Tag list has been updated from your ESP.')
                                        ->success()
                                        ->send();

                                    // Refresh the page to show updated tags
                                    $this->redirect(request()->header('Referer'));
                                }),
                        ])
                        ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),

            Actions\Action::make('manage_content')
                ->label('Manage Email-Gated Content')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(function () {
                    return EmailSubscriberContentResource::getUrl('index');
                }),

            Actions\Action::make('view_analytics')
                ->label('View Analytics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->url(function () {
                    return EmailSubscriberContentResource::getUrl('analytics');
                }),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $tenant = Filament::getTenant();

        // Validate ESP configuration before saving
        if (!empty($data['esp_api_credentials']['api_key'])) {
            $espManager = app(EmailServiceProviderManager::class);
            $validation = $espManager->validateConfiguration(
                $data['email_service_provider'],
                $data['esp_api_credentials']
            );

            if (!$validation['valid']) {
                Notification::make()
                    ->title('Invalid Configuration')
                    ->body(implode(', ', $validation['errors']))
                    ->danger()
                    ->send();
                return;
            }
        }

        $tenant->update([
            'email_service_provider' => $data['email_service_provider'],
            'esp_api_credentials' => $data['esp_api_credentials'],
            'email_verification_cookie_duration_days' => $data['email_verification_cookie_duration_days'],
        ]);

        Notification::make()
            ->success()
            ->title('Settings Updated')
            ->body('Your email subscriber settings have been saved successfully.')
            ->send();

        // Refresh the form
        $this->mount();
    }

    public function getTitle(): string
    {
        return 'Email Subscriber Settings';
    }
} 