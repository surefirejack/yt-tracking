<div>
    <form action="" method="post" wire:submit="checkout">
        @csrf

        <x-section.columns class="max-w-none md:max-w-6xl flex-wrap-reverse">
            <x-section.column>
                @include('livewire.checkout.partials.login-or-register')
                @include('livewire.checkout.partials.payment')
            </x-section.column>
        </x-section.columns>

        <p class="text-xs text-neutral-600 p-4">
            {{ __('By continuing, you agree to our') }} <a target="_blank" href="{{route('terms-of-service')}}" class="text-primary-900 underline">{{ __('Terms of Service') }}</a> {{ __('and') }} <a target="_blank" href="{{route('privacy-policy')}}" class="text-primary-900 underline">{{ __('Privacy Policy') }}</a>.
        </p>

        <x-button-link.primary class="flex flex-row items-center justify-center gap-3  !w-full my-4 disabled:opacity-40" elementType="button" type="submit" wire:loading.attr="disabled">
            {{ __('Confirm & Pay') }}
            <div wire:loading class="max-w-fit max-h-fit">
                <span class="loading loading-ring loading-xs"></span>
            </div>
        </x-button-link.primary>
    </form>


</div>
