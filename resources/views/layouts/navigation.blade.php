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
                <a href="{{ localizedRoute('dashboard') }}" class="flex items-center gap-3">
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
                <x-nav-link :href="localizedRoute('dashboard')" :active="request()->routeIs('dashboard.*')">
                    {{ __('navigation.dashboard') }}
                </x-nav-link>

                {{-- Room Dropdown --}}
                @if (Route::has('resident.my-room.id') || Route::has('resident.my-room.en') || Route::has('resident.room-history.id') || Route::has('resident.room-history.en'))
                    <div x-data="{ roomOpen: false }" @click.away="roomOpen = false" class="relative flex h-full items-stretch">
                        <button @click="roomOpen = !roomOpen" type="button" class="{{ request()->routeIs('resident.my-room.*') || request()->routeIs('resident.room-history.*') ? 'border-green-400 text-gray-900 focus:border-green-700 dark:border-green-400 dark:text-gray-100' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-300' }} inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none">
                            {{ __('navigation.room') }}
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': roomOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="roomOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute left-0 top-full z-50 mt-1 w-48 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700" style="display: none;">
                            <div class="py-1">
                                @if (Route::has('resident.my-room.id') || Route::has('resident.my-room.en'))
                                    <a href="{{ localizedRoute('resident.my-room') }}" class="{{ request()->routeIs('resident.my-room.*') ? 'bg-gray-100 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }} block px-4 py-2 text-sm transition-colors duration-150">
                                        {{ __('navigation.my_room') }}
                                    </a>
                                @endif
                                @if (Route::has('resident.room-history.id') || Route::has('resident.room-history.en'))
                                    <a href="{{ localizedRoute('resident.room-history') }}" class="{{ request()->routeIs('resident.room-history.*') ? 'bg-gray-100 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }} block px-4 py-2 text-sm transition-colors duration-150">
                                        {{ __('navigation.room_history') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Keuangan Dropdown --}}
                @if (Route::has('resident.bills.id') || Route::has('resident.bills.en') || Route::has('resident.payment-history.id') || Route::has('resident.payment-history.en'))
                    <div x-data="{ financeOpen: false }" @click.away="financeOpen = false" class="relative flex h-full items-stretch">
                        <button @click="financeOpen = !financeOpen" type="button" class="{{ request()->routeIs('resident.bills.*') || request()->routeIs('resident.payment-history.*') ? 'border-green-400 text-gray-900 focus:border-green-700 dark:border-green-400 dark:text-gray-100' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-300' }} inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none">
                            {{ __('navigation.finance') }}
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': financeOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="financeOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute left-0 top-full z-50 mt-1 w-48 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700" style="display: none;">
                            <div class="py-1">
                                @if (Route::has('resident.bills.id') || Route::has('resident.bills.en'))
                                    <a href="{{ localizedRoute('resident.bills') }}" class="{{ request()->routeIs('resident.bills.*') ? 'bg-gray-100 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }} block px-4 py-2 text-sm transition-colors duration-150">
                                        {{ __('navigation.bills') }}
                                    </a>
                                @endif
                                @if (Route::has('resident.payment-history.id') || Route::has('resident.payment-history.en'))
                                    <a href="{{ localizedRoute('resident.payment-history') }}" class="{{ request()->routeIs('resident.payment-history.*') ? 'bg-gray-100 font-semibold text-gray-900 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }} block px-4 py-2 text-sm transition-colors duration-150">
                                        {{ __('navigation.payment_history') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Desktop Right Side -->
            <div class="hidden gap-4 sm:ms-6 sm:flex sm:items-center">
                <x-locale-switcher :short="true" />

                @auth
                    @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                        @php
                            $navUserPhoto = auth()->user()->residentProfile?->photo_path;
                        @endphp
                        <div x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false" class="relative">

                            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-3 rounded-lg px-3 py-2 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ auth()->user()->name }}
                                    </div>
                                </div>

                                <svg class="h-4 w-4 text-gray-500 transition-transform duration-200 dark:text-gray-400" :class="{ 'rotate-180': dropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>

                                <div class="relative">
                                    @if ($navUserPhoto)
                                        <img src="{{ Storage::url($navUserPhoto) }}" alt="{{ auth()->user()->name }}" class="h-9 w-9 rounded-full object-cover shadow-md">
                                    @else
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-md dark:bg-green-500">
                                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500 dark:border-gray-900 dark:bg-green-400"></div>
                                </div>
                            </button>

                            <div x-show="dropdownOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 z-50 mt-2 w-72 origin-top-right" style="display: none;">

                                <div class="overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700">

                                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                                        <div class="flex items-center gap-3">
                                            @if ($navUserPhoto)
                                                <img src="{{ Storage::url($navUserPhoto) }}" alt="{{ auth()->user()->name }}" class="h-12 w-12 rounded-full object-cover shadow-md">
                                            @else
                                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-600 text-lg font-bold text-white shadow-md dark:bg-green-500">
                                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-bold text-gray-900 dark:text-gray-100">
                                                    {{ auth()->user()->name }}
                                                </div>
                                                @php
                                                    $residentProfile = auth()->user()->residentProfile;
                                                    $residentCategory = $residentProfile?->residentCategory;
                                                @endphp
                                                @if ($residentCategory)
                                                    <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-100">
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                                                        </svg>
                                                        {{ $residentCategory->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="py-2">
                                        <a href="{{ localizedRoute('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors duration-150 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="font-semibold">{{ __('navigation.profile') }}</span>
                                        </a>
                                        @if (auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                                            <a href="{{ route('filament.admin.pages.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors duration-150 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                                <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span class="font-semibold">{{ __('navigation.admin_panel') }}</span>
                                            </a>
                                        @endif
                                    </div>

                                    <div class="border-t border-gray-200 dark:border-gray-700"></div>

                                    <div class="px-4 py-2">
                                        <button @click="toggleTheme()" type="button" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors duration-150 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                            <div class="flex items-center gap-3">
                                                <svg x-show="!darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                <svg x-show="darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                                </svg>
                                                <span class="font-medium">{{ __('navigation.dark_mode') }}</span>
                                            </div>
                                            <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200" :class="darkMode ? 'bg-green-600' : 'bg-gray-300'">
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200" :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                            </div>
                                        </button>
                                    </div>

                                    <div class="border-t border-gray-200 dark:border-gray-700"></div>

                                    <div class="py-2">
                                        @if (\Illuminate\Support\Facades\Route::has('logout'))
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition-colors duration-150 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                    </svg>
                                                    <span class="font-semibold">{{ __('navigation.logout') }}</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth
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
                <x-responsive-nav-link :href="localizedRoute('dashboard')" :active="request()->routeIs('dashboard.*')">
                    {{ __('navigation.dashboard') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.my-room.id') || Route::has('resident.my-room.en'))
                <x-responsive-nav-link :href="localizedRoute('resident.my-room')" :active="request()->routeIs('resident.my-room.*')">
                    {{ __('navigation.my_room') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.room-history.id') || Route::has('resident.room-history.en'))
                <x-responsive-nav-link :href="localizedRoute('resident.room-history')" :active="request()->routeIs('resident.room-history.*')">
                    {{ __('navigation.room_history') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.bills.id') || Route::has('resident.bills.en'))
                <x-responsive-nav-link :href="localizedRoute('resident.bills')" :active="request()->routeIs('resident.bills.*')">
                    {{ __('navigation.bills') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('resident.payment-history.id') || Route::has('resident.payment-history.en'))
                <x-responsive-nav-link :href="localizedRoute('resident.payment-history')" :active="request()->routeIs('resident.payment-history.*')">
                    {{ __('navigation.payment_history') }}
                </x-responsive-nav-link>
            @endif

            @if (Route::has('profile.edit'))
                <x-responsive-nav-link :href="localizedRoute('profile.edit')" :active="request()->routeIs('profile.edit.*')">
                    {{ __('navigation.profile') }}
                </x-responsive-nav-link>
            @endif

            @if (auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                <x-responsive-nav-link :href="route('filament.admin.pages.dashboard')">
                    {{ __('navigation.admin_panel') }}
                </x-responsive-nav-link>
            @endif

            <!-- Language Switcher Mobile -->
            <div class="border-t border-gray-200 px-4 py-2 dark:border-gray-700">
                <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('navigation.language') ?? 'Language' }}
                </label>
                <x-locale-switcher :short="false" select-class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />
            </div>

            <!-- Dark Mode Toggle -->
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                <button @click="toggleTheme()" type="button" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors duration-150 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <svg x-show="!darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg x-show="darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span class="font-medium">Tema Gelap</span>
                    </div>
                    <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200" :class="darkMode ? 'bg-green-600' : 'bg-gray-300'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200" :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></span>
                    </div>
                </button>
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
