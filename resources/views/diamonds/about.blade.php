<x-layouts.app class="relative overflow-hidden">
    <x-slot name="title">
        {{ __('About Us - YouTube Tracking') }}
    </x-slot>

    <div class="bg-primary-300  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -z-10 -right-28 md:-right-48 top-52 md:top-40 rotate-45">

    </div>
    <div class="bg-primary-300  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -z-10 -right-28 md:-right-56 top-64 md:top-10 rotate-45">

    </div>

    <x-section.hero class="w-full">
        <div class="mx-auto text-center px-4">
            <span class="text-primary-500 uppercase font-semibold">{{ __('About Us') }}</span>
            <x-heading.h1 class="mt-4 text-primary-800 font-bold flex flex-col items-center justify-center">
                <span>{{ __('Meet Our Team') }}</span>
            </x-heading.h1>
            <p class="m-3">{{ __('The experts behind the first platform that shows you which videos are driving leads and sales.') }}</p>
        </div>
    </x-section.hero>

    <!-- Team Section: Jack Born -->
    <x-section.columns class="max-w-none md:max-w-6xl mt-16">
        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h2 class="text-primary-900">
                    {{ __('Jack Born') }}
                </x-heading.h2>
                <x-heading.h6 class="text-primary-500 !uppercase mt-2">
                    {{ __('The SaaS Architect') }}
                </x-heading.h6>
            </div>

            <p class="mt-4">
                {{ __('Jack brings serious software credibility to this project. As the founder of Deadline Funnel (acquired in 2024) and multiple successful SaaS companies, he\'s built platforms that tens of thousands of entrepreneurs rely on daily.') }}
            </p>

            <p class="mt-4">
                {{ __('His technical innovations have powered marketing automation for businesses worldwide, and he even created the Tactical Triangle concept that marketing legend Perry Marshall featured in his bestselling book "80/20 Sales and Marketing."') }}
            </p>

            <p class="mt-4">
                {{ __('Jack\'s track record includes founding DeadlineFunnel.com, SurveyFunnel.io, BoxshotKing.com, and AW Pro Tools. When it comes to building software that actually works for real businesses, Jack\'s been there and done that.') }}
            </p>
        </x-section.column>

        <x-section.column>
            <img src="{{URL::asset('/images/about/Jack-at-beach-about-page-web.png')}}" class="rounded-2xl shadow-lg"/>
        </x-section.column>

    </x-section.columns>

    <!-- Team Section: Justin & Mike Brown -->
    <x-section.columns class="max-w-none md:max-w-6xl mt-16">
        <x-section.column>
            <img src="{{URL::asset('/images/about/JB_Mike_Background_Cafe2.jpg')}}" class="rounded-2xl shadow-lg" />
        </x-section.column>

        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h2 class="text-primary-900">
                    {{ __('Justin & Mike Brown') }}
                </x-heading.h2>
                <x-heading.h6 class="text-primary-500 !uppercase mt-2">
                    {{ __('The YouTube Growth Experts (aka "The Brown Brothers")') }}
                </x-heading.h6>
            </div>

            <p class="mt-4">
                {{ __('Together, Justin and Mike have built something remarkable: a seven-figure video marketing company with over 1.8 million YouTube subscribers and 800+ videos that prove their strategies work.') }}
            </p>

            <p class="mt-4">
                {{ __('Justin is the organic growth specialist who\'s cracked the code on YouTube\'s algorithm. He knows how to tap into the world\'s second-largest search engine to drive real business results, not just vanity metrics.') }}
            </p>

            <p class="mt-4">
                {{ __('Mike is the operations and business development mastermind who\'s been critical in scaling Primal Video behind the scenes. With his background in finance and strategic growth, he ensures everything runs smoothly while the business grows on autopilot.') }}
            </p>

            <p class="mt-4">
                {{ __('Together, they\'ve coached countless entrepreneurs to build recurring income models that grow their businesses while they sleep.') }}
            </p>
        </x-section.column>

    </x-section.columns>

    <!-- What We've Built For You Section -->
    <x-section.block class="mt-16 relative overflow-hidden bg-secondary-100/25">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <div class="text-center mb-12">
                <x-heading.h2 class="text-primary-900">
                    {{ __('What We\'ve Built For You') }}
                </x-heading.h2>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <p class="text-lg mb-6">
                    {{ __('We\'ve combined Jack\'s proven SaaS expertise with Justin and Mike\'s YouTube mastery to create something unique: the first platform that actually shows you which videos are driving leads and sales.') }}
                </p>
                
                <div class="mb-8">
                    <p class="mb-2 font-medium">{{ __('No more:') }}</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>{{ __('Complicated tech stacks') }}</li>
                        <li>{{ __('Complex funnel building') }}</li>
                        <li>{{ __('Confusing page design') }}</li>
                        <li>{{ __('Guessing which content converts') }}</li>
                    </ul>
                </div>
                
                <div class="mb-8">
                    <p class="mb-2 font-medium">{{ __('Instead, you get:') }}</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>{{ __('Crystal-clear revenue tracking from your YouTube content') }}</li>
                        <li>{{ __('Time-saving automation that works behind the scenes') }}</li>
                        <li>{{ __('Audience growth tools that expand your reach') }}</li>
                        <li>{{ __('Email list building that happens automatically') }}</li>
                        <li>{{ __('Real money in your bank account from videos that actually convert') }}</li>
                    </ul>
                </div>
                
                <p class="text-lg">
                    {{ __('Whether you\'re just starting out or you\'re already successful on YouTube, our platform makes it faster and easier than ever to turn your content into a real business.') }}
                </p>
            </div>
        </div>
    </x-section.block>
    
    <!-- Our Mission Section -->
    <x-section.block class="mt-16 bg-primary-950 relative overflow-hidden">

        <div class="bg-primary-50  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -right-24 md:-right-56 top-22 md:top-32 rotate-45">

        </div>
        <div class="bg-primary-50  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-24 md:-right-56 top-32 md:top-10 rotate-45">

        </div>

        <x-section.columns id="mission" class="mt-8">
            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-primary-200 !uppercase">
                        {{ __('Why We Do What We Do') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-white">
                        {{ __('Our Mission') }}
                    </x-heading.h2>
                </div>
                
                <p class="mt-6 text-primary-100">
                    {{ __('We believe every entrepreneur using YouTube deserves to see the real numbers that drive their business forward. Not just views and likes, but leads, sales, and revenue.') }}
                </p>
                
                <p class="mt-4 text-primary-100">
                    {{ __('That\'s why we\'ve taken our combined decades of experience in SaaS development and YouTube growth and distilled it into one powerful platform that finally gives you the visibility and tools you need to succeed.') }}
                </p>
                
                <p class="mt-4 text-primary-100">
                    {{ __('Because at the end of the day, YouTube isn\'t just about creating great content—it\'s about building a great business.') }}
                </p>

            </x-section.column>

            <x-section.column class="flex items-center justify-center">
                <img src="{{URL::asset('/images/about/JB_Transparent2.png')}}" class="h-auto w-60 md:w-80 relative hover:scale-105 transition-all duration-300" alt="Team Image" />
            </x-section.column>

        </x-section.columns>
        <x-section.columns class="max-w-none md:max-w-6xl mt-12  flex-wrap-reverse">
            <x-section.column class="flex items-center justify-center">
                @svg('diamonds/brush', 'h-48 w-48 md:h-60 md:w-60 text-primary-200 relative hover:scale-105 transition-all duration-300')
            </x-section.column>

            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-primary-200 !uppercase">
                        {{ __('Your Brand, Your Colors') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-white">
                        {{ __('Customize Everything.') }}
                    </x-heading.h2>
                </div>

                <div class="text-primary-50/75">
                    <p class="mt-4">
                        {{ __('Customize the primary & secondary colors of your website, error pages, email templates, fonts, social sharing cards, favicons, and more.') }}
                    </p>

                    <p class="mt-4">
                        {{ __('Based on the popular TailwindCSS, you can easily customize the look and feel of your SaaS application.') }}
                    </p>
                </div>
            </x-section.column>

        </x-section.columns>


    </x-section.block>

    

</x-layouts.app>
