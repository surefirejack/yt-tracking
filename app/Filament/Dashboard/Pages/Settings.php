<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\TenantCustomDomain;
use App\Services\TenantCustomDomainService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static string $view = 'filament.dashboard.pages.settings';
    
    protected static bool $shouldRegisterNavigation = false;
    
    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $tenant = Filament::getTenant();
        
        // Load existing custom domains
        $customDomains = $tenant->customDomains()->active()->get()->map(function ($domain) {
            return [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'is_primary' => $domain->is_primary,
                'is_verified' => $domain->is_verified,
                'ssl_status' => $domain->ssl_status,
            ];
        })->toArray();
        
        // Handle legacy time format conversion
        $bestTimeForEmails = $user->best_time_for_emails ?? '9';
        if (is_string($bestTimeForEmails) && strpos($bestTimeForEmails, ':') !== false) {
            // Convert from HH:MM format to hour integer
            $timeParts = explode(':', $bestTimeForEmails);
            $bestTimeForEmails = (string) intval($timeParts[0]);
        } elseif ($bestTimeForEmails instanceof \Carbon\Carbon) {
            // Handle Carbon datetime objects (from the datetime cast)
            $bestTimeForEmails = (string) $bestTimeForEmails->hour;
        }
        
        $this->form->fill([
            'best_time_for_emails' => $bestTimeForEmails,
            'custom_domains' => $customDomains,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Select::make('best_time_for_emails')
                                    ->label('Best time to receive email updates')
                                    ->helperText('Choose the time when you prefer to receive email notifications. Times are shown in your local timezone.')
                                    ->options($this->getTimeOptions())
                                    ->default('9')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        // This will be called when the time is selected
                                        // The $state contains the selected hour in Eastern time
                                    }),
                                
                                Forms\Components\Placeholder::make('timezone_note')
                                    ->label('')
                                    ->content('')
                                    ->extraAttributes([
                                        'x-data' => '{ 
                                            currentTime: "loading...",
                                            init() {
                                                this.updateCurrentTime();
                                                // Update time every minute
                                                setInterval(() => this.updateCurrentTime(), 60000);
                                            },
                                            updateCurrentTime() {
                                                const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                                                const now = new Date();
                                                
                                                const timeFormatter = new Intl.DateTimeFormat("en-US", {
                                                    hour: "numeric",
                                                    minute: "numeric", 
                                                    hour12: true,
                                                    timeZone: userTimeZone
                                                });
                                                this.currentTime = timeFormatter.format(now);
                                            }
                                        }',
                                    ])
                                    ->content(fn () => view('components.timezone-info')),
                            ]),
                        
                        Tabs\Tab::make('Domains')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Custom Domains')
                                    ->description('Manage custom domains for your workspace. Custom domains allow you to use your own domain name instead of the default subdomain.')
                                    ->schema([
                                        Forms\Components\Repeater::make('custom_domains')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Hidden::make('id'),
                                                
                                                Forms\Components\TextInput::make('domain')
                                                    ->label('Domain')
                                                    ->placeholder('go.yoursite.com')
                                                    ->prefix('https://')
                                                    ->required()
                                                    ->regex('/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z]{2,})+$/')
                                                    ->validationMessages([
                                                        'regex' => 'Please enter a valid domain name (e.g., example.com)',
                                                    ]),
                                                
                                                Forms\Components\Toggle::make('is_primary')
                                                    ->label('Primary Domain')
                                                    ->helperText('Set this as your primary domain'),
                                                
                                                Forms\Components\Placeholder::make('verification_status')
                                                    ->label('Status')
                                                    ->content(function (Forms\Get $get) {
                                                        $domainId = $get('id');
                                                        $isVerified = $get('is_verified');
                                                        $sslStatus = $get('ssl_status');
                                                        
                                                        if (!$domainId) {
                                                            return 'New domain - not yet saved';
                                                        }
                                                        
                                                        $status = $isVerified ? '✅ Verified' : '⏳ Pending Verification';
                                                        $ssl = $sslStatus === 'active' ? ' (🔒 SSL Active)' : '';
                                                        
                                                        return $status . $ssl;
                                                    })
                                                    ->visible(fn (Forms\Get $get) => !empty($get('id'))),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Custom Domain')
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->itemLabel(fn (array $state): ?string => $state['domain'] ?? 'New Domain')
                                            ->deleteAction(
                                                fn (Forms\Components\Actions\Action $action) => $action
                                                    ->requiresConfirmation()
                                                    ->modalDescription('Are you sure you want to remove this domain? This action cannot be undone.')
                                            ),
                                    ]),
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    protected function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();
        $tenant = Filament::getTenant();
        
        // Convert hour integer back to time format for database storage
        $bestTimeForEmails = $data['best_time_for_emails'];
        if (is_numeric($bestTimeForEmails)) {
            $bestTimeForEmails = sprintf('%02d:00', (int) $bestTimeForEmails);
        }
        
        // Save user settings
        $user->update([
            'best_time_for_emails' => $bestTimeForEmails,
        ]);

        // Handle custom domains
        if (isset($data['custom_domains'])) {
            $domainService = app(TenantCustomDomainService::class);
            $existingDomainIds = collect($data['custom_domains'])->pluck('id')->filter()->toArray();
            
            // Remove domains that are no longer in the form
            $tenant->customDomains()->whereNotIn('id', $existingDomainIds)->delete();
            
            foreach ($data['custom_domains'] as $domainData) {
                if (!empty($domainData['domain'])) {
                    try {
                        if (!empty($domainData['id'])) {
                            // Update existing domain
                            $domain = $tenant->customDomains()->find($domainData['id']);
                            if ($domain) {
                                $domain->update([
                                    'domain' => $domainData['domain'],
                                    'is_primary' => $domainData['is_primary'] ?? false,
                                ]);
                            }
                        } else {
                            // Add new domain
                            $domainService->addCustomDomain(
                                $tenant,
                                $domainData['domain'],
                                $domainData['is_primary'] ?? false
                            );
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error saving domain')
                            ->body('Domain "' . $domainData['domain'] . '" could not be saved: ' . $e->getMessage())
                            ->send();
                        
                        return;
                    }
                }
            }
        }

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Your settings have been updated successfully.')
            ->send();
            
        // Refresh the form data
        $this->mount();
    }

    protected function getTimezoneOptions(): array
    {
        $timezones = [];
        
        foreach (timezone_identifiers_list() as $timezone) {
            $timezones[$timezone] = str_replace('_', ' ', $timezone);
        }
        
        return $timezones;
    }

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public function getTitle(): string
    {
        return 'Settings';
    }

    protected function getTimeOptions(): array
    {
        // Predefined time options similar to the JavaScript version
        return [
            '3' => '3:00 AM',
            '6' => '6:00 AM', 
            '9' => '9:00 AM',
            '12' => '12:00 PM (noon)',
            '15' => '3:00 PM',
            '18' => '6:00 PM',
            '21' => '9:00 PM',
        ];
    }
} 