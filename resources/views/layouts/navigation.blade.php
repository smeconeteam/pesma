<nav x-data="{ open: false }" class="border-b border-gray-100 bg-white">
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
                                <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                                @endif

                                <h1 class="text-xl font-semibold">
                                    {{ $institution?->dormitory_name ?? config('app.name') }}
                                </h1>
                            </a>
                </div>

                <!-- Link Navigasi -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                    @if (\Illuminate\Support\Facades\Route::has('dashboard'))
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('navigation.dashboard') }}
                    </x-nav-link>
                    @endif

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
                    @endauth

                    @guest
                    @if (\Illuminate\Support\Facades\Route::has('public.registration.create'))
                    <x-nav-link :href="route('public.registration.create')" :active="request()->routeIs('public.registration.*')">
                        {{ __('navigation.registration') }}
                    </x-nav-link>
                    @endif
                    @endguest
                </div>
            </div>

            <!-- Area kanan (Desktop) -->
            <div class="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                {{-- Language Switcher dengan localStorage --}}
                <x-locale-switcher :short="true" />

                @auth
                @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                            <div>{{ auth()->user()->name }}</div>

                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('navigation.profile') }}
                        </x-dropdown-link>

                        @if (\Illuminate\Support\Facades\Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('navigation.logout') }}
                            </x-dropdown-link>
                        </form>
                        @endif
                    </x-slot>
                </x-dropdown>
                @endif
                @endauth

                @guest
                <div class="flex items-center gap-3">
                    @if (\Illuminate\Support\Facades\Route::has('login'))
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:underline">
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

            <!-- Tombol Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! open, 'inline-flex': open }" class="hidden"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Navigasi Responsif -->
    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden sm:hidden">
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