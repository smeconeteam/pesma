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

        $itemsClass = "divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white overflow-hidden";
        $rowClass = "px-3 py-3 sm:px-4 sm:py-3 flex flex-col sm:grid sm:grid-cols-3 sm:gap-4 gap-1";
        $labelClass = "text-xs sm:text-sm font-medium text-gray-500";
        $valueClass = "text-sm sm:text-sm text-gray-900 font-medium";
    @endphp

    <div class="py-4 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (! $assignment)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div class="rounded-md border border-yellow-200 bg-yellow-50 p-3 sm:p-4 text-xs sm:text-sm text-yellow-800">
                            Kamu belum memiliki penempatan kamar aktif.
                        </div>
                    </div>
                </div>
            @else
                {{-- Informasi Kamar --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div class="text-sm sm:text-base font-semibold text-gray-900 mb-3 sm:mb-4">Informasi Kamar</div>

                        <dl class="{{ $itemsClass }}">
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
                                <dt class="{{ $labelClass }}">Tanggal Masuk</dt>
                                <dd class="{{ $valueClass }}">{{ $checkInLabel }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 sm:mt-4 text-xs text-gray-500">
                            * Data diambil dari penempatan kamar aktif (belum checkout).
                        </div>
                    </div>
                </div>

                {{-- Penghuni Sekamar --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div class="text-sm sm:text-base font-semibold text-gray-900 mb-3 sm:mb-4">Penghuni Sekamar</div>

                        <div class="space-y-2 sm:space-y-3">
                            @foreach ($roommates as $rr)
                                @php
                                    $u = $rr->user;
                                    $name = $u?->residentProfile?->full_name ?: ($u?->name ?? '-');

                                    $photoUrl = null;
                                    if ($u?->residentProfile?->photo_path) {
                                        $photoUrl = \Illuminate\Support\Facades\Storage::url($u->residentProfile->photo_path);
                                    }

                                    $phoneNumber = $u?->residentProfile?->phone_number;
                                    $isMe = $u?->id && $u->id === auth()->id();
                                @endphp

                                <div class="p-3 sm:p-4 rounded-lg border border-gray-200 bg-gray-50">
                                    <div class="flex items-center gap-2.5 sm:gap-3">
                                        @if ($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="Foto"
                                                 class="h-10 w-10 rounded-full object-cover border-2 border-white shadow-sm shrink-0" />
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-200 border-2 border-white shadow-sm flex items-center justify-center shrink-0">
                                                <span class="text-sm font-semibold text-gray-600">
                                                    {{ mb_substr($name ?? '-', 0, 1) }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                                    <span class="text-sm font-semibold text-gray-900 truncate">
                                                        {{ $name }}
                                                    </span>
                                                    
                                                    @if ($isMe)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
                                                            Saya
                                                        </span>
                                                    @endif
                                                    
                                                    @if ($rr->is_pic)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200 shrink-0">
                                                            PIC
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Tombol WhatsApp satu baris dengan nama --}}
                                                @if (!$isMe && !empty($phoneNumber))
                                                    @php
                                                        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
                                                        if (substr($cleanPhone, 0, 1) === '0') {
                                                            $cleanPhone = '62' . substr($cleanPhone, 1);
                                                        }
                                                        if (substr($cleanPhone, 0, 2) !== '62') {
                                                            $cleanPhone = '62' . $cleanPhone;
                                                        }
                                                    @endphp
                                                    
                                                    <a href="https://wa.me/{{ $cleanPhone }}" 
                                                       target="_blank"
                                                       title="Hubungi via WhatsApp"
                                                       class="inline-flex items-center justify-center p-1.5 sm:p-2 bg-green-500 hover:bg-green-600 rounded-lg transition-colors shadow-sm hover:shadow active:scale-95 shrink-0">
                                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>

                                            @if (!empty($phoneNumber))
                                                <div class="mt-1 flex items-center gap-1.5 text-xs sm:text-sm text-gray-600">
                                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                                    </svg>
                                                    <span class="font-medium truncate">{{ $phoneNumber }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if ($roommates->isEmpty())
                                <div class="p-3 sm:p-4 rounded-lg border border-gray-200 bg-gray-50 text-xs sm:text-sm text-gray-600">
                                    Tidak ada data penghuni sekamar.
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 sm:mt-4 text-xs text-gray-500">
                            * Hanya menampilkan penghuni aktif di kamar yang sama.
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
