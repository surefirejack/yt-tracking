<x-filament-breezy::grid-section md=2 :title="__('filament-breezy::default.profile.browser_sessions.heading')" :description="__('filament-breezy::default.profile.browser_sessions.subheading')">
    <x-filament::card>
        <x-filament-panels::form>
            {{ $this->form }}
        </x-filament-panels::form>

        <x-filament-actions::modals />
    </x-filament::card>
</x-filament-breezy::grid-section>
