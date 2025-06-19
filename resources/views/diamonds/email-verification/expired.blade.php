@extends('diamonds.layouts.public')

@section('title', 'Verification Link Expired')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-orange-50 to-red-100 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Error Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white p-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-3xl font-bold mb-2">⏰ Link Expired</h1>
                <p class="text-lg opacity-90">This verification link is no longer valid</p>
            </div>

            <!-- Main Content -->
            <div class="p-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    Oops! Something went wrong
                </h2>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-6 mb-8">
                    <div class="flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <p class="text-orange-800 font-medium mb-2">
                        {{ $message ?? 'This verification link has expired or is invalid.' }}
                    </p>
                    <p class="text-orange-700 text-sm">
                        Verification links expire after 2 hours for security reasons.
                    </p>
                </div>

                <!-- What you can do -->
                <div class="bg-blue-50 rounded-lg p-6 mb-8">
                    <h3 class="font-semibold text-blue-900 mb-4">💡 What you can do:</h3>
                    <div class="space-y-3 text-left">
                        <div class="flex items-center text-blue-800">
                            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                            </svg>
                            <span>Go back to the content page and request a new verification email</span>
                        </div>
                        <div class="flex items-center text-blue-800">
                            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            <span>Check your email for a more recent verification link</span>
                        </div>
                        <div class="flex items-center text-blue-800">
                            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            <span>Contact support if you continue to have issues</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <button onclick="history.back()" 
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-4 px-8 rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                        ← Go Back & Try Again
                    </button>
                    
                    <div class="flex space-x-3">
                        <a href="{{ route('home') }}" 
                           class="flex-1 bg-gray-200 text-gray-800 font-medium py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors">
                            Return Home
                        </a>
                        <a href="mailto:{{ config('mail.from.address') }}" 
                           class="flex-1 bg-gray-200 text-gray-800 font-medium py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors">
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Information -->
            <div class="px-8 py-6 bg-gray-50 border-t">
                <div class="text-center space-y-3">
                    <h4 class="font-semibold text-gray-900">🔒 Why do links expire?</h4>
                    <p class="text-sm text-gray-600">
                        For your security, verification links automatically expire after 2 hours. 
                        This prevents unauthorized access to your email accounts.
                    </p>
                    
                    <div class="flex items-center justify-center space-x-6 text-xs text-gray-500 mt-4">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Secure & Encrypted
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.414-1.414L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            2 Hour Validity
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            Need Help?
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Powered by {{ config('app.name') }} • Keeping your data safe and secure</p>
        </div>
    </div>
</div>

<!-- Auto-refresh warning -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Warn user if they try to refresh/reload this page
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'This link has already expired. Refreshing will not help. Go back to request a new verification email.';
    });
});
</script>
@endsection 