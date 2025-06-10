@php
    use Filament\Support\Enums\IconSize;
@endphp

<div class="flex items-center gap-2">
    @if($is_connected && $token_valid)
        <x-filament::badge 
            color="success" 
            icon="heroicon-m-check-circle"
            size="md"
        >
            Connected
        </x-filament::badge>
        
        <div class="text-sm text-gray-600 dark:text-gray-400">
            @if($email)
                <div><strong>Email:</strong> {{ $email }}</div>
            @endif
            @if(isset($nickname) && $nickname)
                <div><strong>Channel:</strong> {{ $nickname }}</div>
            @endif
            @if(isset($user_id) && $user_id)
                <div><strong>User ID:</strong> <code class="text-xs bg-gray-200 px-1 rounded">{{ $user_id }}</code></div>
            @endif
        </div>
    @elseif($is_connected && !$token_valid)
        <x-filament::badge 
            color="warning" 
            icon="heroicon-m-exclamation-triangle"
            size="md"
        >
            Connection Expired
        </x-filament::badge>
        
        <div class="text-sm text-orange-600 dark:text-orange-400">
            <div>Your YouTube connection has expired.</div>
            <div>Please disconnect and reconnect your account.</div>
            @if($email)
                <div class="mt-2"><strong>Account:</strong> {{ $email }}</div>
            @endif
        </div>
    @else
        <x-filament::badge 
            color="gray" 
            icon="heroicon-m-x-circle"
            size="md"
        >
            Not Connected
        </x-filament::badge>
    @endif
</div> 