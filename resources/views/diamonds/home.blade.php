<x-layouts.app class="relative overflow-hidden">
    <x-slot name="title">
        {{ __('Video Bolt: The software YouTube experts use for maximum growth.') }}
    </x-slot>

    <div class="bg-primary-300  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -z-10 -right-28 md:-right-48 top-52 md:top-40 rotate-45">

    </div>
    <div class="bg-primary-300  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -z-10 -right-28 md:-right-56 top-64 md:top-10 rotate-45">

    </div>

    <x-section.hero class="w-full">

        <div class="mx-auto text-center px-4">
            <span class="text-primary-500 uppercase font-semibold">{{ __('If you have a YouTube channel... then this is for you:') }}</span>
            <x-heading.h1 class="mt-4 text-primary-800 font-bold flex flex-col items-center justify-center">
                <span class="flex flex-row items-center justify-center">
                    <span>
                        {{ __('The Ultimate YouTube Software') }}
                    </span>
                    
                </span>
                
                <span class="cd-headline slide is-full-width"><span>To Get You </span>
                    <span class="cd-words-wrapper">
                        <b class="is-visible">Click Stats</b>
                        <b>Lead Stats</b>
                        <b>Sales Stats</b>
                        <b>More Attention</b>
                        <b>More Leads</b>
                        <b>More Sales</b>
                        <b>More Views</b>
                        <b>More Clicks</b>
                        <b>More Subs</b>
                    </span>
            </x-heading.h1>
            <link rel="stylesheet" href="/css/jqueryAnimateText.css">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="/js/jqueryAnimateText.js"></script>

            <x-heading.h3 class="m-3">{{ __('You\'re about to unlock a new level of YouTube growth') }}</x-heading.h3>

            <div class="flex flex-wrap gap-4 justify-center md:flex-row mt-12">
                <x-button-link.primary href="#pricing" class="self-center !py-3" elementType="a">
                    {{ __('Start Your VideoBolt Test Drive') }}
                </x-button-link.primary>
                <x-button-link.secondary-outline href="//demo.saasykit.com" class=" self-center !py-3" rel=”nofollow” >
                    {{ __('Watch the Video') }}
                </x-button-link.secondary-outline>

            </div>

            <x-user-ratings link="#testimonials" class="items-center justify-center mt-6 relative z-40 p-4">
                <x-slot name="avatars">
                    <x-user-ratings.avatar src="/images/founders/primalvideo-logo-circle.png" alt="testimonial 1"/>
                    <x-user-ratings.avatar src="/images/founders/justin-cicle.png" alt="testimonial 2"/>
                    <x-user-ratings.avatar src="/images/founders/mike-circle.png" alt="testimonial 3"/>
                    <x-user-ratings.avatar src="/images/founders/jack-circle.png" alt="testimonial 4"/>
                </x-slot>

                {{ __('Co-founded by YouTube experts with over 12 years in business, 1.8 Million subscribers, and over 190 Million views.') }}
            </x-user-ratings>

            <div class="mx-auto md:max-w-3xl lg:max-w-5xl text-center p-4">
                <img class="drop-shadow-2xl mt-8 transition hover:scale-101 rounded-2xl" src="{{URL::asset('/images/diamonds/features/hero-image.png')}}" />
            </div>

        </div>
    </x-section.hero>

    <x-section.columns class="max-w-none md:max-w-6xl mt-16" >
        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500 !uppercase">
                    {{ __('No more guessing') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('Shows You Which Videos Get the Most Leads & Sales') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('Simple to start. Easy to understand.') }}
            </p>

            <p class="mt-4">
                {{ __('And crazy powerful for your business.') }}
            </p>

            <p class="mt-4">
                {{ __('Go beyond YouTube\'s views, likes, and subscribes metrics and get to the REAL metrics that matter: Clicks, Leads, Sales, Revenue.') }}
            </p>
        </x-section.column>

        <x-section.column>
            <img src="{{URL::asset('/images/screenshots/url-performance-close-2.png')}}"  class="relative z-10 hover:scale-105 transition-all duration-300" />
        </x-section.column>

    </x-section.columns>

    <div class="text-center pt-6 mx-auto max-w-5xl ">
        <img src="{{URL::asset('/images/screenshots/performance-detail.png')}}" >
    </div>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6 flex-wrap-reverse">
        <x-section.column >
            <img src="{{URL::asset('/images/screenshots/video-performance-close.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300" />
        </x-section.column>

        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500 !uppercase">
                    {{ __('Trends Become Obvious') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('Shows you video performance over time') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('No more digging through Google Analytics in search of the numbers you need.') }}
            </p>

            <p class="mt-4">
                {{ __('Now the critical business metrics you need are right at your fingertips.') }}
            </p>
        </x-section.column>

    </x-section.columns>

    <div class="text-center mt-24" x-intersect="$el.classList.add('slide-in-top')">
        
        <x-heading.h2 class="text-primary-900">
            {{ __('VideoBolt is newbie friendly') }}
            <br />
            {{ __('but it\'s also powerful enough for the pros') }}
        </x-heading.h2>
    </div>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6">
        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="beaker" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Split Testing') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Send visitors to 2 different pages to see which one performs best.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="utms" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('UTM Tracking') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Extra tracking power for your smart links.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="iphone" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Device Detection') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('You can send iOS and Android users to different pages.') }}</p>
        </x-section.column>

    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6">
        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="clock" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Time Expiring Links') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('You can set a time limit for your links to expire. Great for promotions.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="qr" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('QR Codes') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Create QR codes for your links.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="lock" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Password Protection') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Protect your links with a password.') }}</p>
        </x-section.column>

    </x-section.columns>



    


    <div class="text-center mt-24 px-4" x-intersect="$el.classList.add('slide-in-top')">
        <x-heading.h6 class="text-primary-500 !uppercase">
            {{ __('Plus...') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Grows Your Email List') }}
            <br />
            {{ __(' with Beautiful Download Pages') }}
        </x-heading.h2>
    </div>

    <p class="text-center py-4">{{ __('In just a few minutes you\'ll be up and running with a download area that turns your viewers into email subscribers') }}</p>

    <div class="text-center pt-6 mx-auto max-w-5xl ">
        <img src="{{URL::asset('/images/screenshots/yt-to-optin-4.png')}}" >
    </div>

    <x-section.block class="mt-12 bg-secondary-100/25 relative overflow-hidden">
        <div class="bg-secondary-900  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-5 z-0 -right-16 -bottom-16 md:-right-56 md:-bottom-10 rotate-45">

        </div>
        <div class="bg-secondary-900  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-16 -bottom-0 md:-right-56 md:-bottom-32 rotate-45">

        </div>

        <x-section.columns class="mt-8">
            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-secondary-700 !uppercase">
                        {{ __('Newbie Friendly') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-primary-950">
                        {{ __('Copy & Paste Easy to Use') }}
                    </x-heading.h2>
                </div>

                <div class="text-primary-950/75">
                    <p class="mt-4">
                        {{ __('If you can describe your download, you have all the skill you need to create a beautiful page for your subscribers.') }}
                    </p>
                </div>
            </x-section.column>

            <x-section.column>
                <img src="{{URL::asset('/images/screenshots/edit-page-3.png')}}" dir="right" class="relative z-10 hover:scale-105 transition-all duration-300">
            </x-section.column>

        </x-section.columns>
        <x-section.columns class="max-w-none md:max-w-6xl mt-12  flex-wrap-reverse">
            <x-section.column >
                <img src="{{URL::asset('/images/screenshots/customize-content-settings.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300" />
            </x-section.column>

            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-secondary-700 !uppercase">
                        {{ __('Your brand') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-primary-950">
                        {{ __('Customized to Your Brand') }}
                    </x-heading.h2>
                </div>

                <div class="text-primary-950/75">
                    <p class="mt-4">
                        {{ __('VideoBolt automatically pulls in your YouTube banner, logo, and colors to make your members area personalized to you in the blink of an eye.') }}
                    </p>

                   
                </div>
            </x-section.column>

        </x-section.columns>
    </x-section.block>

    <x-section.block class="mt-32 bg-primary-950 relative overflow-hidden">

        <div class="bg-primary-50  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -right-24 md:-right-56 top-22 md:top-32 rotate-45">

        </div>
        <div class="bg-primary-50  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-24 md:-right-56 top-32 md:top-10 rotate-45">

        </div>

        <x-section.columns id="features" class="mt-8">
            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-primary-200 !uppercase">
                        {{ __('Boosts your revenue') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-white">
                        {{ __('Resource page with affiliate links') }}
                    </x-heading.h2>
                </div>

                <div class="text-primary-50/75">
                    <p class="mt-4">
                        {{ __('Easily offer your customers subscription-based & one-time purchase products with SaaSykit. All the webhook handling, subscription management, and billing are already set up for you in a beautiful and easy-to-use admin panel.') }}
                    </p>
                    <p class="mt-4">
                        {{ __('Collect payments with Stripe and Paddle, and manage your customers with ease.') }}
                    </p>
                </div>
            </x-section.column>

            <x-section.column class="flex items-center justify-center">
                @svg('diamonds/money-bag', 'h-60 w-60 md:h-80 md:w-80 text-primary-200 relative hover:scale-105 transition-all duration-300')
            </x-section.column>

        </x-section.columns>
        


    </x-section.block>

    <div class="text-center mt-24 px-4" x-intersect="$el.classList.add('slide-in-top')">
        <x-heading.h6 class="text-primary-500 !uppercase">
            {{ __('Got Clients? We got you covered!') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('If You\'re an Agency You Can') }}
            <br />
            {{ __('Manage All Your Clients') }}
            <br />
            {{ __('With VideoBolt') }}
        </x-heading.h2>
    </div>

    <p class="text-center py-4">{{ __('We have plans that give you as many client workspaces as you want, and you can invite your team too.') }}</p>

    <x-section.block class="mt-12 relative overflow-hidden">

        <div class="bg-primary-300  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-24 md:-right-56 top-22 md:top-32 rotate-45">

        </div>
        <div class="bg-primary-300  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-24 md:-right-56 top-32 md:top-10 rotate-45">

        </div>

        <x-section.columns class="max-w-none md:max-w-6xl pt-8">
            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-primary-500 !uppercase">
                        {{ __('Agency Feature') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-primary-900">
                        {{ __('Multiple Client Workspaces') }}
                    </x-heading.h2>
                </div>

                <p class="mt-4">
                    {{ __('No matter how many clients you have, our plans and platform can give you a separate workspace for each client.') }}
                </p>
                
            </x-section.column>

            <x-section.column>
                <img src="{{URL::asset('/images/screenshots/tenant-closeup.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300"  />
            </x-section.column>

        </x-section.columns>

        <x-section.columns class="max-w-none md:max-w-6xl pt-8 flex-wrap-reverse">

            <x-section.column>
                <img src="{{URL::asset('/images/screenshots/invite-team-member.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300"  />
            </x-section.column>

            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-primary-500 !uppercase">
                        {{ __('Agency Feature') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-primary-900">
                        {{ __('Manage multiple team members') }}
                    </x-heading.h2>
                </div>

                <p class="mt-4">
                    {{ __('As your business grows, VideoBolt will grow with you.') }}
                </p>

                <p class="mt-4">
                    {{ __('You can invite team members and control their permissions. When a team members leaves you can easily shut off their access.') }}
                </p>

                <p class="pt-4">
                    {{ __('Supported login providers:') }}
                </p>
                <div class="flex gap-3 pt-1 flex-wrap">
                    @svg('diamonds/colored/google', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                    @svg('diamonds/colored/facebook', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                    @svg('diamonds/colored/twitter-oauth-2', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                    @svg('diamonds/colored/linkedin', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                    @svg('diamonds/colored/github', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                    @svg('diamonds/colored/gitlab', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                    @svg('diamonds/colored/bitbucket', 'h-12 w-12 py-2 px-2 border border-primary-50 rounded-lg')
                </div>
            </x-section.column>

        </x-section.columns>
    </x-section.block>


    


    <div class="mx-4 mt=16">
        <x-heading.h6 class="text-center mt-24 text-primary-500 !uppercase" id="pricing">
            {{ __('Choose Your Plan') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900 text-center">
            {{ __('Start Your 14 Day Free Trial') }}
        </x-heading.h2>
        <p class="text-center py-4">Pricing plan goes here</p>
    </div>

    <div class="pricing">
        <x-plans.all calculate-saving-rates="true" show-default-product="1"/>
        <x-products.all />
    </div>

    <div class="text-center mt-24 mx-4" id="faq">
        <x-heading.h6 class="text-primary-500">
            {{ __('FAQ') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Got a Question?') }}
        </x-heading.h2>
        <p>{{ __('Here are the most common questions to help you with your decision.') }}</p>
    </div>

    <div class="max-w-none md:max-w-6xl mx-auto">
        <x-accordion class="mt-4 p-8">
            <x-accordion.item active="true" name="faqs">
                <x-slot name="title">{{ __('What is VideoBolt?') }}</x-slot>

                <p>
                    {{ __('VideoBolt is software built to help you grow your YouTube channel. It\'s cofounded by YouTube experts Justin and Mike Brown of Primal Video who have 1.8 million subscribers and over 190 million views. VideoBolt gives you the information you need to know which videos are bringing you the most leads and sales. It also helps makes it easier than ever to deliver special downloads and bonus content so you can build a true asset in your business that builds trust and increases your sales.') }}
                </p>

            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('What features does VideoBolt offer?') }}</x-slot>

                <p class="mt-4">
                    {{ __('Here are some of the features included in VideoBolt in a nutshell:') }}
                </p>

                <ul class="mt-4 list-disc ms-4 ps-4">
                    <li><b>{{ __('Detailed Video Stats: ') }}</b>{{ __('Shows you key metrics for any video on your channel including leads, sales, revenue.') }}</li>
                    <li><b>{{ __('URL Traffic: ') }}</b>{{ __('For key pages like your email signup forms or checkout pages see side by side comparisons of your videos. Finaly see which videos are sending the most clicks, leads, and sales.') }}</li>
                    <li><b>{{ __('Time Saving Automation: ') }}</b>{{ __('Converts any links in your existing video descriptions over to VideoBolt smart links.') }}</li>
                    <li><b>{{ __('Subscriber\'s Only Content: ') }}</b>{{ __('Delivers downloads, free guides, checklists, blueprints and resources to your YouTube subscribers. Grows your email list at the same time.') }}</li>
                    <li><b>{{ __('Simple Conversion Setup: ') }}</b>{{ __('If you can copy and paste, you can start tracking your leads and sales.') }}</li>
                    <li><b>{{ __('Time Expiring Links: ') }}</b>{{ __('Great for season or one time promotions like Black Friday, Cyber Monday, or product launch announcements.') }}</li>
                    <li><b>{{ __('Resource Page: ') }}</b>{{ __('Quickly create a resource page and use affiliate links to generate passive revenue. No page building required!') }}</li>
                    <li><b>{{ __('Profile Page: ') }}</b>{{ __('Guides your YouTube subscribers to your other social media accounts, websites, products, and podcasts.') }}</li>

                    <li>{{ __('And much more...') }}</li>
                </ul>

            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('What if I have more than one channel?') }}</x-slot>

                <p>
                    {{ __('Depending on which plan you choose, we can give you extra workspaces where you can use VideoBolt on multiple YouTube channels.') }}
                </p>

            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('Do you offer support?') }}</x-slot>

                <p>
                    {{ __('Of course! We offer email support and we have extensive documentation. You can contact our support team from within the software.')}}
                </p>

            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{'Do you offer a trial?'}}</x-slot>

                <p>
                    {{ __('Yes! We have a 14 day free trial with all of our plans.')}}
                </p>

            </x-accordion.item>

            

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{'Do you offer refunds?'}}</x-slot>

                <p>
                    {{ __('Yes, we offer a 30-day money-back guarantee... on top of the 14 day free trial. Which means from the moment you start your trial you have 44 days to try VideoBolt, see how you like it, and make your decision.')}}
                </p>

            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{'What if I\'m not "tech savvy"?'}}</x-slot>

                <p>
                    {{ __('No worries at all! You\'re in good company because many of our clients and community members feel the same way. That\'s why we built VideoBolt to be super simple to use.')}}
                </p>
                <p>
                    {{ __('And to make sure you know it\'s going to fit your needs and be simple to use... we offer a 14 day free trial.')}}
                </p>

            </x-accordion.item>

           

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{'Is there documentation available?'}}</x-slot>

                <p>
                    {{ __('Yes, an extensive documentation is available to help you get started with VideoBolt. You can find the documentation ')}} <a href="{{ config('app.documentation.support') }}" target="_blank">here</a>.
                </p>

            </x-accordion.item>
        </x-accordion>


        <div class="text-center">
            <x-section.outro>
                <x-heading.h6 class="text-primary-50">
                    {{ __('Grow your YouTube channel faster than ever') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-50 drop-shadow-4xl">
                    {{ __('Level Up Your Channel Today') }}
                </x-heading.h2>

                <p class="text-primary-100 mt-2">
                    {{ __('VideoBolt is powerful enough for a YouTube channel with millions of subscribers') }}
                    <br />
                    {{ __(' and simple enough for even the most tech-phobic newbie to use.') }}
                </p>

                <div class="mt-12">
                    <x-button-link.secondary href="/" >
                        {{ __('Start Your Test Drive of VideoBolt Now') }}
                    </x-button-link.secondary>
                </div>
            </x-section.outro>
        </div>
    </div>

</x-layouts.app>
