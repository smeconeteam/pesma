<nav x-data="{ 
    open: false,
    darkMode: localStorage.getItem('darkMode') === 'true'
}" 
x-init="
    // Sinkronisasi dengan DOM saat load
    darkMode = document.documentElement.classList.contains('dark');
    
    // Watch perubahan darkMode
    $watch('darkMode', val => {
        localStorage.setItem('darkMode', val ? 'true' : 'false');
        if (val) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });
"
class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 transition-colors duration-200">
    <!-- Menu Navigasi Utama -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
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
                                    <!-- Fallback ke SVG default jika tidak ada logo -->
                                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                @endif
                                
                                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $institution?->dormitory_name ?? config('app.name') }}
                                </h1>
                            </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('navigation.dashboard') }}
                    </x-nav-link>

                    @if (\Illuminate\Support\Facades\Route::has('resident.my-room'))
                    <x-nav-link :href="route('resident.my-room')" :active="request()->routeIs('resident.my-room')">
                        {{ __('navigation.my_room') }}
                    </x-nav-link>

                    @if (\Illuminate\Support\Facades\Route::has('resident.room-history'))
                    <x-nav-link :href="route('resident.room-history')" :active="request()->routeIs('resident.room-history')">
                        {{ __('navigation.room_history') }}
                    </x-nav-link>

                    @guest
                    @if (\Illuminate\Support\Facades\Route::has('public.registration.create'))
                    <x-nav-link :href="route('public.registration.create')" :active="request()->routeIs('public.registration.*')">
                        {{ __('navigation.registration') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Area kanan (Desktop) -->
            <div class="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                {{-- Language Switcher dengan localStorage --}}
                <x-locale-switcher :short="true" />

                @auth
                <!-- Modern User Dropdown -->
                @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                <div x-data="{ dropdownOpen: false }" 
                     @click.away="dropdownOpen = false" 
                     class="relative">
                    
                    <!-- Trigger Button -->
                    <button @click="dropdownOpen = !dropdownOpen"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <!-- User Info -->
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ auth()->user()->name }}
                            </div>
                        </div>
                        
                        <!-- Chevron -->
                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400 transition-transform duration-200"
                             :class="{ 'rotate-180': dropdownOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        
                        <!-- Avatar -->
                        <div class="relative">
                            <div class="h-9 w-9 rounded-full bg-black flex items-center justify-center text-white font-bold text-sm shadow-md">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 border-2 border-white dark:border-gray-900"></div>
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="dropdownOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-72 origin-top-right z-50"
                         style="display: none;">
                        
                        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-xl ring-1 ring-black ring-opacity-5 dark:ring-gray-700 overflow-hidden">
                            
                            <!-- User Info Section -->
                            <div class="px-4 py-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-full bg-black flex items-center justify-center text-white font-bold text-lg shadow-md">
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">
                                            {{ auth()->user()->name }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <svg class="h-3.5 w-3.5 text-green-600 dark:text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Penghuni</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <!-- Profile Link -->
                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="font-medium">Profil Saya</span>
                                </a>

                                <!-- Dark Mode Toggle -->
                                <button @click="darkMode = !darkMode"
                                        class="w-full flex items-center justify-between gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <div class="flex items-center gap-3">
                                        <svg x-show="!darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <svg x-show="darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                        </svg>
                                        <span class="font-medium">Tema Gelap</span>
                                    </div>
                                    <!-- Toggle Switch -->
                                    <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                                         :class="darkMode ? 'bg-green-600' : 'bg-gray-300'">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                              :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                    </div>
                                </button>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200 dark:border-gray-700"></div>

                            <!-- Logout -->
                            <div class="py-2">
                                @if (\Illuminate\Support\Facades\Route::has('logout'))
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-150">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        <span class="font-semibold">Keluar</span>
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
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-300 hover:underline">
                        {{ __('navigation.login') }}
                    </a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('public.registration.create'))
                    <a href="{{ route('public.registration.create') }}"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ __('navigation.register') }}
                    </a>
                    @endif
                </div>
                @endguest
            </div>

            <!-- Hamburger -->
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

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('navigation.dashboard') }}
            </x-responsive-nav-link>

            @if (\Illuminate\Support\Facades\Route::has('resident.my-room'))
            <x-responsive-nav-link :href="route('resident.my-room')" :active="request()->routeIs('resident.my-room')">
                {{ __('navigation.my_room') }}
            </x-responsive-nav-link>

            @if (\Illuminate\Support\Facades\Route::has('resident.room-history'))
            <x-responsive-nav-link :href="route('resident.room-history')" :active="request()->routeIs('resident.room-history')">
                {{ __('navigation.room_history') }}
            </x-responsive-nav-link>

            <!-- Dark Mode Toggle (Mobile) -->
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <button @click="darkMode = !darkMode"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150">
                    <div class="flex items-center gap-3">
                        <svg x-show="!darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg x-show="darkMode" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <span class="font-medium">Tema Gelap</span>
                    </div>
                    <!-- Toggle Switch -->
                    <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                         :class="darkMode ? 'bg-green-600' : 'bg-gray-300'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                              :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></span>
                    </div>
                </button>
            </div>

            {{-- Link Profil tetap ada --}}
            @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                {{ __('navigation.profile') }}
            </x-responsive-nav-link>
            @endif

            {{-- Language Switcher Mobile --}}
            <div class="px-4 py-2 border-t border-gray-200">
                <label class="block text-xs font-medium text-gray-700 mb-2">{{ __('navigation.language') ?? 'Language' }}</label>
                <x-locale-switcher
                    :short="false"
                    select-class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" />
            </div>

            @if (\Illuminate\Support\Facades\Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('navigation.logout') }}
                </x-responsive-nav-link>
            </form>
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