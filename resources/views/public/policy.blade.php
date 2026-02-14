<x-public-layout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">
                {{ $policy->title ?? __('public.policy.title') }}
            </h1>
            @if($policy->updated_at)
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('public.policy.updated_at', ['date' => $policy->updated_at instanceof \Carbon\Carbon ? $policy->updated_at->translatedFormat('d F Y') : $policy->updated_at]) }}
                    </span>
                </p>
            @endif
        </div>

        <!-- Back Button -->
        <div class="mx-auto max-w-3xl mb-6">
            <button onclick="if(window.opener) { window.close(); } else if(history.length > 1) { history.back(); } else { window.location.href='{{ url('/') }}'; }"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 hover:shadow-md dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('public.policy.back') }}
            </button>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-3xl">
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <div class="prose prose-green max-w-none dark:prose-invert prose-h1:border-b-2 prose-h1:border-green-100 prose-h1:pb-2 dark:prose-h1:border-green-800 prose-blockquote:bg-green-50 prose-blockquote:py-4 prose-blockquote:px-5 prose-blockquote:border-l-4 prose-blockquote:border-green-500 prose-blockquote:rounded-r-lg prose-blockquote:text-green-800 dark:prose-blockquote:bg-green-900/20 dark:prose-blockquote:text-green-300 prose-img:rounded-xl">
                    {!! $policy->content !!}
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-6 flex items-center justify-center gap-2 text-center">
                <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('public.policy.footer_note') }}
                </span>
            </div>
        </div>
    </div>


</x-public-layout>
