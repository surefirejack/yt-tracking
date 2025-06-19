<x-filament-panels::page>
    <!-- Page Introduction -->
    <x-filament::section>
        <x-slot name="heading">
            Email Subscriber Settings
        </x-slot>

        <x-slot name="description">
            Configure your email service provider integration and manage how email verification works for your gated content. These settings control how subscribers are added to your email list and how long they maintain access to content.
        </x-slot>

        <x-slot name="headerActions">
            <x-filament::icon-button
                icon="heroicon-m-envelope"
                color="primary"
                size="lg"
            />
        </x-slot>
    </x-filament::section>

    <!-- Settings Form -->
    <form wire:submit="save">
        <x-filament::section>
            {{ $this->form }}
            
            <x-slot name="footerActions">
                <x-filament::button type="submit">
                    <x-filament::icon icon="heroicon-m-check" class="w-4 h-4 mr-2" />
                    Save Settings
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    </form>

    <!-- Quick Links -->
    <x-filament::section>
        <x-slot name="heading">
            Quick Links
        </x-slot>

        <x-slot name="description">
            Navigate to related email subscriber content management areas.
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::link
                :href="\App\Filament\Dashboard\Resources\EmailSubscriberContentResource::getUrl('index')"
                class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex items-center p-6">
                    <x-filament::icon-button
                        icon="heroicon-o-document-text"
                        color="primary"
                        size="xl"
                        class="mr-4"
                    />
                    <div>
                        <div class="font-medium text-gray-950 dark:text-white">Manage Content</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Create and edit email-gated content</div>
                    </div>
                </div>
            </x-filament::link>
            
            <x-filament::link
                :href="\App\Filament\Dashboard\Resources\EmailSubscriberContentResource::getUrl('analytics')"
                class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex items-center p-6">
                    <x-filament::icon-button
                        icon="heroicon-o-chart-bar"
                        color="primary"
                        size="xl"
                        class="mr-4"
                    />
                    <div>
                        <div class="font-medium text-gray-950 dark:text-white">View Analytics</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Track conversion metrics and performance</div>
                    </div>
                </div>
            </x-filament::link>
        </div>
    </x-filament::section>

    <!-- Help Section -->
    <x-filament::section>
        <x-slot name="heading">
            <x-filament::icon icon="heroicon-m-question-mark-circle" class="w-5 h-5 mr-2" />
            Need Help?
        </x-slot>

        <x-slot name="description">
            Common setup information and configuration tips.
        </x-slot>

        <div class="space-y-4">
            <div class="border-l-4 border-primary-500 pl-4">
                <div class="font-medium text-gray-950 dark:text-white">Kit/ConvertKit Integration</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">You'll need both your Kit API Key and API Secret from Account Settings > General to enable email list integration.</div>
            </div>

            <div class="border-l-4 border-primary-500 pl-4">
                <div class="font-medium text-gray-950 dark:text-white">Cookie Duration</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Determines how long subscribers stay logged in after email verification.</div>
            </div>

            <div class="border-l-4 border-primary-500 pl-4">
                <div class="font-medium text-gray-950 dark:text-white">Content Gating</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Create content with specific tags to control which subscribers can access it.</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page> 