<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profil Penghuni') }}
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
        $room     = $assignment?->room;
        $block    = $room?->block;
        $dorm     = $block?->dorm;
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

        // helper untuk list item
        $itemsClass = "divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white";
        $rowClass = "px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6";
        $labelClass = "text-sm font-medium text-gray-500";
        $valueClass = "mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0";
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Ringkasan --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex items-center gap-4">
                        <div class="shrink-0">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Foto Profil"
                                     class="h-16 w-16 rounded-full object-cover border" />
                            @else
                                <div class="h-16 w-16 rounded-full bg-gray-100 border flex items-center justify-center">
                                    <span class="text-xl font-semibold text-gray-600">
                                        {{ mb_substr($profile?->full_name ?? $user->name ?? 'U', 0, 1) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ $profile?->full_name ?? $user->name ?? '-' }}
                            </div>
                            <div class="mt-1 text-sm text-gray-600">
                                {{ $user->email ?? '-' }}
                                <span class="mx-2">•</span>
                                Status: <span class="font-semibold text-gray-900">{{ $statusLabel }}</span>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border">
                                    Kode Kamar: {{ $roomCode }}
                                </span>

                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border">
                                    Check-in: {{ $checkInLabel }}
                                </span>

                                @if ($assignment)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $assignment->is_pic ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-800 border' }} border">
                                        {{ $assignment->is_pic ? 'Kamu PIC' : 'Bukan PIC' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ url('/kontak') }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                            Kontak Admin
                        </a>

                        <a href="{{ url('/riwayat-kamar') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                            Riwayat Kamar
                        </a>
                    </div>

                </div>
            </div>

            {{-- DATA PENGHUNI (LIST) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Data Penghuni</div>

                    <dl class="mt-4 {{ $itemsClass }}">
                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Nama Lengkap</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->full_name ?? $user->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Email</dt>
                            <dd class="{{ $valueClass }}">{{ $user->email ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">No. HP</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->phone_number ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Kategori Penghuni</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->residentCategory?->name ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Kewarganegaraan</dt>
                            <dd class="{{ $valueClass }}">
                                {{ $profile?->citizenship_status ?? '-' }}
                                @if (($profile?->citizenship_status ?? null) === 'WNA')
                                    <span class="text-gray-600">({{ $profile?->country?->name ?? '-' }})</span>
                                @endif
                            </dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">NIK / ID Nasional</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->national_id ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">NIM / ID Mahasiswa</dt>
                            <dd class="{{ $valueClass }}">{{ $profile?->student_id ?? '-' }}</dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Jenis Kelamin</dt>
                            <dd class="{{ $valueClass }}">
                                @php
                                    $g = $profile?->gender;
                                    echo $g === 'M' ? 'Laki-laki' : ($g === 'F' ? 'Perempuan' : '-');
                                @endphp
                            </dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Tanggal Check-in</dt>
                            <dd class="{{ $valueClass }}">{{ $checkInLabel }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- INFO ASRAMA (LIST) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Info Asrama</div>

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

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">PIC Kamar</dt>
                            <dd class="{{ $valueClass }}">
                                @if (! $assignment)
                                    -
                                @elseif ($picName === '-')
                                    PIC belum ditetapkan
                                @else
                                    {{ $picName }} @if($isYouPic) <span class="text-green-600 font-semibold">(Kamu)</span> @endif
                                @endif
                            </dd>
                        </div>

                        <div class="{{ $rowClass }}">
                            <dt class="{{ $labelClass }}">Status</dt>
                            <dd class="{{ $valueClass }}">{{ $statusLabel }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Ubah Profile --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Ubah Profil</div>
                    <div class="mt-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- Ubah Password --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Ubah Password</div>
                    <div class="mt-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
