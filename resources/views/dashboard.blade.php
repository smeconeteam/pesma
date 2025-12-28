<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Penghuni') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- CARD PENGHUNI --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div class="flex items-center gap-4">
                            {{-- Foto Profil --}}
                            <div class="shrink-0">
                                @if (!empty($residentPhotoUrl))
                                    <img
                                        src="{{ $residentPhotoUrl }}"
                                        alt="Foto profil"
                                        class="h-14 w-14 rounded-full object-cover border"
                                    />
                                @else
                                    <div class="h-14 w-14 rounded-full bg-gray-100 border flex items-center justify-center">
                                        <span class="text-lg font-semibold text-gray-600">
                                            {{ mb_substr($residentName ?? 'U', 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold text-gray-900">
                                        {{ $residentName }}
                                    </div>

                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border">
                                        Kamar: {{ $roomCode }}
                                    </span>
                                </div>

                                <div class="mt-1 text-sm text-gray-600">
                                    Status: <span class="font-semibold text-gray-900">{{ $statusLabel }}</span>
                                    <span class="mx-2">•</span>
                                    Check-in: <span class="font-semibold text-gray-900">{{ $checkInDate }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol cepat --}}
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

                    @if (! $hasRoom)
                        <div class="mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                            Kamu belum memiliki penempatan kamar saat ini. Silakan hubungi admin asrama jika ini tidak sesuai.
                        </div>
                    @endif
                </div>
            </div>

            {{-- CARD PROFIL PIC --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-base font-semibold text-gray-900">Profil PIC</div>
                            <div class="mt-1 text-sm text-gray-600">
                                PIC adalah penanggung jawab kamar (mis. koordinasi informasi dan laporan).
                            </div>
                        </div>

                        @if ($isYouPic)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                Kamu adalah PIC
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center gap-4">
                        {{-- Foto PIC --}}
                        <div class="shrink-0">
                            @if (!empty($picPhotoUrl))
                                <img
                                    src="{{ $picPhotoUrl }}"
                                    alt="Foto PIC"
                                    class="h-12 w-12 rounded-full object-cover border"
                                />
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
                                {{ $hasRoom ? $picName : '-' }}
                            </div>
                            <div class="text-sm text-gray-600">
                                @if (! $hasRoom)
                                    PIC akan muncul setelah kamu ditempatkan ke kamar.
                                @else
                                    @if ($picName === '-' )
                                        PIC belum ditetapkan untuk kamar ini.
                                    @else
                                        Hubungi PIC untuk koordinasi kebutuhan kamar.
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        * Data PIC diambil dari penghuni kamar aktif yang ditandai sebagai PIC.
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
