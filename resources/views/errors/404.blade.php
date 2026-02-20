<x-public-layout>
    <div class="mx-auto flex min-h-[70vh] max-w-7xl items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="w-full max-w-lg text-center">
            {{-- Animated Search Icon --}}
            <div class="mx-auto mb-8 flex h-28 w-28 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-amber-500 dark:text-amber-400 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="animation-duration: 2s;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                </svg>
            </div>

            {{-- Error Code --}}
            <h1 class="mb-2 text-8xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                <span class="bg-gradient-to-r from-amber-500 to-yellow-500 bg-clip-text text-transparent">404</span>
            </h1>

            {{-- Title --}}
            <h2 class="mb-3 text-2xl font-bold text-gray-800 dark:text-gray-200">
                {{ __('errors.404_title') }}
            </h2>

            {{-- Description --}}
            <p class="mb-8 text-gray-500 dark:text-gray-400">
                {{ __('errors.404_description') }}
            </p>

            {{-- Action Buttons --}}
            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ localizedRoute('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-green-700 hover:shadow-lg dark:bg-green-500 dark:hover:bg-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    {{ __('errors.back_home') }}
                </a>
                <a href="{{ localizedRoute('rooms.available') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    {{ __('errors.browse_rooms') }}
                </a>
            </div>

            {{-- Subtle Hint --}}
            <p class="mt-8 text-xs text-gray-400 dark:text-gray-500">
                {{ __('errors.url_hint') }}
            </p>
        </div>
    </div>
</x-public-layout>
