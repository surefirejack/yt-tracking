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
        
        // Generate tracking code
        $tenantUuid = $tenant->uuid ?? 'YOUR_TENANT_UUID';
        $baseUrl = config('app.url', 'https://youtubetracking.test');
        $trackingCode = <<<EOT
<script>
    (function() {
        window.ytTracking = window.ytTracking || {};
        window.ytTracking.tenantUuid = '{$tenantUuid}';
        
        var script = document.createElement('script');
        script.src = '{$baseUrl}/js/universal.js';
        script.async = true;
        script.defer = true;
        
        var firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(script, firstScript);
    })();
</script>
EOT;
        
        $this->form->fill([
            'best_time_for_emails' => $bestTimeForEmails,
            'custom_domains' => $customDomains,
            'tracking_code' => $trackingCode,
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
                                Forms\Components\Section::make('Tracking Code')
                                    ->description('Add this tracking code to your website.')
                                    ->schema([
                                        Forms\Components\Textarea::make('tracking_code')
                                            ->label('Tracking Code')
                                            ->readonly()
                                            ->rows(10)
                                            ->maxLength(255)
                                            ->extraAttributes([
                                                'id' => 'tracking-code-textarea'
                                            ]),
                                        
                                        Forms\Components\Placeholder::make('copy_button')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="mt-3">
                                                    <button type="button" 
                                                            onclick="copyTrackingCode()"
                                                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Copy to Clipboard
                                                    </button>
                                                </div>
                                                
                                                <script>
                                                function copyTrackingCode() {
                                                    console.log("Copy button clicked");
                                                    
                                                    setTimeout(function() {
                                                        var selectors = [
                                                            "#tracking-code-textarea",
                                                            "textarea[readonly]", 
                                                            "textarea"
                                                        ];
                                                        
                                                        var element = null;
                                                        for (var i = 0; i < selectors.length; i++) {
                                                            element = document.querySelector(selectors[i]);
                                                            if (element && element.value) {
                                                                console.log("Found textarea with selector: " + selectors[i]);
                                                                break;
                                                            }
                                                        }
                                                        
                                                        if (!element) {
                                                            alert("Could not find textarea");
                                                            return;
                                                        }
                                                        
                                                        var textToCopy = element.value || "";
                                                        console.log("Text length: " + textToCopy.length);
                                                        
                                                        if (!textToCopy.trim()) {
                                                            alert("No text to copy");
                                                            return;
                                                        }
                                                        
                                                        if (navigator.clipboard) {
                                                            navigator.clipboard.writeText(textToCopy).then(function() {
                                                                alert("Copied " + textToCopy.length + " characters!");
                                                            });
                                                        } else {
                                                            var temp = document.createElement("textarea");
                                                            temp.value = textToCopy;
                                                            document.body.appendChild(temp);
                                                            temp.select();
                                                            document.execCommand("copy");
                                                            document.body.removeChild(temp);
                                                            alert("Copied " + textToCopy.length + " characters!");
                                                        }
                                                    }, 100);
                                                }
                                                </script>
                                            ')),
                                    ]),
                            ]),
                        Tabs\Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
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