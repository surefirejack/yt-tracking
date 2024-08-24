@if (count($groupedPlans) == 0)
    <x-section.columns class="max-w-none md:max-w-6xl mt-6 justify-center">
    @foreach($plans as $plan)
        <x-section.column class="md:!basis-1/3 !px-4">
            <x-plans.one :popular="$plan->product->is_popular" link="{{route('checkout.subscription', $plan->slug)}}">
                <x-slot name="name">{{ $plan->product->name }}</x-slot>
                <x-slot name="price">@money($plan->prices[0]->price, $plan->prices[0]->currency->code)</x-slot>
                <x-slot name="interval">
                    @if($plan->type === \App\Constants\PlanType::SEAT_BASED->value)
                        <span class="text-sm">{{__('per seat')}}</span>
                    @endif
                    / {{$plan->interval_count > 1 ? $plan->interval_count : '' }} {{ __($plan->interval->name) }}
                </x-slot>
                <x-slot name="description">
                    <ul class="flex flex-col items-center gap-4">
                        @if($plan->product->features)
                            @foreach($plan->product->features as $feature)
                                <x-features.li-item>{{$feature['feature']}}</x-features.li-item>
                            @endforeach
                        @endif
                    </ul>
                </x-slot>
            </x-plans.one>
        </x-section.column>
    @endforeach
    </x-section.columns>
@else
    <x-tab-slider class="mt-6 md:max-w-6xl">
        <x-slot name="tabNames">
            @php
                if (empty($preselectedInterval)) {
                    $preselectedInterval = array_keys($groupedPlans)[0] ?? null;
                }
            @endphp
            @foreach($groupedPlans as $interval => $intervalPlans)
                <x-tab-slider.tab-name controls="pricing-{{$interval}}" active="{{ $preselectedInterval == $interval ? 'true' : 'false' }}">

                    {{ ucfirst(__($intervalPlans[0]?->interval?->adverb)) }}

                    @isset($intervalSavingPercentage[$interval])
                        <x-pill class="text-primary-500 bg-primary-50 ml-">{{ __('Save ') . $intervalSavingPercentage[$interval] }} %</x-pill>
                    @endisset
                </x-tab-slider.tab-name>

            @endforeach
        </x-slot>

        @foreach($groupedPlans as $interval => $plans)
            <x-tab-slider.tab-content id="pricing-{{$interval}}">
                <x-section.columns class="max-w-none md:max-w-6xl mt-6 justify-center">

                    @foreach($plans as $plan)
                        <x-section.column class="md:!basis-1/3 !px-4">
                            <x-plans.one :popular="$plan->product->is_popular" link="{{route('checkout.subscription', $plan->slug)}}">
                                <x-slot name="name">{{ $plan->product->name }}</x-slot>
                                <x-slot name="price">@money($plan->prices[0]->price, $plan->prices[0]->currency->code)</x-slot>
                                <x-slot name="interval">
                                    @if($plan->type === \App\Constants\PlanType::SEAT_BASED->value)
                                        <span class="text-sm">{{__('Per seat')}}</span>
                                    @endif
                                    / {{$plan->interval_count > 1 ? $plan->interval_count : '' }} {{ __($plan->interval->name) }}
                                </x-slot>
                                <x-slot name="description">
                                    <ul class="flex flex-col items-center gap-4">
                                        @if($plan->product->features)
                                            @foreach($plan->product->features as $feature)
                                                <x-features.li-item>{{$feature['feature']}}</x-features.li-item>
                                            @endforeach
                                        @endif
                                    </ul>
                                </x-slot>
                            </x-plans.one>
                        </x-section.column>
                    @endforeach
                </x-section.columns>
            </x-tab-slider.tab-content>
        @endforeach

    </x-tab-slider>
@endif
