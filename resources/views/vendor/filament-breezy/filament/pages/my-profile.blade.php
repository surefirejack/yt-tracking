<x-filament::page>
    <x-filament::tabs>
        @foreach ($this->getRegisteredMyProfileComponents() as $key => $component)
            @unless(is_null($component))
                @php
                    $componentInstance = app($component);
                    $tabLabel = method_exists($componentInstance, 'getTabLabel') 
                        ? $componentInstance->getTabLabel() 
                        : ucwords(str_replace('_', ' ', $key));
                @endphp
                <x-filament::tabs.item :id="$key" :label="$tabLabel">
                    @livewire($component)
                </x-filament::tabs.item>
            @endunless
        @endforeach
    </x-filament::tabs>
</x-filament::page>
