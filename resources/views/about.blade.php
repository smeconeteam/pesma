<x-public-layout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">
                {{ __('about.title') }}
            </h1>
        </div>

        <!-- Main Content -->
        <div class="mx-auto mt-4 max-w-3xl">
            @if ($institution && $institution->about_content)
                <div class="content-card">
                    <div class="rich-content">
                        {!! $institution->about_content !!}
                    </div>
                </div>
            @else
                <div class="content-card">
                    <div class="empty-state">
                        <svg class="h-16 w-16" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3>{{ __('about.content_unavailable_title') }}</h3>
                        <p>{{ __('about.content_unavailable_desc', ['name' => $institution->dormitory_name ?? config('app.name', 'Laravel')]) }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
