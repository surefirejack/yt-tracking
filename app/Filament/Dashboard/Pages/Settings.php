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
            
        // Check if tokens are valid (not expired and has refresh token)
        $youtubeTokenValid = false;
        if ($youtubeConnected) {
            $youtubeTokenService = app(\App\Services\YouTubeTokenService::class);
            $youtubeTokenValid = $youtubeTokenService->hasValidConnection($user);
        }
            
        $youtubeEmail = $user->userParameters()
            ->where('name', 'youtube_email')
            ->first()?->value ?? '';

        $youtubeNickname = $user->userParameters()
            ->where('name', 'youtube_nickname')
            ->first()?->value ?? '';

        $youtubeUserId = $user->userParameters()
            ->where('name', 'youtube_user_id')
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
            'youtube_token_valid' => $youtubeTokenValid,
            'youtube_email' => $youtubeEmail,
            'youtube_nickname' => $youtubeNickname,
            'youtube_user_id' => $youtubeUserId,
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
                Forms\Components\Hidden::make('youtube_token_valid'),
                Forms\Components\Hidden::make('youtube_email'),
                Forms\Components\Hidden::make('youtube_nickname'),
                Forms\Components\Hidden::make('youtube_user_id'),
                
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
                                        Forms\Components\ViewField::make('youtube_status')
                                            ->label('Connection Status')
                                            ->view('components.youtube-connection-badge')
                                            ->viewData(function (Forms\Get $get) {
                                                $isConnected = $get('youtube_connected');
                                                $tokenValid = $get('youtube_token_valid');
                                                $email = $get('youtube_email');
                                                $nickname = $get('youtube_nickname');
                                                $userId = $get('youtube_user_id');
                                                
                                                return [
                                                    'is_connected' => $isConnected,
                                                    'token_valid' => $tokenValid,
                                                    'email' => $email,
                                                    'nickname' => $nickname,
                                                    'user_id' => $userId,
                                                ];
                                            }),
                                            
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('connect_youtube')
                                                ->label('Connect YouTube')
                                                ->icon('heroicon-o-video-camera')
                                                ->color('danger')
                                                ->size('sm')
                                                ->url(fn () => url('/integrations/youtube/redirect?tenant=' . Filament::getTenant()->uuid))
                                                ->visible(fn (Forms\Get $get) => !$get('youtube_connected')),
                                                
                                            Forms\Components\Actions\Action::make('disconnect_youtube')
                                                ->label('Disconnect YouTube')
                                                ->icon('heroicon-o-x-mark')
                                                ->color('gray')
                                                ->size('sm')
                                                ->requiresConfirmation()
                                                ->modalHeading('Disconnect YouTube Account')
                                                ->modalDescription('Are you sure you want to disconnect your YouTube account? This will remove access to video analytics.')
                                                ->modalSubmitActionLabel('Yes, disconnect')
                                                ->action(function () {
                                                    // Use JavaScript to make the disconnect request
                                                    return redirect()->to('javascript:void(0)');
                                                })
                                                ->extraAttributes([
                                                    'onclick' => 'disconnectYouTube(); return false;'
                                                ])
                                                ->visible(fn (Forms\Get $get) => $get('youtube_connected')),
                                        ])
                                        ->alignment('start'),
                                        
                                        Forms\Components\Placeholder::make('youtube_description')
                                            ->label('')
                                            ->content(function (Forms\Get $get) {
                                                $isConnected = $get('youtube_connected');
                                                
                                                if ($isConnected) {
                                                    return 'Your YouTube account is connected and ready to track video analytics and performance metrics.';
                                                } else {
                                                    return 'Connect your YouTube account to start tracking video analytics, monitor performance metrics, and gain insights into your content.';
                                                }
                                            })
                                            ->extraAttributes(['class' => 'text-sm text-gray-600 dark:text-gray-400']),
                                            
                                        // Keep the JavaScript function for disconnect
                                        Forms\Components\Placeholder::make('disconnect_script')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <script>
                                                function disconnectYouTube() {
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
                                            '))
                                            ->visible(fn (Forms\Get $get) => $get('youtube_connected')),
                                    ]),

                                // YouTube Subscription Test Section (MVP)
                                Forms\Components\Section::make('YouTube Subscription Test (MVP)')
                                    ->description('Test if users are subscribed to specific YouTube channels - useful for building subscriber-only content areas.')
                                    ->schema([
                                        Forms\Components\Placeholder::make('subscription_test_status')
                                            ->label('Test Status')
                                            ->content(function (Forms\Get $get) {
                                                $isConnected = $get('youtube_connected');
                                                $tokenValid = $get('youtube_token_valid');
                                                
                                                if ($isConnected && $tokenValid) {
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Ready to test
                                                            </span>
                                                        </div>
                                                    ');
                                                } elseif ($isConnected && !$tokenValid) {
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-orange-800 bg-orange-100 rounded-full">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Connection expired
                                                            </span>
                                                            <span class="text-sm text-orange-600">Please reconnect your YouTube account</span>
                                                        </div>
                                                    ');
                                                } else {
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Connect YouTube first
                                                            </span>
                                                        </div>
                                                    ');
                                                }
                                            }),

                                        Forms\Components\TextInput::make('subscription_test_channel')
                                            ->label('Channel ID or Username')
                                            ->placeholder('e.g. UCBJycsmduvYEL83R_U4JriQ or @channelname')
                                            ->helperText('Enter either a YouTube channel ID (starts with UC) or username (starts with @)')
                                            ->live()
                                            ->visible(fn (Forms\Get $get) => $get('youtube_connected') && $get('youtube_token_valid')),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_subscription')
                                                ->label('Check Subscription Status')
                                                ->icon('heroicon-o-magnifying-glass')
                                                ->color('primary')
                                                ->size('sm')
                                                ->requiresConfirmation(false)
                                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                                    $channelIdentifier = $get('subscription_test_channel');
                                                    
                                                    if (empty($channelIdentifier)) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->warning()
                                                            ->title('Missing Channel')
                                                            ->body('Please enter a channel ID or username to test.')
                                                            ->send();
                                                        return;
                                                    }

                                                    $user = auth()->user();
                                                    $youtubeApiService = app(\App\Services\YouTubeApiService::class);
                                                    
                                                    try {
                                                        // Get channel information
                                                        $channelInfo = $youtubeApiService->getChannelByIdentifier($user, $channelIdentifier);
                                                        
                                                        if (!$channelInfo || empty($channelInfo['items'])) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->danger()
                                                                ->title('Channel Not Found')
                                                                ->body('Could not find a channel with that identifier. Please check and try again.')
                                                                ->send();
                                                            return;
                                                        }

                                                        $channel = $channelInfo['items'][0];
                                                        $channelId = $channel['id'];
                                                        $channelTitle = $channel['snippet']['title'];
                                                        
                                                        // Check subscription
                                                        $subscriptionData = $youtubeApiService->checkSubscription($user, $channelId);
                                                        $isSubscribed = $subscriptionData !== null;
                                                        
                                                        if ($isSubscribed) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->success()
                                                                ->title('✅ Subscribed!')
                                                                ->body("You are subscribed to {$channelTitle}")
                                                                ->duration(8000)
                                                                ->send();
                                                        } else {
                                                            \Filament\Notifications\Notification::make()
                                                                ->warning()
                                                                ->title('❌ Not Subscribed')
                                                                ->body("You are not subscribed to {$channelTitle}")
                                                                ->duration(8000)
                                                                ->send();
                                                        }

                                                        // Store the last test result for display
                                                        $set('last_test_result', [
                                                            'channel_title' => $channelTitle,
                                                            'channel_id' => $channelId,
                                                            'is_subscribed' => $isSubscribed,
                                                            'tested_at' => now()->format('M j, Y g:i A')
                                                        ]);

                                                    } catch (\Exception $e) {
                                                        \Log::error('Subscription test error in settings', [
                                                            'user_id' => $user->id,
                                                            'channel_identifier' => $channelIdentifier,
                                                            'error' => $e->getMessage()
                                                        ]);
                                                        
                                                        \Filament\Notifications\Notification::make()
                                                            ->danger()
                                                            ->title('Test Failed')
                                                            ->body('An error occurred while testing: ' . $e->getMessage())
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn (Forms\Get $get) => $get('youtube_connected') && $get('youtube_token_valid') && !empty($get('subscription_test_channel'))),

                                            Forms\Components\Actions\Action::make('list_subscriptions')
                                                ->label('Show My Subscriptions')
                                                ->icon('heroicon-o-list-bullet')
                                                ->color('secondary')
                                                ->size('sm')
                                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                                    $user = auth()->user();
                                                    $youtubeApiService = app(\App\Services\YouTubeApiService::class);
                                                    
                                                    try {
                                                        // Get user's subscriptions
                                                        $subscriptionsList = $youtubeApiService->getUserSubscriptionsList($user, 25);
                                                        
                                                        if (empty($subscriptionsList)) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->warning()
                                                                ->title('No Subscriptions Found')
                                                                ->body('You don\'t appear to have any public subscriptions, or they might be private.')
                                                                ->duration(8000)
                                                                ->send();
                                                            return;
                                                        }

                                                        // Store the subscriptions list for display
                                                        $set('user_subscriptions', $subscriptionsList);

                                                        \Filament\Notifications\Notification::make()
                                                            ->success()
                                                            ->title('Subscriptions Loaded')
                                                            ->body('Found ' . count($subscriptionsList) . ' subscriptions. You can now copy channel IDs to test.')
                                                            ->duration(8000)
                                                            ->send();

                                                    } catch (\Exception $e) {
                                                        \Log::error('Error loading subscriptions in settings', [
                                                            'user_id' => $user->id,
                                                            'error' => $e->getMessage()
                                                        ]);
                                                        
                                                        \Filament\Notifications\Notification::make()
                                                            ->danger()
                                                            ->title('Failed to Load Subscriptions')
                                                            ->body('An error occurred: ' . $e->getMessage())
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn (Forms\Get $get) => $get('youtube_connected') && $get('youtube_token_valid')),
                                        ])
                                        ->alignment('start'),

                                        Forms\Components\Placeholder::make('last_test_result_display')
                                            ->label('Last Test Result')
                                            ->content(function (Forms\Get $get) {
                                                $result = $get('last_test_result');
                                                
                                                if (!$result) {
                                                    return 'No tests run yet.';
                                                }
                                                
                                                $statusIcon = $result['is_subscribed'] ? '✅' : '❌';
                                                $statusText = $result['is_subscribed'] ? 'Subscribed' : 'Not Subscribed';
                                                $statusColor = $result['is_subscribed'] ? 'text-green-600' : 'text-orange-600';
                                                
                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='bg-gray-50 rounded-lg p-3 border'>
                                                        <div class='flex items-center gap-2 mb-2'>
                                                            <span class='text-lg'>{$statusIcon}</span>
                                                            <span class='font-medium {$statusColor}'>{$statusText}</span>
                                                        </div>
                                                        <div class='text-sm text-gray-600'>
                                                            <div><strong>Channel:</strong> {$result['channel_title']}</div>
                                                            <div><strong>ID:</strong> <code class='text-xs bg-gray-200 px-1 rounded'>{$result['channel_id']}</code></div>
                                                            <div><strong>Tested:</strong> {$result['tested_at']}</div>
                                                        </div>
                                                    </div>
                                                ");
                                            })
                                            ->visible(fn (Forms\Get $get) => !empty($get('last_test_result'))),

                                        Forms\Components\Hidden::make('last_test_result'),

                                        Forms\Components\Placeholder::make('user_subscriptions_display')
                                            ->label('Your Subscriptions')
                                            ->content(function (Forms\Get $get) {
                                                $subscriptions = $get('user_subscriptions');
                                                
                                                if (!$subscriptions || empty($subscriptions)) {
                                                    return 'Click "Show My Subscriptions" above to load your subscription list.';
                                                }
                                                
                                                $html = '<div class="space-y-3 max-h-96 overflow-y-auto">';
                                                
                                                foreach ($subscriptions as $index => $subscription) {
                                                    $channelId = htmlspecialchars($subscription['channel_id']);
                                                    $channelTitle = htmlspecialchars($subscription['channel_title']);
                                                    $description = htmlspecialchars(substr($subscription['description'] ?? '', 0, 100));
                                                    if (strlen($subscription['description'] ?? '') > 100) {
                                                        $description .= '...';
                                                    }
                                                    
                                                    $html .= "
                                                        <div class='bg-white border rounded-lg p-3 hover:bg-gray-50'>
                                                            <div class='flex items-start justify-between'>
                                                                <div class='flex-1 min-w-0'>
                                                                    <h4 class='font-medium text-gray-900 truncate'>{$channelTitle}</h4>
                                                                    <p class='text-sm text-gray-600 mt-1'>{$description}</p>
                                                                    <div class='mt-2'>
                                                                        <code class='text-xs bg-gray-100 px-2 py-1 rounded font-mono'>{$channelId}</code>
                                                                    </div>
                                                                </div>
                                                                <button 
                                                                    type='button'
                                                                    onclick='copyChannelId(\"{$channelId}\")'
                                                                    class='ml-3 text-sm text-blue-600 hover:text-blue-800 font-medium'
                                                                    title='Copy Channel ID'
                                                                >
                                                                    Copy ID
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ";
                                                }
                                                
                                                $html .= '</div>';
                                                
                                                // Add JavaScript for copying
                                                $html .= '
                                                    <script>
                                                    function copyChannelId(channelId) {
                                                        if (navigator.clipboard) {
                                                            navigator.clipboard.writeText(channelId).then(function() {
                                                                // Find the subscription test input and populate it
                                                                const testInput = document.querySelector(\'input[name="data.subscription_test_channel"]\');
                                                                if (testInput) {
                                                                    testInput.value = channelId;
                                                                    testInput.dispatchEvent(new Event(\'input\', { bubbles: true }));
                                                                }
                                                                alert("Channel ID copied and filled in test field!");
                                                            }).catch(function() {
                                                                alert("Failed to copy channel ID");
                                                            });
                                                        } else {
                                                            alert("Copy not supported in this browser");
                                                        }
                                                    }
                                                    </script>
                                                ';
                                                
                                                return new \Illuminate\Support\HtmlString($html);
                                            })
                                            ->visible(fn (Forms\Get $get) => !empty($get('user_subscriptions'))),

                                        Forms\Components\Hidden::make('user_subscriptions'),

                                        Forms\Components\Placeholder::make('subscription_test_description')
                                            ->label('')
                                            ->content(function (Forms\Get $get) {
                                                $isConnected = $get('youtube_connected');
                                                
                                                if ($isConnected) {
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                                                            <p><strong>How to use:</strong></p>
                                                            <ol class="list-decimal list-inside space-y-1 ml-2">
                                                                <li>Find a YouTube channel you want to test</li>
                                                                <li>Get the channel ID (starts with UC) or username (starts with @)</li>
                                                                <li>Enter it above and click "Check Subscription Status"</li>
                                                                <li>The result will show if you\'re subscribed to that channel</li>
                                                            </ol>
                                                            <p class="mt-3"><strong>Use cases:</strong> Perfect for building subscriber-only content areas, exclusive member benefits, or gated content based on YouTube subscriptions.</p>
                                                        </div>
                                                    ');
                                                } else {
                                                    return 'Connect your YouTube account above to test subscription status for any channel.';
                                                }
                                            })
                                            ->extraAttributes(['class' => 'text-sm text-gray-600 dark:text-gray-400']),
                                    ])
                                    ->collapsible()
                                    ->collapsed(fn (Forms\Get $get) => !$get('youtube_connected'))
                                    ->visible(fn () => config('services.youtube.test_mode', false)),
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