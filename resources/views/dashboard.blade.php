<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight transition-colors duration-200">
            {{ __('dashboard.title') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- CARD PENGHUNI --}}
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-900 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-green-100 dark:border-gray-700 transition-colors duration-200">
                <div class="p-5 sm:p-7">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="shrink-0">
                            @if (!empty($residentPhotoUrl))
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full blur opacity-40"></div>
                                    <img
                                        src="{{ $residentPhotoUrl }}"
                                        alt="Foto profil"
                                        class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-full object-cover border-3 border-white dark:border-gray-800 shadow-lg"
                                    />
                                </div>
                            @else
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full blur opacity-40"></div>
                                    <div class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 border-3 border-white dark:border-gray-800 shadow-lg flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl font-bold text-white">
                                            {{ mb_substr($residentName ?? 'U', 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100 break-words transition-colors duration-200">
                                {{ $residentName }}
                            </div>

                            <div class="mt-2.5 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white dark:bg-gray-800 text-green-700 dark:text-green-400 shadow-sm border border-green-200 dark:border-green-700 transition-colors duration-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    {{ __('dashboard.room', ['code' => $roomCode]) }}
                                </span>
                                
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white dark:bg-gray-800 text-green-700 dark:text-green-400 shadow-sm border border-green-200 dark:border-green-700 transition-colors duration-200">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center gap-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300 transition-colors duration-200">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">{{ __('dashboard.check_in') }}:</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ $checkInDate }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <a href="{{ route('resident.my-room') }}"
                           class="inline-flex items-center justify-center gap-2 px-3 py-3 bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl font-bold text-xs text-white uppercase tracking-wide hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all shadow-md hover:shadow-lg active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>{{ __('dashboard.my_room') }}</span>
                        </a>

                        <a href="{{ route('resident.room-history') }}"
                           class="inline-flex items-center justify-center gap-2 px-3 py-3 bg-white dark:bg-gray-800 border-2 border-green-200 dark:border-green-700 rounded-xl font-bold text-xs text-green-700 dark:text-green-400 uppercase tracking-wide hover:bg-green-50 dark:hover:bg-gray-700 hover:border-green-300 dark:hover:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all shadow-md hover:shadow-lg active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ __('dashboard.room_history') }}</span>
                        </a>
                    </div>

                    @if (! $hasRoom)
                        <div class="mt-5 rounded-xl border-2 border-amber-200 dark:border-amber-800 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 p-4 shadow-sm transition-colors duration-200">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <div class="font-bold text-sm text-amber-900 dark:text-amber-200">{{ __('dashboard.no_room_title') }}</div>
                                    <div class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                                        {{ __('dashboard.no_room_message') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- CARD PIC KAMAR --}}
            @if (!empty($picInfo))
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                    <div class="p-5 sm:p-7">
                        {{-- Header --}}
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2.5 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl shadow-md">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100 transition-colors duration-200">{{ __('dashboard.pic_title') }}</h3>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">Hubungi PIC untuk koordinasi kebutuhan kamar</p>
                            </div>
                        </div>

                        {{-- Info PIC --}}
                        @php
                            // Bersihkan nomor telepon dari karakter non-angka
                            $cleanPicPhone = preg_replace('/[^0-9]/', '', $picInfo['phone']);
                            
                            // Jika nomor diawali dengan 0, ganti dengan 62
                            if (substr($cleanPicPhone, 0, 1) === '0') {
                                $cleanPicPhone = '62' . substr($cleanPicPhone, 1);
                            }
                            
                            // Jika tidak diawali 62, tambahkan 62
                            if (substr($cleanPicPhone, 0, 2) !== '62') {
                                $cleanPicPhone = '62' . $cleanPicPhone;
                            }
                            
                            // Buat URL WhatsApp
                            $picWaUrl = 'https://wa.me/' . $cleanPicPhone;
                        @endphp

                        <a href="{{ $picWaUrl }}" 
                           target="_blank"
                           class="block p-4 bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 hover:shadow-md transition-all group">
                            <div class="flex items-center gap-4">
                                {{-- Icon --}}
                                <div class="shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Info PIC --}}
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-sm sm:text-base text-gray-900 dark:text-gray-100 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">
                                        {{ $picInfo['name'] }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                        <span class="font-medium">{{ $picInfo['phone'] }}</span>
                                    </div>
                                </div>

                                {{-- Arrow Icon --}}
                                <div class="shrink-0">
                                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-green-600 dark:group-hover:text-green-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                    <div class="p-5 sm:p-7">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2.5 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl shadow-md">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">PIC Kamar</h3>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">Hubungi PIC untuk koordinasi kebutuhan kamar</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-center py-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">PIC akan muncul setelah kamu ditempatkan ke kamar</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- CARD DAFTAR KONTAK --}}
            @if (!empty($contacts) && count($contacts) > 0)
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                    <div class="p-5 sm:p-7">
                        {{-- Header --}}
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2.5 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100 transition-colors duration-200">Kontak Penting</h3>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">Hubungi admin untuk bantuan dan informasi</p>
                            </div>
                        </div>

                        {{-- Daftar Kontak --}}
                        <div class="space-y-3">
                            @foreach ($contacts as $contact)
                                @php
                                    $cleanContactPhone = preg_replace('/[^0-9]/', '', $contact->phone);
                                    
                                    if (substr($cleanContactPhone, 0, 1) === '0') {
                                        $cleanContactPhone = '62' . substr($cleanContactPhone, 1);
                                    }
                                    
                                    if (substr($cleanContactPhone, 0, 2) !== '62') {
                                        $cleanContactPhone = '62' . $cleanContactPhone;
                                    }
                                    
                                    $waUrl = 'https://wa.me/' . $cleanContactPhone;
                                    if (!empty($contact->auto_message)) {
                                        $waUrl .= '?text=' . urlencode($contact->auto_message);
                                    }
                                @endphp
                                
                                <a href="{{ $waUrl }}" 
                                target="_blank"
                                class="block p-4 bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 hover:shadow-md transition-all group">
                                    <div class="flex items-center gap-4">
                                        {{-- Icon --}}
                                        <div class="shrink-0">
                                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                            </div>
                                        </div>

                                        {{-- Info Kontak --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-sm sm:text-base text-gray-900 dark:text-gray-100 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">
                                                {{ $contact->display_name }}
                                            </div>
                                            <div class="mt-1 flex items-center gap-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                                <span class="font-medium">{{ $contact->phone }}</span>
                                            </div>
                                            @if (!empty($contact->name))
                                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $contact->name }}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Arrow Icon --}}
                                        <div class="shrink-0">
                                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-green-600 dark:group-hover:text-green-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>