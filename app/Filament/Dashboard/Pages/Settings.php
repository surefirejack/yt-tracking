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
        
        // Check YouTube connection status
        $youtubeConnected = $user->userParameters()
            ->where('name', 'youtube_connected')
            ->where('value', true)
            ->exists();
            
        $youtubeEmail = $user->userParameters()
            ->where('name', 'youtube_email')
            ->first()?->value ?? '';
        
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
            'youtube_connected' => $youtubeConnected,
            'youtube_email' => $youtubeEmail,
        ]);
        
        // Handle flash messages
        if (session('youtube_connected')) {
            Notification::make()
                ->success()
                ->title('YouTube Connected')
                ->body('Your YouTube account has been successfully connected.')
                ->send();
        }
        
        if (session('youtube_disconnected')) {
            Notification::make()
                ->success()
                ->title('YouTube Disconnected')
                ->body('Your YouTube account has been disconnected.')
                ->send();
        }
        
        if (session('youtube_error')) {
            Notification::make()
                ->danger()
                ->title('YouTube Connection Error')
                ->body(session('youtube_error'))
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Hidden fields for YouTube integration
                Forms\Components\Hidden::make('youtube_connected'),
                Forms\Components\Hidden::make('youtube_email'),
                
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
                        Tabs\Tab::make('Integrations')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Forms\Components\Section::make('YouTube Integration')
                                    ->description('Connect your YouTube account to track video analytics and performance.')
                                    ->schema([
                                        Forms\Components\Placeholder::make('youtube_status')
                                            ->label('Connection Status')
                                            ->content(function (Forms\Get $get) {
                                                $isConnected = $get('youtube_connected');
                                                $email = $get('youtube_email');
                                                
                                                if ($isConnected) {
                                                    $statusText = 'Connected';
                                                    if ($email) {
                                                        $statusText .= ' (' . $email . ')';
                                                    }
                                                    
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="flex items-center space-x-2">
                                                            <div class="flex items-center space-x-2 text-green-600">
                                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <span class="font-medium">' . $statusText . '</span>
                                                            </div>
                                                        </div>
                                                    ');
                                                }
                                                
                                                return new \Illuminate\Support\HtmlString('
                                                    <div class="flex items-center space-x-2 text-gray-500">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <span>Not Connected</span>
                                                    </div>
                                                ');
                                            }),
                                            
                                        Forms\Components\Placeholder::make('youtube_actions')
                                            ->label('')
                                            ->content(function (Forms\Get $get) {
                                                $isConnected = $get('youtube_connected');
                                                
                                                if ($isConnected) {
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="space-y-3">
                                                            <p class="text-sm text-gray-600">
                                                                Your YouTube account is connected and ready to track video analytics.
                                                            </p>
                                                            <button type="button" 
                                                                    onclick="disconnectYouTube()"
                                                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                                Disconnect YouTube
                                                            </button>
                                                            
                                                            <script>
                                                            function disconnectYouTube() {
                                                                if (!confirm("Are you sure you want to disconnect your YouTube account?")) {
                                                                    return;
                                                                }
                                                                
                                                                console.log("Making disconnect request...");
                                                                
                                                                // Get CSRF token
                                                                const token = document.querySelector(\'meta[name="csrf-token"]\')?.getAttribute(\'content\') 
                                                                           || document.querySelector(\'input[name="_token"]\')?.value;
                                                                
                                                                if (!token) {
                                                                    console.error("CSRF token not found");
                                                                    alert("Security token not found. Please refresh the page and try again.");
                                                                    return;
                                                                }
                                                                
                                                                fetch("' . url('/integrations/youtube/disconnect/' . Filament::getTenant()->uuid) . '", {
                                                                    method: "POST",
                                                                    headers: {
                                                                        "Content-Type": "application/json",
                                                                        "X-CSRF-TOKEN": token,
                                                                        "Accept": "application/json"
                                                                    },
                                                                    credentials: "same-origin"
                                                                })
                                                                .then(response => {
                                                                    console.log("Response status:", response.status);
                                                                    if (response.redirected) {
                                                                        console.log("Redirecting to:", response.url);
                                                                        window.location.href = response.url;
                                                                    } else if (response.ok) {
                                                                        // Reload the current page to see the updated state
                                                                        window.location.reload();
                                                                    } else {
                                                                        throw new Error("Request failed: " + response.status);
                                                                    }
                                                                })
                                                                .catch(error => {
                                                                    console.error("Disconnect error:", error);
                                                                    alert("Failed to disconnect YouTube account. Please try again.");
                                                                });
                                                            }
                                                            </script>
                                                        </div>
                                                    ');
                                                }
                                                
                                                return new \Illuminate\Support\HtmlString('
                                                    <div class="space-y-3">
                                                        <p class="text-sm text-gray-600">
                                                            Connect your YouTube account to start tracking video analytics, monitor performance metrics, and gain insights into your content.
                                                        </p>
                                                        <a href="' . url('/integrations/youtube/redirect?tenant=' . Filament::getTenant()->uuid) . '" 
                                                           class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                                            </svg> &nbsp;
                                                            Connect YouTube
                                                        </a>
                                                    </div>
                                                ');
                                            }),
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