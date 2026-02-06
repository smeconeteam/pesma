<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('profile.title') }}
        </h2>
    </x-slot>

    @php
    $profile = $user->residentProfile ?? null;

    // Foto profil
    $photoUrl = null;
    if ($profile?->photo_path) {
    $photoUrl = \Illuminate\Support\Facades\Storage::url($profile->photo_path);
    }

    // Penempatan kamar aktif
    $room = $assignment?->room;
    $block = $room?->block;
    $dorm = $block?->dorm;
    $roomType = $room?->roomType;

    $roomCode = $room?->code
    ?? $room?->room_code
    ?? $room?->name
    ?? $room?->number
    ?? '-';

    $status = $profile?->status ?? ($assignment ? 'active' : 'registered');
    $statusLabel = match ($status) {
    'active', 'aktif' => 'Aktif',
    'inactive', 'nonaktif' => 'Nonaktif',
    'registered', 'pending', 'menunggu_penempatan' => 'Menunggu Penempatan',
    default => ucfirst(str_replace('_', ' ', (string) $status)),
    };

    $checkIn = $assignment?->check_in_date ?? $profile?->check_in_date;
    $checkInLabel = $checkIn
    ? (method_exists($checkIn, 'format') ? $checkIn->format('d M Y') : date('d M Y', strtotime((string) $checkIn)))
    : '-';

    // PIC kamar
    $picUser = $picAssignment?->user ?? null;
    $picName = $picUser
    ? ($picUser->residentProfile?->full_name ?: $picUser->name)
    : '-';

    $isYouPic = $picUser?->id && $picUser->id === $user->id;

    // helper untuk list item - updated for mobile
    $itemsClass = "divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden";
    $rowClass = "px-3 py-2.5 sm:px-4 sm:py-3 flex flex-col sm:grid sm:grid-cols-3 sm:gap-4 gap-0.5";
    $labelClass = "text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400";
    $valueClass = "text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 break-words";

    $isInactive = in_array($status, ['inactive', 'nonaktif']);
    @endphp

    <div class="py-4 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- Ringkasan --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg rounded-xl">
                <div class="p-4 sm:p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="shrink-0">
                            @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Foto Profil"
                                class="h-14 w-14 sm:h-16 sm:w-16 rounded-full object-cover border dark:border-gray-600" />
                            @else
                            <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-full bg-gray-100 dark:bg-gray-700 border dark:border-gray-600 flex items-center justify-center">
                                <span class="text-lg sm:text-xl font-semibold text-gray-600 dark:text-gray-400">
                                    {{ mb_substr($profile?->full_name ?? $user->name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                {{ $profile?->full_name ?? $user->name ?? '-' }}
                            </div>
                            <div class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-400 truncate">
                                {{ $user->email ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Status: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $statusLabel }}</span>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1.5 sm:gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border dark:border-gray-600">
                                    {{ __('profile.room_code') }}: {{ $roomCode }}
                                </span>

                                <span class="inline-flex items-center px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border dark:border-gray-600">
                                    {{ __('profile.check_in') }}: {{ $checkInLabel }}
                                </span>

                                @if ($assignment)
                                <span class="inline-flex items-center px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold {{ $assignment->is_pic ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border dark:border-gray-600' }} border">
                                    {{ $assignment->is_pic ? __('profile.you_pic') : __('profile.not_pic') }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (!$isInactive)
                        <div class="flex flex-wrap gap-2 mt-2 sm:mt-0">
                            <a href="{{ url('/riwayat-kamar') }}"
                               class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                                {{ __('profile.room_history_button') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- DATA PENGHUNI (LIST) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-4 sm:p-6">
                    <div class="text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('profile.resident_data') }}</div>

                    <dl class="mt-4 {{ $itemsClass }}">
                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.full_name') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->full_name ?? $user->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.email') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $user->email ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.phone') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->phone_number ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.address') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->address ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.resident_category') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->residentCategory?->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.citizenship') }}</dt>
                            <dd class="{{ $valueClass }}">
                                {{ $profile?->citizenship_status ?? '-' }}
                                @if (($profile?->citizenship_status ?? null) === 'WNA')
                                <span class="text-gray-600 dark:text-gray-400">({{ $profile?->country?->name ?? '-' }})</span>
                                @endif
                            </dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.national_id') }}l</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->national_id ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.school') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->university_school ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.student_id') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->student_id ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.gender') }}</dt>
                            <dd class="{{ $valueClass }}">
                                @php
                                $g = $profile?->gender;
                                echo $g === 'M' ? __('profile.gender_m') : ($g === 'F' ? __('profile.gender_f') : '-');
                                @endphp
                            </dd>
                        </div>

                        @if (!$isInactive)
                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Tanggal Masuk</dt>
                                <dd class="{{ $valueClass }}">{{ $checkInLabel }}</dd>
                            </div>
                        @else
                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Tanggal Keluar</dt>
                                <dd class="{{ $valueClass }}">{{ $checkOutLabel ?? '-' }}</dd>
                            </div>
                        @endif

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.check_in_date') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $checkInLabel }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- INFO ASRAMA (LIST) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-4 sm:p-6">
                    <div class="text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('profile.dorm_info') }}</div>

                    <dl class="mt-4 {{ $itemsClass }}">
                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.branch') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $dorm?->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.block') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $block?->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.room_code') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $roomCode }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.room_type') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $roomType?->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.room_pic') }}</dt>
                            <dd class="{{ $valueClass }}">
                                @if (! $assignment)
                                -
                                @elseif ($picName === '-')
                                {{ __('profile.pic_not_assigned') }}
                                @else
                                {{ $picName }} @if($isYouPic) <span class="text-green-600 dark:text-green-400 font-semibold">{{ __('profile.you') }}</span> @endif
                                @endif
                            </dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">{{ __('profile.status') }}</dt>
                            <dd class="{{ $valueClass }}">{{ $statusLabel }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Ubah Profile --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-4 sm:p-6">
                    <div class="text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('profile.update_profile') }}</div>
                    <div class="mt-3 sm:mt-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- Ubah Password --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-4 sm:p-6">
                    <div class="text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100">Ubah Password</div>
                    <div class="mt-3 sm:mt-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>