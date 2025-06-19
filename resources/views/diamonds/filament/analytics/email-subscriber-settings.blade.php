<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Page Introduction -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.92a1.5 1.5 0 01-.9 1.382l-8.1 3.6a1.5 1.5 0 01-1.2 0l-8.1-3.6a1.5 1.5 0 01-.9-1.382V6.75" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-blue-900">
                        Email Subscriber Settings
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Configure your email service provider integration and manage how email verification works for your gated content. These settings control how subscribers are added to your email list and how long they maintain access to content.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="bg-white rounded-lg shadow">
            <form wire:submit="save" class="space-y-6 p-6">
                {{ $this->form }}
                
                <div class="flex justify-end pt-6 border-t border-gray-200">
                    <x-filament::button type="submit" size="lg">
                        <x-heroicon-m-check class="w-5 h-5 mr-2"/>
                        Save Settings
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Quick Links -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Quick Links</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ \App\Filament\Dashboard\Resources\EmailSubscriberContentResource::getUrl('index') }}" 
                   class="flex items-center p-4 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors">
                    <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-4.5V6.375a3.375 3.375 0 00-3.375-3.375H7.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 10.5h6.75M10.5 15h3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 6.375a3.375 3.375 0 013.375-3.375h4.5a2.25 2.25 0 011.414.586l1.586 1.586A2.25 2.25 0 0116.5 6.375v11.25a2.25 2.25 0 01-2.25 2.25h-8.5a2.25 2.25 0 01-2.25-2.25V6.375z" />
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">Manage Content</div>
                        <div class="text-sm text-gray-500">Create and edit email-gated content</div>
                    </div>
                </a>
                
                <a href="{{ \App\Filament\Dashboard\Resources\EmailSubscriberContentResource::getUrl('analytics') }}" 
                   class="flex items-center p-4 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors">
                    <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">View Analytics</div>
                        <div class="text-sm text-gray-500">Track conversion metrics and performance</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-amber-900">
                        Need Help?
                    </h3>
                    <div class="mt-2 text-sm text-amber-700 space-y-2">
                        <p><strong>Kit/ConvertKit Integration:</strong> You'll need your Kit API key from your account settings.</p>
                        <p><strong>Cookie Duration:</strong> Determines how long subscribers stay logged in after verification.</p>
                        <p><strong>Content Gating:</strong> Create content with specific tags to control subscriber access.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page> 