<x-filament::page x-data="{ activeTab: 'tab-0' }">
    <x-filament::tabs>
        @foreach ($this->getRegisteredMyProfileComponents() as $key => $component)
            @unless(is_null($component))
                @php
                    $tabId = 'tab-' . $loop->index;
                    $componentInstance = app($component);
                    $tabLabel = method_exists($componentInstance, 'getTabLabel') 
                        ? $componentInstance->getTabLabel() 
                        : ucwords(str_replace('_', ' ', $key));
                @endphp
                <x-filament::tabs.item 
                    x-on:click="activeTab = '{{ $tabId }}'"
                    :alpine-active="'activeTab === \'' . $tabId . '\''"
                >
                    {{ $tabLabel }}
                </x-filament::tabs.item>
            @endunless
        @endforeach
    </x-filament::tabs>

    <div class="mt-6">
        @foreach ($this->getRegisteredMyProfileComponents() as $key => $component)
            @unless(is_null($component))
                @php
                    $tabId = 'tab-' . $loop->index;
                @endphp
                <div x-show="activeTab === '{{ $tabId }}'">
                    @livewire($component)
                </div>
            @endunless
        @endforeach
    </div>
</x-filament::page> 