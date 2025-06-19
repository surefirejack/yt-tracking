<div class="bg-gray-50 min-h-screen">
    {{-- Header with preview notice --}}
    <div class="bg-blue-600 text-white p-3 text-center text-sm font-medium">
        📱 PREVIEW MODE - This is how visitors will see your email-gated content access form
    </div>

    {{-- Main content area --}}
    <div class="max-w-2xl mx-auto py-12 px-4">
        {{-- Content card --}}
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            {{-- Header section --}}
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-8 text-center">
                <h1 class="text-3xl font-bold mb-4">You're About to Get Access to</h1>
                <h2 class="text-2xl font-semibold text-blue-100">{{ $content->title }}</h2>
                
                @if($tenant->ytChannel?->banner_image_url)
                <div class="mt-6">
                    <img src="{{ $tenant->ytChannel->banner_image_url }}" 
                         alt="Channel Banner" 
                         class="w-full h-24 object-cover rounded-lg opacity-90">
                </div>
                @endif
            </div>

            {{-- Form section --}}
            <div class="p-8">
                {{-- Email input form --}}
                <div class="mb-8">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter your email to access this exclusive content:
                    </label>
                    <div class="flex gap-3">
                        <input type="email" 
                               id="email" 
                               placeholder="yourbestemail@gmail.com"
                               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               disabled>
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors"
                                disabled>
                            Get Access
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        We'll send you a quick verification email. One click and you're in! 
                    </p>
                </div>

                {{-- Subscription agreement --}}
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" 
                               checked 
                               disabled
                               class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div class="text-sm text-gray-700">
                            <span class="font-medium">Yes, I want to subscribe to {{ $tenant->ytChannel?->title ?? $tenant->name }}'s email list</span>
                            <p class="text-gray-500 mt-1">
                                By checking this box, you'll be added to our email list with the tag "{{ $tagName }}" 
                                and gain access to this exclusive content. You can unsubscribe anytime.
                            </p>
                        </div>
                    </label>
                </div>

                {{-- Preview of what they'll get --}}
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">What you'll get access to:</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 line-clamp-3">
                            {!! Str::limit(strip_tags($content->content), 200) !!}
                        </div>
                        @if(strlen(strip_tags($content->content)) > 200)
                            <p class="text-sm text-gray-500 mt-2 italic">...and more exclusive content</p>
                        @endif
                    </div>
                </div>

                {{-- Trust indicators --}}
                <div class="flex items-center justify-center gap-6 mt-8 pt-6 border-t text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Secure & encrypted
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                        Instant access
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        No spam, ever
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer info --}}
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>URL: <code class="bg-gray-100 px-2 py-1 rounded">{{ request()->getSchemeAndHttpHost() }}{{ $contentUrl }}</code></p>
            <p class="mt-2">Required ESP Tag: <span class="font-medium">{{ $tagName }}</span></p>
        </div>
    </div>

    {{-- Preview mode footer --}}
    <div class="bg-gray-800 text-white p-4 text-center text-sm">
        This is a preview of your email access form. Visitors will see this interface when they click your content link.
        <br>
        <span class="text-gray-300">Form inputs are disabled in preview mode.</span>
    </div>
</div> 