@props(['room'])

<a href="{{ route('rooms.show', $room->code) }}" class="overflow-hidden rounded-2xl bg-gray-100 shadow-md transition-shadow duration-200 ease-in hover:scale-[1.02] hover:shadow-lg">
    <div class="relative h-[200px] w-full" @if ($room->thumbnail) style="background-image: url('{{ asset('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @else style="background-image: url('https://placehold.net/600x400.png');  background-size: cover; background-position: center;" @endif>
        @if ($room->is_active)
            <div class="absolute right-2 top-2 rounded-xl bg-green-500 px-3 py-1 text-white">{{ $room->residentCategory->name ?? __('public.active') }}</div>
        @endif
    </div>
    <div class="space-y-2 p-4">
        <div class="text-xl font-semibold">{{ $room->block->dorm->name }} {{ __('public.number') }} {{ $room->number }} {{ __('public.type') }} {{ $room->roomType->name }}</div>
        <div class="inline-flex rounded-md px-2 text-green-600 ring-1 ring-inset ring-green-600">{{ $room->roomType->name }}</div>
        <div class="room-location">{{ __('public.complex') }} {{ $room->block->name }}, {{ __('public.branch') }} {{ $room->block->dorm->name }}, {{ \Illuminate\Support\Str::words($room->block->dorm->address, 7, '...') }}</div>
        <div class="mt-4 flex items-center justify-between">
            <div class="text-lg font-bold">
                Rp.{{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}
                <small>{{ __('public.per_month') }}</small>
            </div>
            <div class="inline-flex items-center gap-1 text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>

                {{ $room->capacity ?? $room->roomType->default_capacity }} {{ __('public.person') }}
            </div>
        </div>
    </div>
</a>
