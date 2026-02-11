<nav x-data="{
    open: false,
    darkMode: localStorage.getItem('darkMode') === 'true',
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        document.documentElement.classList.toggle('dark', this.darkMode);
    }
}" x-init="if (darkMode) document.documentElement.classList.add('dark');
$watch('darkMode', val => document.documentElement.classList.toggle('dark', val));" class="border-b border-gray-200 bg-white transition-colors duration-200 dark:border-gray-700 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <!-- Logo -->
            <div class="flex shrink-0 items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
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

            <!-- Desktop Navigation Links (Dashboard Only) -->
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('navigation.dashboard') }}
                </x-nav-link>

                @if (Route::has('resident.my-room'))
                    <x-nav-link :href="route('resident.my-room')" :active="request()->routeIs('resident.my-room')">
                        {{ __('navigation.my_room') }}
                    </x-nav-link>
                @endif

                @if (Route::has('resident.room-history'))
                    <x-nav-link :href="route('resident.room-history')" :active="request()->routeIs('resident.room-history')">
                        {{ __('navigation.room_history') }}
                    </x-nav-link>
                @endif

                @if (Route::has('resident.bills'))
                    <x-nav-link :href="route('resident.bills')" :active="request()->routeIs('resident.bills')">
                        {{ __('navigation.bills') }}
                    </x-nav-link>
                @endif

                @if (Route::has('resident.payment-history'))
                    <x-nav-link :href="route('resident.payment-history')" :active="request()->routeIs('resident.payment-history')">
                        {{ __('navigation.payment_history') }}
                    </x-nav-link>
                @endif
            </div>

            <!-- Desktop Right Side -->
            <div class="hidden gap-4 sm:ms-6 sm:flex sm:items-center">
                <x-locale-switcher :short="true" />

                @if (Route::has('profile.edit'))
                    <x-profile-dropdown :user="auth()->user()" @toggle-theme="toggleTheme()" />
                @endif
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
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden bg-white sm:hidden dark:bg-gray-900">
        <div class="space-y-1 pb-3 pt-2">
            @if (Route::has('dashboard'))
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('navigation.dashboard') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.my-room'))
                <x-responsive-nav-link :href="route('resident.my-room')" :active="request()->routeIs('resident.my-room')">
                    {{ __('navigation.my_room') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.room-history'))
                <x-responsive-nav-link :href="route('resident.room-history')" :active="request()->routeIs('resident.room-history')">
                    {{ __('navigation.room_history') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.bills'))
                <x-responsive-nav-link :href="route('resident.bills')" :active="request()->routeIs('resident.bills')">
                    {{ __('navigation.bills') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.payment-history'))
                <x-responsive-nav-link :href="route('resident.payment-history')" :active="request()->routeIs('resident.payment-history')">
                    {{ __('navigation.payment_history') }}
                </x-responsive-nav-link>
            @endif

            <!-- Dark Mode Toggle -->
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                <x-theme-toggle-button @click="toggleTheme()" :dark-mode="darkMode" />
            </div>

            @if (Route::has('profile.edit'))
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    {{ __('navigation.profile') }}
                </x-responsive-nav-link>
            @endif

            <!-- Language Switcher Mobile -->
            <div class="border-t border-gray-200 px-4 py-2 dark:border-gray-700">
                <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('navigation.language') ?? 'Language' }}
                </label>
                <x-locale-switcher :short="false" select-class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />
            </div>

            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('navigation.logout') }}
                    </x-responsive-nav-link>
                </form>
            @endif
        </div>
    </div>
</nav>
