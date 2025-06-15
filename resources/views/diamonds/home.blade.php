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
                
                <span class="cd-headline slide is-full-width"><span>To Give You </span>
                    <span class="cd-words-wrapper">
                        <b class="is-visible">Click Tracking</b>
                        <b>Leads Tracking</b>
                        <b>Sales Tracking</b>
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

            <x-heading.h3 class="m-3">{{ __('Your future YouTube success begins right now.') }}</x-heading.h3>

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
                    {{ __('See Which Videos Get You the Most Leads & Sales') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('Create and manage your products, plans, and pricing, set features for each plan, mark a plan as featured, and more.') }}
            </p>

            <p class="mt-4">
                {{ __('Rewards your customers with discounts and manage all that from a beautiful admin panel.') }}
            </p>
        </x-section.column>

        <x-section.column>
            <img src="{{URL::asset('/images/screenshots/url-performance.png')}}"  class="relative z-10 hover:scale-105 transition-all duration-300" />
        </x-section.column>

    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6 flex-wrap-reverse">
        <x-section.column >
            <img src="{{URL::asset('/images/diamonds/features/checkout.png')}}" class="rounded-2xl" />
        </x-section.column>

        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500 !uppercase">
                    {{ __('Buttery smooth') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('Beautiful checkout process.') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('In a few clicks, your customers can subscribe to your service using a beautiful checkout page that shows all the details of the plan they are subscribing to, allowing them to add a coupon code if they have one, and choose their payment method.') }}
            </p>
        </x-section.column>

    </x-section.columns>

    <x-section.block class="mt-32 bg-primary-950 relative overflow-hidden">

        <div class="bg-primary-50  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 -right-24 md:-right-56 top-22 md:top-32 rotate-45">

        </div>
        <div class="bg-primary-50  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-24 md:-right-56 top-32 md:top-10 rotate-45">

        </div>

        <x-section.columns id="features" class="mt-8">
            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-primary-200 !uppercase">
                        {{ __('a solid SaaS') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-white">
                        {{ __('Subscriptions & One-time purchases.') }}
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

    <div class="text-center mt-24 mx-4" id="tech-stack">
        <x-heading.h6 class="text-primary-500 !uppercase">
            {{ __('The best of the best') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('A solid tech stack') }}
        </x-heading.h2>
    </div>


    <div class="text-center p-4 mx-auto">
        <p >{{ __('Laravel, TailwindCSS, Livewire, AlpineJS & FilamentPhp') }}</p>

        <div class="flex flex-wrap items-center justify-center gap-12 mt-8">
            <img src="{{URL::asset('/images/diamonds/tech-stack/laravel.svg')}}" class="h-6 md:h-8 hover:cursor-pointer hover:scale-103 hover:opacity-100 transition grayscale hover:grayscale-0 opacity-50" />
            <img src="{{URL::asset('/images/diamonds/tech-stack/filament.avif')}}" class="h-6 md:h-8 hover:cursor-pointer hover:scale-103 hover:opacity-100 transition grayscale hover:grayscale-0 opacity-50" />
            <img src="{{URL::asset('/images/diamonds/tech-stack/tailwindcss.svg')}}" class="h-6 md:h-8 hover:cursor-pointer hover:scale-103 hover:opacity-100 transition grayscale hover:grayscale-0 opacity-50" />
            <img src="{{URL::asset('/images/diamonds/tech-stack/livewire.png')}}" class="h-12 md:h-16 hover:cursor-pointer hover:scale-103 hover:opacity-100 transition grayscale hover:grayscale-0 opacity-50" />
            <img src="{{URL::asset('/images/diamonds/tech-stack/alpinejs.svg')}}" class="h-8 md:h-10 hover:cursor-pointer hover:scale-103 hover:opacity-100 transition grayscale hover:grayscale-0 opacity-50" />
        </div>

    </div>

    <x-section.block class="mt-32 bg-secondary-100/25 relative overflow-hidden">
        <div class="bg-secondary-900  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-5 z-0 -right-16 -bottom-16 md:-right-56 md:-bottom-10 rotate-45">

        </div>
        <div class="bg-secondary-900  w-40 h-40 md:w-96 md:h-96 rounded-3xl absolute opacity-10 z-0 -right-16 -bottom-0 md:-right-56 md:-bottom-32 rotate-45">

        </div>

        <x-section.columns class="mt-8">
            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-secondary-700 !uppercase">
                        {{ __('SaaS Stats') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-primary-950">
                        {{ __('Know your numbers') }}
                    </x-heading.h2>
                </div>

                <div class="text-primary-950/75">
                    <p class="mt-4">
                        {{ __('View your MRR (monthly recurring revenue), Churn rates, ARPU (average revenue per user), and other SaaS metrics right inside your admin panel.') }}
                    </p>
                </div>
            </x-section.column>

            <x-section.column>
                <img src="{{URL::asset('/images/diamonds/features/stats.png')}}" dir="right" class="relative z-10 hover:scale-105 transition-all duration-300">
            </x-section.column>

        </x-section.columns>
        <x-section.columns class="max-w-none md:max-w-6xl mt-12  flex-wrap-reverse">
            <x-section.column >
                <img src="{{URL::asset('/images/diamonds/features/blog.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300" />
            </x-section.column>

            <x-section.column>
                <div x-intersect="$el.classList.add('slide-in-top')">
                    <x-heading.h6 class="text-secondary-700 !uppercase">
                        {{ __('Content is king') }}
                    </x-heading.h6>
                    <x-heading.h2 class="text-primary-950">
                        {{ __('A ready Blog.') }}
                    </x-heading.h2>
                </div>

                <div class="text-primary-950/75">
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


    <div class="text-center mt-24 px-4" x-intersect="$el.classList.add('slide-in-top')">
        <x-heading.h6 class="text-primary-500 !uppercase">
            {{ __('Can\'t Get Any Easier') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('A stunning subscriber\'s Only Area') }}
            <br />
            {{ __(' for Content and Downloads') }}
        </x-heading.h2>
    </div>

    <p class="text-center py-4">{{ __('In just a few minutes you\'ll be up and running with a download area that turns your viewers into email subscribers') }}</p>

    <div class="text-center pt-6 mx-auto max-w-5xl ">
        <img src="{{URL::asset('/images/screenshots/members-area-3-shots.png')}}" >
    </div>

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
                <img src="{{URL::asset('/images/diamonds/features/email.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300"  />
            </x-section.column>

        </x-section.columns>

        <x-section.columns class="max-w-none md:max-w-6xl pt-8 flex-wrap-reverse">

            <x-section.column>
                <img src="{{URL::asset('/images/diamonds/features/login.png')}}" class="relative z-10 hover:scale-105 transition-all duration-300"  />
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
                    {{ __('SaaSykit includes built-in user authentication, supporting both traditional email/password authentication and social login options such as Google, Facebook, Twitter, Github, LinkedIn, and more.') }}
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


    <div class="text-center mt-24" x-intersect="$el.classList.add('slide-in-top')">
        <x-heading.h6 class="text-primary-500 !uppercase">
            {{ __('Oh, we\'re not done yet') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('And a whole lot more') }}
        </x-heading.h2>
    </div>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6">
        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="users" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Users & Roles') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Manage your users, create roles and assign permissions to your users.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="translatable" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Fully translatable') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Translate your application to any language you want.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="seo" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Sitemap & SEO') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Auto-generated sitemap and SEO optimization out of the box.') }}</p>
        </x-section.column>

    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6">
        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="user-dashboard" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('User Dashboard') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Users can manage their subscriptions, change payment method, upgrade plan, cancel subscription alone.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="tool" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Highly customizable') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Manage your SaaS settings from within the admin panel. No need to redeploy app for simple changes anymore.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="development" class="w-1/4 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Developer-friendly') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Built with developers in mind, uses best coding practices. Offers handlers & events and automated tests covering critical components of the application.') }}</p>
        </x-section.column>

    </x-section.columns>


    <div class="mx-4 mt=16">
        <x-heading.h6 class="text-center mt-24 text-primary-500 !uppercase" id="pricing">
            {{ __('Launch your SaaS Today') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900 text-center">
            {{ __('Ship Your SaaS in Days') }}
        </x-heading.h2>
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
