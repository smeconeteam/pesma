<x-public-layout>
    <div class="mx-auto flex min-h-[70vh] max-w-7xl items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="w-full max-w-lg text-center">
            {{-- Animated Lock Icon --}}
            <div class="mx-auto mb-8 flex h-28 w-28 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-red-500 dark:text-red-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>

            {{-- Error Code --}}
            <h1 class="mb-2 text-8xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                <span class="bg-gradient-to-r from-red-500 to-orange-500 bg-clip-text text-transparent">403</span>
            </h1>

            {{-- Title --}}
            <h2 class="mb-3 text-2xl font-bold text-gray-800 dark:text-gray-200">
                {{ __('errors.403_title') }}
            </h2>

            {{-- Description --}}
            <p class="mb-8 text-gray-500 dark:text-gray-400">
                {{ __('errors.403_description') }}
            </p>

            {{-- Action Buttons --}}
            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ localizedRoute('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-green-700 hover:shadow-lg dark:bg-green-500 dark:hover:bg-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    {{ __('errors.back_home') }}
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        {{ __('errors.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>
