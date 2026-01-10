<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kamar Saya') }}
        </h2>
    </x-slot>

    @php
        $room     = $assignment?->room;
        $block    = $room?->block;
        $dorm     = $block?->dorm;
        $roomType = $room?->roomType;

        $roomCode = $room?->code
            ?? $room?->room_code
            ?? $room?->name
            ?? $room?->number
            ?? '-';

        $checkIn = $assignment?->check_in_date;
        $checkInLabel = $checkIn
            ? (method_exists($checkIn, 'format') ? $checkIn->format('d M Y') : date('d M Y', strtotime((string) $checkIn)))
            : '-';

        $capacity = $room?->capacity ?? $room?->default_capacity ?? null;

        $itemsClass = "divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white";
        $rowClass = "px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6";
        $labelClass = "text-sm font-medium text-gray-500";
        $valueClass = "mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0";
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (! $assignment)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                            Kamu belum memiliki penempatan kamar aktif.
                        </div>
                    </div>
                </div>
            @else
                {{-- Informasi Kamar --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-base font-semibold text-gray-900">Informasi Kamar</div>

                        <dl class="mt-4 {{ $itemsClass }}">
                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Cabang</dt>
                                <dd class="{{ $valueClass }}">{{ $dorm?->name ?? '-' }}</dd>
                            </div>

                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Komplek</dt>
                                <dd class="{{ $valueClass }}">{{ $block?->name ?? '-' }}</dd>
                            </div>

                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Kode Kamar</dt>
                                <dd class="{{ $valueClass }}">{{ $roomCode }}</dd>
                            </div>

                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Tipe Kamar</dt>
                                <dd class="{{ $valueClass }}">{{ $roomType?->name ?? '-' }}</dd>
                            </div>

                            @if (! is_null($capacity))
                                <div class="{{ $rowClass }}">
                                    <dt class="{{ $labelClass }}">Kapasitas</dt>
                                    <dd class="{{ $valueClass }}">{{ $capacity }} orang</dd>
                                </div>
                            @endif

                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Status PIC Saya</dt>
                                <dd class="{{ $valueClass }}">
                                    {{ $assignment->is_pic ? 'PIC' : 'Bukan PIC' }}
                                </dd>
                            </div>

                            <div class="{{ $rowClass }}">
                                <dt class="{{ $labelClass }}">Tanggal Check-in</dt>
                                <dd class="{{ $valueClass }}">{{ $checkInLabel }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 text-xs text-gray-500">
                            * Data diambil dari penempatan kamar aktif (belum checkout).
                        </div>
                    </div>
                </div>

                {{-- Profil PIC --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-base font-semibold text-gray-900">Profil PIC</div>

                        @php
                            $picUser = $picAssignment?->user;
                            $picName = $picUser
                                ? ($picUser->residentProfile?->full_name ?: $picUser->name)
                                : '-';

                            $picPhotoUrl = null;
                            if ($picUser?->residentProfile?->photo_path) {
                                $picPhotoUrl = \Illuminate\Support\Facades\Storage::url($picUser->residentProfile->photo_path);
                            }

                            $isYouPic = $picUser?->id && $picUser->id === auth()->id();
                        @endphp

                        <div class="mt-4 flex items-center gap-4">
                            <div class="shrink-0">
                                @if ($picPhotoUrl)
                                    <img src="{{ $picPhotoUrl }}" alt="Foto PIC"
                                         class="h-12 w-12 rounded-full object-cover border" />
                                @else
                                    <div class="h-12 w-12 rounded-full bg-gray-100 border flex items-center justify-center">
                                        <span class="text-base font-semibold text-gray-600">
                                            {{ mb_substr($picName ?? '-', 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ $picName }}
                                    @if ($isYouPic)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                            Kamu
                                        </span>
                                    @endif
                                </div>

                                <div class="text-sm text-gray-600">
                                    @if ($picName === '-')
                                        PIC belum ditetapkan untuk kamar ini.
                                    @else
                                        PIC adalah penanggung jawab kamar untuk koordinasi informasi dan laporan.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Penghuni Sekamar --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-base font-semibold text-gray-900">Penghuni Sekamar</div>

                        <div class="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-200">
                            @foreach ($roommates as $rr)
                                @php
                                    $u = $rr->user;
                                    $name = $u?->residentProfile?->full_name ?: ($u?->name ?? '-');

                                    $photoUrl = null;
                                    if ($u?->residentProfile?->photo_path) {
                                        $photoUrl = \Illuminate\Support\Facades\Storage::url($u->residentProfile->photo_path);
                                    }

                                    $isMe = $u?->id && $u->id === auth()->id();
                                @endphp

                                <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        @if ($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="Foto"
                                                 class="h-10 w-10 rounded-full object-cover border" />
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-100 border flex items-center justify-center">
                                                <span class="text-sm font-semibold text-gray-600">
                                                    {{ mb_substr($name ?? '-', 0, 1) }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $name }}
                                            @if ($isMe)
                                                <span class="ml-2 text-xs text-green-700 font-semibold">(Saya)</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if ($rr->is_pic)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                                PIC
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if ($roommates->isEmpty())
                                <div class="px-4 py-4 sm:px-6 text-sm text-gray-600">
                                    Tidak ada data penghuni sekamar.
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 text-xs text-gray-500">
                            * Hanya menampilkan penghuni aktif di kamar yang sama.
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
