<x-public-layout>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-center gap-4 text-center md:gap-6">
            <h1 class="max-w-4xl text-3xl font-bold md:text-4xl lg:text-5xl dark:text-white">{{ __('public.landing-headline', ['name' => $institution->dormitory_name ?? config('app.name', 'Laravel')]) }}</h1>

            <p class="text-base text-gray-600 dark:text-gray-300">{{ $institution->dormitory_name ?? __('public.modern_dormitory') }} {{ __('public.landing-description') }}</p>

            <div class="mt-2 flex gap-2">
                <a href="#rooms" class="rounded-md bg-gray-100 px-4 py-3 text-center text-sm font-medium text-green-600 ring-1 ring-inset ring-green-600 transition-colors hover:bg-green-600 hover:text-white dark:bg-gray-800 dark:text-green-400 dark:ring-green-500 dark:hover:bg-green-600 dark:hover:text-white">{{ __('public.view_rooms') }}</a>
                <a href="{{ route('public.registration.create') }}" class="items-center rounded-md bg-green-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">{{ __('public.register_now') }}</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-3xl flex-col items-center justify-between gap-6 rounded-2xl bg-white px-12 py-6 text-center shadow-md md:flex-row dark:bg-gray-800 dark:shadow-gray-900/10">
            <div class="flex flex-col gap-2">
                <h3 class="text-4xl font-bold text-green-600 dark:text-green-400">{{ $totalRooms - 1 }}+</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ __('public.rooms_available') }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <h3 class="text-4xl font-bold text-green-600 dark:text-green-400">100%</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ __('public.full_facilities') }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <h3 class="text-4xl font-bold text-green-600 dark:text-green-400">24/7</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ __('public.security_guaranteed') }}</p>
            </div>
        </div>
    </div>

    <!-- Rooms Section -->
    <div class="mx-auto mt-24 flex flex-col items-center justify-between gap-6 rounded-t-2xl bg-white px-4 py-6 sm:px-6 lg:px-8 dark:bg-gray-800 dark:shadow-gray-900/10" id="rooms">
        <div class="flex w-full items-center justify-between border-b-2 border-gray-200 pb-4 dark:border-gray-700">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.available_rooms') }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('public.choose_best_room') }}</p>
            </div>
            @if ($totalRooms > 6)
                <a href="{{ route('rooms.available') }}" class="hidden items-center rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 md:inline-flex dark:bg-green-500 dark:hover:bg-green-600">
                    {{ __('public.view_all') }} ({{ $totalRooms }})
                </a>
            @endif
        </div>

        @if ($rooms->count() > 0)
            <div class="grid w-full grid-cols-1 gap-6 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rooms as $room)
                    <x-room-card :room="$room" />
                @endforeach
            </div>

            @if ($totalRooms > 6)
                <div class="view-all-wrapper">
                    <a href="{{ route('rooms.available') }}" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 md:hidden dark:bg-green-500 dark:hover:bg-green-600">
                        {{ __('public.view_all') }} ({{ $totalRooms }})
                    </a>
                </div>
            @endif
        @else
            <div style="text-align: center; padding: 80px 20px;">
                <p class="text-gray-500 dark:text-gray-400" style="font-size: 20px;">{{ __('public.no_rooms_available') }}</p>
            </div>
        @endif
    </div>

</x-public-layout>
