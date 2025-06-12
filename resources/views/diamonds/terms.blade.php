<x-layouts.simple>
<style>
    h1 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    h2 {
        font-size: 1.5rem;  
        font-weight: 600;
        margin-bottom: 1rem;
        margin-top: 1.5rem;
    }

    h3 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1rem;
        margin-top: 1.5rem;
    }

    p {
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }

    li p {
        margin-top: 1.5rem;
    }

    ol {
        margin-bottom: 1.5rem;
    }
</style>
    <div class="bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center">
            

            <div class="w-full sm:max-w-2xl p-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg prose dark:prose-invert">
                {!! $terms !!}
            </div>
        </div>
    </div>
</x-layouts.simple> 