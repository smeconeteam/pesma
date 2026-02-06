<x-public-layout>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-center gap-4 text-center md:gap-6">
            <h1 class="text-3xl font-bold md:text-4xl lg:text-5xl">{{ __('public.landing-headline') }}</h1>

            <p class="text-base">{{ $institution->dormitory_name ?? __('public.modern_dormitory') }} {{ __('public.landing-description') }}</p>

            <div class="mt-2 flex gap-2">
                <a href="#rooms" class="rounded-md bg-gray-100 px-4 py-3 text-center text-sm font-medium text-green-600 ring-1 ring-inset ring-green-600 transition-colors hover:bg-green-600 hover:text-white">{{ __('public.view_rooms') }}</a>
                <a href="{{ route('public.registration.create') }}" class="items-center rounded-md bg-green-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">{{ __('public.register_now') }}</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-3xl flex-col items-center justify-between gap-6 rounded-2xl bg-white px-12 py-6 text-center shadow-md md:flex-row">
            <div class="flex flex-col gap-2">
                <h3 class="text-4xl font-bold text-green-600">{{ $totalRooms - 1 }}+</h3>
                <p>{{ __('public.rooms_available') }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <h3 class="text-4xl font-bold text-green-600">100%</h3>
                <p>{{ __('public.full_facilities') }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <h3 class="text-4xl font-bold text-green-600">24/7</h3>
                <p>{{ __('public.security_guaranteed') }}</p>
            </div>
        </div>
    </div>

    <!-- Rooms Section -->
    <div class="mx-auto mt-24 flex flex-col items-center justify-between gap-6 rounded-t-2xl bg-white px-4 py-6 sm:px-6 lg:px-8" id="rooms">
        <div class="flex w-full items-center justify-between border-b-2 border-gray-200 pb-4">
            <div>
                <h2 class="text-xl font-semibold">{{ __('public.available_rooms') }}</h2>
                <p class="text-sm">{{ __('public.choose_best_room') }}</p>
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
                    <a href="{{ route('rooms.show', $room->id) }}" class="overflow-hidden rounded-2xl bg-gray-100 shadow-md transition-shadow duration-200 ease-in hover:scale-[1.02] hover:shadow-lg">
                        <div class="relative h-[200px] w-full" @if ($room->thumbnail) style="background-image: url('{{ asset('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @else style="background-image: url('https://placehold.net/600x400.png');  background-size: cover; background-position: center;" @endif>
                            @if ($room->is_active)
                                <div class="absolute right-2 top-2 rounded-xl bg-green-500 px-3 py-1 text-white">{{ $room->residentCategory->name ?? __('public.active') }}</div>
                            @endif
                        </div>
                        <div class="space-y-2 p-4">
                            <div class="text-xl font-semibold">{{ $room->block->dorm->name }} Nomor {{ $room->number }} Tipe {{ $room->roomType->name }}</div>
                            <div class="inline-flex rounded-md px-2 text-green-400 ring-1 ring-inset ring-green-600">{{ $room->roomType->name }}</div>
                            <div class="room-location">Komplek {{ $room->block->name }}, Cabang {{ $room->block->dorm->name }}, {{ \Illuminate\Support\Str::words($room->block->dorm->address, 7, '...') }}</div>
                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-lg font-bold">
                                    Rp.{{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}
                                    <small>/bulan</small>
                                </div>
                                <div class="inline-flex items-center gap-1 text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>

                                    {{ $room->capacity ?? $room->roomType->default_capacity }} orang
                                </div>
                            </div>
                        </div>
                    </a>
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
                <p style="font-size: 20px; color: #64748b;">{{ __('public.no_rooms_available') }}</p>
            </div>
        @endif
    </div>

</x-public-layout>
