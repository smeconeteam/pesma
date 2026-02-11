@props(['room'])

<a href="{{ route('rooms.show', $room->code) }}" class="group overflow-hidden rounded-2xl bg-white shadow-md transition-all duration-200 ease-in hover:scale-[1.02] hover:shadow-lg dark:bg-gray-800 dark:shadow-gray-900/20">
    <div class="relative h-[200px] w-full bg-gray-200 dark:bg-gray-700" @if ($room->thumbnail) style="background-image: url('{{ asset('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @else style="background-image: url('https://placehold.net/600x400.png');  background-size: cover; background-position: center;" @endif>
        @if ($room->is_active)
            <div class="absolute right-2 top-2 rounded-xl bg-green-500 px-3 py-1 text-xs font-bold text-white shadow-sm">{{ $room->residentCategory->name ?? __('public.active') }}</div>
        @endif
    </div>
    <div class="space-y-3 p-4">
        <div>
            <div class="text-lg font-bold text-gray-900 line-clamp-2 dark:text-white">
                {{ $room->block->dorm->name }} {{ __('public.number') }} {{ $room->number }}
            </div>
            <div class="mt-1 flex flex-wrap gap-2">
                 <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30">
                    {{ $room->roomType->name }}
                </span>
            </div>
        </div>

        <div class="text-sm text-gray-600 line-clamp-2 dark:text-gray-400">
            {{ __('public.complex') }} {{ $room->block->name }}, {{ $room->block->dorm->name }}, {{ \Illuminate\Support\Str::words($room->block->dorm->address, 7, '...') }}
        </div>

        <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
            <div class="flex flex-col">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('public.price') }}</span>
                <div class="text-lg font-bold text-green-600 dark:text-green-500">
                    Rp {{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}
                    <small class="text-sm font-normal text-gray-500 dark:text-gray-400">/{{ __('public.month') }}</small>
                </div>
            </div>
            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span class="text-sm font-medium">{{ $room->capacity ?? $room->roomType->default_capacity }} {{ __('public.person') }}</span>
            </div>
        </div>
    </div>
</a>
