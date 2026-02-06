<nav x-data="{ 
    open: false,
    darkMode: localStorage.getItem('darkMode') === 'true',
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" 
x-init="
    if (darkMode) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    
    $watch('darkMode', val => {
        if (val) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });
"
class="border-b bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 transition-colors duration-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <div class="flex shrink-0 items-center">
                    {{-- LOGIC LOGO & LINK BERANDA --}}
                    @auth
                        @if (\Illuminate\Support\Facades\Route::has('dashboard'))
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        @else
                            <a href="{{ url('/') }}" class="flex items-center gap-3">
                        @endif
                    @else
                        <a href="{{ url('/') }}" class="flex items-center gap-3">
                    @endauth

                        @if ($institution?->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($institution->logo_path) }}" 
                                 alt="Logo {{ $institution->dormitory_name }}" 
                                 class="h-10 w-10 object-contain">
                        @else
                            <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        @endif
                        
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 transition-colors duration-200">
                            {{ $institution?->dormitory_name ?? config('app.name') }}
                        </h1>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('navigation.dashboard') }}
                    </x-nav-link>

                    @if (\Illuminate\Support\Facades\Route::has('resident.my-room'))
                        <x-nav-link :href="route('resident.my-room')" :active="request()->routeIs('resident.my-room')">
                            {{ __('navigation.my_room') }}
                        </x-nav-link>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('resident.room-history'))
                        <x-nav-link :href="route('resident.room-history')" :active="request()->routeIs('resident.room-history')">
                            {{ __('navigation.room_history') }}
                        </x-nav-link>
                    @endif

                    {{-- MENU TAGIHAN (DITAMBAHKAN DI SINI) --}}
                    @if (\Illuminate\Support\Facades\Route::has('resident.bills'))
                        <x-nav-link :href="route('resident.bills')" :active="request()->routeIs('resident.bills')">
                            {{ __('navigation.bills') }}
                        </x-nav-link>
                    @endif

                    {{-- MENU RIWAYAT PEMBAYARAN --}}
                    @if (\Illuminate\Support\Facades\Route::has('resident.payment-history'))
                        <x-nav-link :href="route('resident.payment-history')" :active="request()->routeIs('resident.payment-history')">
                            {{ __('navigation.payment_history') }}
                        </x-nav-link>
                    @endif

                    @guest
                        @if (\Illuminate\Support\Facades\Route::has('public.registration.create'))
                            <x-nav-link :href="route('public.registration.create')" :active="request()->routeIs('public.registration.*')">
                                {{ __('navigation.registration') }}
                            </x-nav-link>
                        @endif
                    @endguest
                </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                {{-- Language Switcher --}}
                <x-locale-switcher :short="true" />

                @auth
                    @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                    <div x-data="{ dropdownOpen: false }" 
                         @click.away="dropdownOpen = false" 
                         class="relative">
                        
                        <button @click="dropdownOpen = !dropdownOpen"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ auth()->user()->name }}
                                </div>
                            </div>
                            
                            <svg class="h-4 w-4 text-gray-500 dark:text-gray-400 transition-transform duration-200"
                                 :class="{ 'rotate-180': dropdownOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            
                            <div class="relative">
                                <div class="h-9 w-9 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md bg-green-600 dark:bg-green-500">
                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 dark:bg-green-400 border-2 border-white dark:border-gray-900"></div>
                            </div>
                        </button>

                        <div x-show="dropdownOpen"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-72 origin-top-right z-50"
                             style="display: none;">
                            
                            <div class="rounded-xl shadow-xl ring-1 overflow-hidden bg-white dark:bg-gray-800 ring-black ring-opacity-5 dark:ring-gray-700">
                                
                                <div class="px-4 py-3 border-b bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md bg-green-600 dark:bg-green-500">
                                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm text-gray-900 dark:text-gray-100 truncate">
                                                {{ auth()->user()->name }}
                                            </div>
                                            @php
                                                $residentProfile = auth()->user()->residentProfile;
                                                $residentCategory = $residentProfile?->residentCategory;
                                            @endphp
                                            @if ($residentCategory)
                                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                                                    </svg>
                                                    {{ $residentCategory->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="py-2">
                                    <a href="{{ route('profile.edit') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-semibold">{{ __('navigation.profile') }}</span>
                                    </a>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                                <div class="py-2 px-4">
                                    <button @click="toggleTheme()"
                                            type="button"
                                            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <div class="flex items-center gap-3">
                                            <svg x-show="!darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                            <svg x-show="darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                            </svg>
                                            <span class="font-medium">{{ __('navigation.dark_mode') }}</span>
                                        </div>
                                        <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                                             :class="darkMode ? 'bg-green-600' : 'bg-gray-300'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                                  :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                        </div>
                                    </button>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                                <div class="py-2">
                                    @if (\Illuminate\Support\Facades\Route::has('logout'))
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-150">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
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

                @guest
                <div class="flex items-center gap-3">
                    @if (\Illuminate\Support\Facades\Route::has('login'))
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                        {{ __('navigation.login') }}
                    </a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('public.registration.create'))
                    <a href="{{ route('public.registration.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 dark:bg-green-500 px-3 py-2 text-sm font-medium text-white hover:bg-green-700 dark:hover:bg-green-600 transition-colors">
                        {{ __('navigation.register') }}
                    </a>
                    @endif
                </div>
                @endguest
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 dark:text-gray-500 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-500 dark:hover:text-gray-400 focus:bg-gray-100 dark:focus:bg-gray-800 focus:text-gray-500 dark:focus:text-gray-400 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden sm:hidden bg-white dark:bg-gray-900 transition-colors duration-200">
        <div class="space-y-1 pb-3 pt-2">
            @auth
                @if (\Illuminate\Support\Facades\Route::has('dashboard'))
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('navigation.dashboard') }}
                    </x-responsive-nav-link>
                @endif

                @if (\Illuminate\Support\Facades\Route::has('resident.my-room'))
                    <x-responsive-nav-link :href="route('resident.my-room')" :active="request()->routeIs('resident.my-room')">
                        {{ __('navigation.my_room') }}
                    </x-responsive-nav-link>
                @endif

                @if (\Illuminate\Support\Facades\Route::has('resident.room-history'))
                    <x-responsive-nav-link :href="route('resident.room-history')" :active="request()->routeIs('resident.room-history')">
                        {{ __('navigation.room_history') }}
                    </x-responsive-nav-link>
                @endif
                
                    {{-- MENU TAGIHAN (DITAMBAHKAN DI SINI UNTUK MOBILE) --}}
                @if (\Illuminate\Support\Facades\Route::has('resident.bills'))
                    <x-responsive-nav-link :href="route('resident.bills')" :active="request()->routeIs('resident.bills')">
                        {{ __('navigation.bills') }}
                    </x-responsive-nav-link>
                @endif

                {{-- MENU RIWAYAT PEMBAYARAN (MOBILE) --}}
                @if (\Illuminate\Support\Facades\Route::has('resident.payment-history'))
                    <x-responsive-nav-link :href="route('resident.payment-history')" :active="request()->routeIs('resident.payment-history')">
                        {{ __('navigation.payment_history') }}
                    </x-responsive-nav-link>
                @endif

                {{-- MENU PROFIL (MOBILE) --}}
                @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                    <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                        {{ __('navigation.profile') }}
                    </x-responsive-nav-link>
                @endif

                {{-- PENGATURAN SECTION --}}
                <div class="pt-3 mt-2 border-t border-gray-200 dark:border-gray-700">
                    <div class="px-3 pb-1">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('navigation.settings') ?? 'Pengaturan' }}</span>
                    </div>
                </div>

                <div class="px-4 py-3">
                    <button @click="toggleTheme()"
                            type="button"
                            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150">
                        <div class="flex items-center gap-3">
                            <svg x-show="!darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg x-show="darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <span class="font-medium">{{ __('navigation.dark_mode') }}</span>
                        </div>
                        <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                             :class="darkMode ? 'bg-green-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                  :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></span>
                        </div>
                    </button>
                </div>

                {{-- Language Switcher Mobile --}}
                <div class="px-4 py-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('navigation.language') ?? 'Language' }}</label>
                    <x-locale-switcher
                        :short="false"
                        select-class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />
                </div>

                @if (\Illuminate\Support\Facades\Route::has('logout'))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-300 dark:hover:border-red-700 transition duration-150 ease-in-out">
                                {{ __('navigation.logout') }}
                            </button>
                        </form>
                    </div>
                @endif
            @endauth

            @guest
                @if (\Illuminate\Support\Facades\Route::has('login'))
                    <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                        {{ __('navigation.login') }}
                    </x-responsive-nav-link>
                @endif

                @if (\Illuminate\Support\Facades\Route::has('public.registration.create'))
                    <x-responsive-nav-link :href="route('public.registration.create')" :active="request()->routeIs('public.registration.*')">
                        {{ __('navigation.registration') }}
                    </x-responsive-nav-link>
                @endif
            @endguest
        </div>
    </div>
</nav>