<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
            {{ __('roomhistory.title') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-12 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-4 sm:p-6">
                    {{-- Header Info Penghuni --}}
                    <div class="mb-4 sm:mb-6 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 p-3 sm:p-4">
                        <div class="flex flex-col sm:flex-row items-start sm:items-start justify-between gap-3">
                            <div class="flex-1 w-full">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $residentProfile->full_name }}</h3>
                                <div class="mt-2 space-y-1 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                    <p><span class="font-medium">{{ __('roomhistory.student_id') }}:</span> {{ $residentProfile->student_id }}</p>
                                    <p><span class="font-medium">{{ __('roomhistory.category') }}:</span> {{ $residentProfile->residentCategory->name ?? '-' }}</p>
                                    <p><span class="font-medium">{{ __('roomhistory.status') }}:</span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            @if($residentProfile->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($residentProfile->status === 'inactive') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @endif">
                                            {{ ucfirst($residentProfile->status) }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            @if($residentProfile->photo_path)
                            <img src="{{ Storage::url($residentProfile->photo_path) }}"
                                alt="{{ $residentProfile->full_name }}"
                                class="h-16 w-16 sm:h-20 sm:w-20 rounded-lg object-cover shrink-0 border border-gray-200 dark:border-gray-600">
                            @else
                            <div class="flex h-16 w-16 sm:h-20 sm:w-20 shrink-0 items-center justify-center rounded-lg bg-gray-200 dark:bg-gray-600 text-xl sm:text-2xl font-bold text-gray-600 dark:text-gray-300">
                                {{ substr($residentProfile->full_name, 0, 1) }}
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Kamar Saat Ini --}}
                    @if($currentRoom)
                    <div class="mb-4 sm:mb-6 rounded-lg border-2 border-green-500 bg-green-50 dark:bg-green-900/20 p-3 sm:p-4">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex-1">
                                <h4 class="text-xs sm:text-sm font-medium text-green-800 dark:text-green-300">{{ __('roomhistory.current_room') }}</h4>
                                <p class="mt-1 text-base sm:text-lg font-semibold text-green-900 dark:text-green-100 break-words">
                                    {{ $currentRoom->room->block->dorm->name }} -
                                    {{ $currentRoom->room->block->name }} -
                                    Kamar {{ $currentRoom->room->number }} ({{ $currentRoom->room->roomType->name }})
                                </p>
                                <p class="mt-1 text-xs sm:text-sm text-green-700 dark:text-green-300">
                                    {{ __('roomhistory.check_in') }}: {{ \Carbon\Carbon::parse($currentRoom->check_in_date)->format('d M Y') }}
                                    @if($currentRoom->is_pic)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-green-600 px-2 py-0.5 text-xs font-medium text-white">
                                        {{ __('roomhistory.room_pic') }}
                                    </span>
                                    @endif
                                </p>
                            </div>
                            <svg class="h-6 w-6 sm:h-8 sm:w-8 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                    </div>
                    @else
                    <div class="mb-4 sm:mb-6 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 p-3 sm:p-4">
                        <p class="text-center text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ __('roomhistory.no_current_room') }}</p>
                    </div>
                    @endif

                    {{-- Summary Statistics --}}
                    @if(!$histories->isEmpty())
                    <div class="mb-6 sm:mb-8 grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-3 sm:p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-2 sm:p-3 shrink-0">
                                    <svg class="h-5 w-5 sm:h-6 sm:w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ __('roomhistory.total_rooms') }}</p>
                                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $histories->count() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-3 sm:p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-yellow-100 dark:bg-yellow-900/30 p-2 sm:p-3 shrink-0">
                                    <svg class="h-5 w-5 sm:h-6 sm:w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ __('roomhistory.room_transfers') }}</p>
                                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $histories->where('movement_type', 'transfer')->count() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-3 sm:p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-purple-100 dark:bg-purple-900/30 p-2 sm:p-3 shrink-0">
                                    <svg class="h-5 w-5 sm:h-6 sm:w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ __('roomhistory.times_as_pic') }}</p>
                                    <p class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $histories->where('is_pic', true)->count() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Riwayat Perpindahan --}}
                    <div class="mb-3 sm:mb-4">
                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('roomhistory.history_title') }}</h4>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ __('roomhistory.total_history', ['count' => $histories->count()]) }}</p>
                    </div>

                    @if($histories->isEmpty())
                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 p-6 sm:p-8 text-center">
                        <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ __('roomhistory.no_history') }}</p>
                    </div>
                    @else
                    {{-- Timeline View --}}
                    <div class="relative">
                        @foreach($histories as $index => $history)
                        <div class="mb-6 sm:mb-8 flex">
                            {{-- Timeline Line --}}
                            <div class="relative mr-3 sm:mr-4 flex flex-col items-center">
                                {{-- Circle Icon --}}
                                <div class="flex h-8 w-8 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full border-2
                                            @if($history->movement_type === 'new') border-blue-500 bg-blue-100 dark:bg-blue-900/30
                                            @elseif($history->movement_type === 'transfer') border-yellow-500 bg-yellow-100 dark:bg-yellow-900/30
                                            @else border-red-500 bg-red-100 dark:bg-red-900/30
                                            @endif">
                                    @if($history->movement_type === 'new')
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    @elseif($history->movement_type === 'transfer')
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    @else
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    @endif
                                </div>

                                {{-- Vertical Line --}}
                                @if(!$loop->last)
                                <div class="h-full w-0.5 bg-gray-300 dark:bg-gray-600"></div>
                                @endif
                            </div>

                            {{-- Content Card --}}
                            <div class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 sm:p-4 shadow-sm">
                                <div class="mb-2 flex flex-col sm:flex-row items-start sm:items-start justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                    @if($history->movement_type === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @elseif($history->movement_type === 'transfer') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @endif">
                                            @if($history->movement_type === 'new') {{ __('roomhistory.movement_new') }}
                                            @elseif($history->movement_type === 'transfer') {{ __('roomhistory.movement_transfer') }}
                                            @else {{ __('roomhistory.movement_checkout') }}
                                            @endif
                                        </span>
                                        @if($history->is_pic)
                                        <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ __('roomhistory.room_pic') }}
                                        </span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">
                                        {{ \Carbon\Carbon::parse($history->created_at)->format('d M Y H:i') }}
                                    </span>
                                </div>

                                <div class="mt-2 sm:mt-3">
                                    <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-gray-100 break-words">
                                        {{ $history->room->block->dorm->name }} -
                                        {{ $history->room->block->name }}
                                    </p>
                                    <p class="text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100 break-words">
                                        {{ __('roomhistory.room', ['number' => $history->room->number]) }} - {{ $history->room->roomType->name }}
                                    </p>

                                    <div class="mt-2 grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('roomhistory.check_in') }}:</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ \Carbon\Carbon::parse($history->check_in_date)->format('d M Y') }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('roomhistory.check_out') }}:</span>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $history->check_out_date ? \Carbon\Carbon::parse($history->check_out_date)->format('d M Y') : '-' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($history->check_in_date && $history->check_out_date)
                                    @php
                                    $checkIn = \Carbon\Carbon::parse($history->check_in_date);
                                    $checkOut = \Carbon\Carbon::parse($history->check_out_date);
                                    $now = now();

                                    if ($checkIn->gt($now)) {
                                        $days = 0;
                                        $displayText = __('roomhistory.days_before_checkin');
                                    } else {
                                        $days = (int) $checkIn->diffInDays($checkOut);
                                        $displayText = __('roomhistory.days', ['days' => $days]);
                                    }
                                    @endphp
                                    <div class="mt-2">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('roomhistory.duration') }}:</span>
                                        <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $displayText }}
                                        </p>
                                    </div>
                                    @elseif($history->check_in_date && !$history->check_out_date)
                                    @php
                                    $checkIn = \Carbon\Carbon::parse($history->check_in_date);
                                    $now = now();

                                    if ($now->lt($checkIn)) {
                                        $days = (int) $now->diffInDays($checkIn);
                                        $displayText = __('roomhistory.will_checkin', ['days' => $days]);
                                    } else {
                                        $days = (int) $checkIn->diffInDays($now);
                                        $displayText = __('roomhistory.days', ['days' => $days]);
                                    }
                                    @endphp
                                    <div class="mt-2">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('roomhistory.duration') }}:</span>
                                        <p class="text-xs sm:text-sm font-medium text-green-700 dark:text-green-300">
                                            {{ $displayText }}
                                        </p>
                                    </div>
                                    @endif


                                    @if($history->notes)
                                    <div class="mt-2 sm:mt-3 rounded bg-gray-50 dark:bg-gray-700/50 p-2">
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('roomhistory.notes') }}:</p>
                                        <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 break-words">{{ $history->notes }}</p>
                                    </div>
                                    @endif

                                    @if($history->recordedBy)
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                        {{ __('roomhistory.recorded_by') }}: {{ $history->recordedBy->name }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Back to Top Button --}}
    <button id="backToTop"
        class="fixed bottom-6 right-6 z-50 hidden rounded-full bg-gradient-to-r from-green-600 to-emerald-600 p-3 shadow-lg transition-all hover:from-green-700 hover:to-emerald-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 active:scale-95">
        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Back to Top functionality
            const backToTopButton = document.getElementById('backToTop');

            if (backToTopButton) {
                // Show button when user scrolls down 300px
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        backToTopButton.classList.remove('hidden');
                    } else {
                        backToTopButton.classList.add('hidden');
                    }
                });

                // Scroll to top when button is clicked
                backToTopButton.addEventListener('click', () => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>
</x-app-layout>