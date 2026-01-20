<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Penghuni') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- CARD PENGHUNI --}}
            <div class="bg-gradient-to-br {{ $isInactive ? 'from-gray-50 to-slate-50' : 'from-green-50 to-emerald-50' }} overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border {{ $isInactive ? 'border-gray-200' : 'border-green-100' }}">
                <div class="p-5 sm:p-7">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="shrink-0">
                            @if (!empty($residentPhotoUrl))
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br {{ $isInactive ? 'from-gray-400 to-slate-500' : 'from-green-400 to-emerald-500' }} rounded-full blur opacity-40"></div>
                                    <img
                                        src="{{ $residentPhotoUrl }}"
                                        alt="Foto profil"
                                        class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-full object-cover border-3 border-white shadow-lg {{ $isInactive ? 'grayscale' : '' }}"
                                    />
                                </div>
                            @else
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br {{ $isInactive ? 'from-gray-400 to-slate-500' : 'from-green-400 to-emerald-500' }} rounded-full blur opacity-40"></div>
                                    <div class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-br {{ $isInactive ? 'from-gray-500 to-slate-600' : 'from-green-500 to-emerald-600' }} border-3 border-white shadow-lg flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl font-bold text-white">
                                            {{ mb_substr($residentName ?? 'U', 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="text-lg sm:text-xl font-bold text-gray-900 break-words">
                                {{ $residentName }}
                            </div>

                            <div class="mt-2.5 flex flex-wrap gap-2">
                                @if (!$isInactive)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white text-green-700 shadow-sm border border-green-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                        Kamar {{ $roomCode }}
                                    </span>
                                @endif
                                
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white shadow-sm border {{ $isInactive ? 'text-gray-700 border-gray-200' : 'text-green-700 border-green-200' }}">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            @if (!$isInactive && $checkInDate !== '-')
                                <div class="mt-3 flex items-center gap-2 text-xs sm:text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="font-medium">Masuk:</span>
                                    <span class="font-bold text-gray-900">{{ $checkInDate }}</span>
                                </div>
                            @endif

                            @if ($isInactive && $lastCheckout)
                                <div class="mt-3 flex items-center gap-2 text-xs sm:text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="font-medium">Keluar:</span>
                                    <span class="font-bold text-gray-900">{{ $lastCheckout }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!$isInactive)
                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <a href="{{ route('resident.my-room') }}"
                               class="inline-flex items-center justify-center gap-2 px-3 py-3 bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl font-bold text-xs text-white uppercase tracking-wide hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-md hover:shadow-lg active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <span>Kamar</span>
                            </a>

                            <a href="{{ route('resident.room-history') }}"
                               class="inline-flex items-center justify-center gap-2 px-3 py-3 bg-white border-2 border-green-200 rounded-xl font-bold text-xs text-green-700 uppercase tracking-wide hover:bg-green-50 hover:border-green-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-md hover:shadow-lg active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Riwayat</span>
                            </a>
                        </div>
                    @else
                        <div class="mt-6">
                            <a href="{{ route('resident.room-history') }}"
                               class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-white border-2 border-gray-200 rounded-xl font-bold text-xs text-gray-700 uppercase tracking-wide hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all shadow-md hover:shadow-lg active:scale-95 w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Lihat Riwayat Kamar</span>
                            </a>
                        </div>
                    @endif

                    @if ($isInactive)
                        <div class="mt-5 rounded-xl border-2 border-red-200 bg-gradient-to-br from-red-50 to-rose-50 p-4 shadow-sm">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <div class="font-bold text-red-900 mb-1">Status: Dikeluarkan dari Asrama</div>
                                    @if ($checkoutReason)
                                        <div class="text-xs text-red-800 leading-relaxed">
                                            <span class="font-semibold">Alasan:</span> {{ $checkoutReason }}
                                        </div>
                                    @else
                                        <div class="text-xs text-red-800 leading-relaxed">
                                            Anda telah dikeluarkan dari asrama. Silakan hubungi admin untuk informasi lebih lanjut.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif (!$hasRoom)
                        <div class="mt-5 rounded-xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50 p-4 shadow-sm">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <div class="font-bold text-amber-900 mb-1">Belum Ada Kamar</div>
                                    <div class="text-xs text-amber-800 leading-relaxed">Kamu belum memiliki penempatan kamar. Silakan hubungi admin asrama jika ini tidak sesuai.</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 3 TAGIHAN TERBARU --}}
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100">
                <div class="p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-900">Tagihan Terbaru</h3>
                                <p class="text-xs sm:text-sm text-gray-600 mt-0.5">Tagihan terakhir Anda</p>
                            </div>
                        </div>
                        <a href="{{ route('resident.bills') }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                            Lihat Semua
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>

                    @if ($recentBills->count() > 0)
                    <div class="space-y-3">
                        @foreach ($recentBills as $bill)
                        <div class="p-4 bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl border-2 border-gray-200 hover:border-blue-300 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-bold text-gray-900">{{ $bill->billingType->name }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                            {{ $bill->status === 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($bill->status === 'partial' ? 'bg-blue-100 text-blue-800' : 
                                               ($bill->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800')) }}">
                                            {{ $bill->status_label }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-2 text-sm text-gray-600">
                                        <div>No. Tagihan: <span class="font-semibold text-gray-900">{{ $bill->bill_number }}</span></div>
                                        @if ($bill->period_start && $bill->period_end)
                                        <div>Periode: {{ $bill->period_start->format('d M Y') }} - {{ $bill->period_end->format('d M Y') }}</div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex items-center gap-4 text-sm flex-wrap">
                                        <div>
                                            <span class="text-gray-600">Total:</span>
                                            <span class="font-bold text-gray-900">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</span>
                                        </div>
                                        @if ($bill->status !== 'issued')
                                        <div>
                                            <span class="text-gray-600">Dibayar:</span>
                                            <span class="font-bold text-green-600">Rp {{ number_format($bill->paid_amount, 0, ',', '.') }}</span>
                                        </div>
                                        @endif
                                        @if ($bill->remaining_amount > 0)
                                        <div>
                                            <span class="text-gray-600">Sisa:</span>
                                            <span class="font-bold text-red-600">Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Tidak ada tagihan</h3>
                        <p class="mt-2 text-sm text-gray-500">Belum ada tagihan yang tercatat.</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- KONTAK PENTING --}}
            @if ($contacts->count() > 0)
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100">
                <div class="p-5 sm:p-7">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="p-2.5 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900">Kontak Penting</h3>
                            <p class="text-xs sm:text-sm text-gray-600 mt-0.5">Hubungi jika ada keperluan darurat</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($contacts as $contact)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->phone) }}{{ $contact->auto_message ? '?text=' . urlencode($contact->auto_message) : '' }}" 
                           target="_blank"
                           class="flex items-center gap-3 p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border-2 border-green-200 hover:border-green-300 hover:shadow-md transition-all">
                            <div class="p-2.5 bg-green-600 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-900">{{ $contact->display_name }}</div>
                                <div class="text-sm text-gray-600">{{ $contact->phone }}</div>
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