@extends('diamonds.layouts.subscriber-layout')

@section('title', 'Access Denied - ' . $content->title)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Content Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Access Denied</h1>
        
        <p class="text-lg text-gray-600 mb-6">{{ $message }}</p>
        
        <div class="space-y-4">
            <a href="{{ route('email-gated-content.show', [$channelname, $content->slug]) }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Try Again
            </a>
            
            @if($tenant->ytChannel)
                <div class="text-sm text-gray-500">
                    <p>Make sure you're subscribed to 
                        <strong>{{ $tenant->ytChannel->title ?? $channelname }}</strong>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 