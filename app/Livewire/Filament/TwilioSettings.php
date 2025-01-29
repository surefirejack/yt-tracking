<?php

namespace App\Livewire\Filament;

use App\Services\ConfigManager;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class TwilioSettings extends Component implements HasForms
{
    private ConfigManager $configManager;

    use InteractsWithForms;

    public ?array $data = [];

    public function boot(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    public function render()
    {
        return view('livewire.filament.twilio-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'sid' => $this->configManager->get('services.twilio.sid'),
            'token' => $this->configManager->get('services.twilio.token'),
            'from' => $this->configManager->get('services.twilio.from'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('sid')
                            ->label(__('SID'))
                            ->helperText(__('The Account SID from your Twilio account.'))
                            ->required(),
                        TextInput::make('token')
                            ->label(__('Token'))
                            ->helperText(__('The Auth Token from your Twilio account.'))
                            ->required(),
                        TextInput::make('from')
                            ->label(__('From'))
                            ->helperText(__('The phone number or alphanumeric sender ID to send messages from.'))
                            ->required(),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Paddle Settings'))
                        ->view('filament.admin.resources.verification-provider-resource.pages.partials.twilio-how-to'),
                ])->columnSpan([
                    'sm' => 6,
                    'xl' => 4,
                    '2xl' => 4,
                ]),
            ])->columns(12)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configManager->set('services.twilio.sid', $data['sid']);
        $this->configManager->set('services.twilio.token', $data['token']);
        $this->configManager->set('services.twilio.from', $data['from']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
