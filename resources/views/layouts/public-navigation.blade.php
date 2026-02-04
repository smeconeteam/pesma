<nav x-data="{
    open: false,
    darkMode: localStorage.getItem('darkMode') === 'true',
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        document.documentElement.classList.toggle('dark', this.darkMode);
    }
}" x-init="if (darkMode) document.documentElement.classList.add('dark');
$watch('darkMode', val => document.documentElement.classList.toggle('dark', val));" class="sticky left-0 top-0 w-full border-b border-gray-200 bg-white transition-all duration-200 dark:border-gray-700 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Logo -->
            <div class="flex shrink-0 items-center">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    @if ($institution?->logo_path)
                        <img src="{{ Storage::url($institution->logo_path) }}" alt="Logo {{ $institution->dormitory_name }}" class="h-10 w-10 object-contain">
                    @else
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    @endif

                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $institution?->dormitory_name ?? config('app.name') }}
                    </h1>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <div class="hidden space-x-8 sm:flex">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'border-green-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:border-gray-300 dark:hover:border-gray-600' }} inline-flex items-center border-b-2 px-1 pt-1 transition-colors">
                    {{ __('public.home') }}
                </a>
                <a href="{{ route('rooms.available') }}" class="{{ request()->routeIs('rooms.available') || request()->routeIs('rooms.show') ? 'border-green-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:border-gray-300 dark:hover:border-gray-600' }} inline-flex items-center border-b-2 px-1 pt-1 transition-colors">
                    {{ __('public.available_rooms') }}
                </a>
            </div>

            <!-- Desktop Right Side -->
            <div class="hidden gap-4 sm:flex sm:items-center">
                <x-locale-switcher :short="true" class="cursor-pointer" select-class="cursor-pointer text-sm rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />

                @auth
                    @if (Route::has('dashboard'))
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                            {{ __('navigation.dashboard') }}
                        </a>
                    @endif
                @endauth

                @guest
                    <div class="flex items-center gap-3">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex w-full rounded-md ring-1 ring-green-600 ring-inset bg-gray-100 px-3 py-2 text-center text-sm font-medium text-green-600 transition-colors hover:bg-green-600 hover:text-white">
                                {{ __('navigation.login') }}
                            </a>
                        @endif

                        @if (Route::has('public.registration.create'))
                            <a href="{{ route('public.registration.create') }}" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                                {{ __('navigation.register') }}
                            </a>
                        @endif
                    </div>
                @endguest
            </div>

            <!-- Mobile Menu Button -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-800">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div :class="{ 'h-auto block': open, 'hidden h-0': !open }" class="hidden h-0 bg-white transition-all duration-200 sm:hidden dark:bg-gray-900">
        <div class="space-y-1 px-2 pb-3 pt-2">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }} block rounded-md px-3 py-2 text-base font-medium transition-colors">
                {{ __('public.home') }}
            </a>
            <a href="{{ route('rooms.available') }}" class="{{ request()->routeIs('rooms.available') || request()->routeIs('rooms.show') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }} block rounded-md px-3 py-2 text-base font-medium transition-colors">
                {{ __('public.available_rooms') }}
            </a>

            <div class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                @auth
                    @if (Route::has('dashboard'))
                        <a href="{{ route('dashboard') }}" class="mb-2 block rounded-md bg-green-600 px-3 py-2 text-base font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                            {{ __('navigation.dashboard') }}
                        </a>
                    @endif
                @endauth

                @guest
                    <div class="flex w-full items-center justify-between gap-2">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="block w-full rounded-md ring ring-green-600 ring-inset bg-gray-100 px-3 py-2 text-center text-base font-medium text-green-600 transition-colors hover:bg-green-600 hover:text-white">
                                {{ __('navigation.login') }}
                            </a>
                        @endif

                        @if (Route::has('public.registration.create'))
                            <a href="{{ route('public.registration.create') }}" class="block w-full rounded-md bg-green-600 px-3 py-2 text-center text-base font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                                {{ __('navigation.register') }}
                            </a>
                        @endif
                    </div>
                @endguest
            </div>

            <!-- Language Switcher Mobile -->
            <div class="mt-2 border-t border-gray-200 px-3 py-2 dark:border-gray-700">
                <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('navigation.language') ?? 'Language' }}
                </label>
                <x-locale-switcher :short="false" select-class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />
            </div>
        </div>
    </div>
</nav>
