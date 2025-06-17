<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth h-full">
<!--
G'day!

You're probably here to figure out:

"Who are the smart, strong, and attractive creators of this beautiful marketing software?"

Well, colour us flattered!

Game recognizes game.

And I gotta say, you're OUR kind of marketer: Viewing the source code on cool tools is our jam too.

So my friend (I hope I can call you a friend...) I've got something special for you.

In Australia this is called "Mates Rates".

And during this "launch phase" our mates get a 10% discount.

You can head on over to https://videostats.ai/fairdinkum

But don't wait too long or this deal will be "cactus". (Some Aussie slang you can look up.)

We're doing it as a launch special "easter egg"... and you found it.

See you on the inside!

Oh, and keep this just between us mates... cool?

PS - if you're still keen to know a bit more about me and my co-founders, head on over to https://videostats.ai/about - but don't delay on that discount deal.

PPS - the one American among us pointed out that "colour" is spelled "color"... but we outnumber him 2 to 1 so we're sticking with "colour".
-->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.layouts.partials.head')
</head>
<body class="text-primary-900 flex flex-col min-h-screen"  x-data>
    <div id="app" class="flex flex-col flex-grow">
        <x-layouts.app.header class="flex-shrink-0"/>

        <div class="flex-grow">
            <div {{ $attributes->merge(['class' => 'mx-auto']) }}>
                {{ $slot }}
            </div>
        </div>

        <x-layouts.app.footer class="flex-shrink-0" />

        @include('components.layouts.partials.tail')
    </div>
    <x-impersonate::banner/>
</body>
</html>
