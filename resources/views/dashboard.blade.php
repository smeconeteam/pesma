<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Penghuni') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- CARD PENGHUNI --}}
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-green-100">
                <div class="p-5 sm:p-7">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="shrink-0">
                            @if (!empty($residentPhotoUrl))
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full blur opacity-40"></div>
                                    <img
                                        src="{{ $residentPhotoUrl }}"
                                        alt="Foto profil"
                                        class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-full object-cover border-3 border-white shadow-lg"
                                    />
                                </div>
                            @else
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full blur opacity-40"></div>
                                    <div class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 border-3 border-white shadow-lg flex items-center justify-center">
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
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white text-green-700 shadow-sm border border-green-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    Kamar {{ $roomCode }}
                                </span>
                                
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white text-green-700 shadow-sm border border-green-200">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center gap-2 text-xs sm:text-sm text-gray-700">
                                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">Masuk:</span>
                                <span class="font-bold text-gray-900">{{ $checkInDate }}</span>
                            </div>
                        </div>
                    </div>

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

                    @if (! $hasRoom)
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

            {{-- STATISTIK TAGIHAN --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Tagihan --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Tagihan</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $totalBills }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Belum Lunas --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Belum Lunas</p>
                            <p class="mt-2 text-2xl font-bold text-amber-600">{{ $unpaidBills }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Belum Dibayar --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Belum Dibayar</p>
                            <p class="mt-2 text-xl font-bold text-red-600">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Sudah Dibayar --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Sudah Dibayar</p>
                            <p class="mt-2 text-xl font-bold text-green-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAGIHAN YANG PERLU PERHATIAN --}}
            @if ($urgentBills->count() > 0)
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100">
                <div class="p-5 sm:p-7">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="p-2.5 bg-gradient-to-br from-red-500 to-orange-600 rounded-xl shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900">Tagihan yang Perlu Perhatian</h3>
                            <p class="text-xs sm:text-sm text-gray-600 mt-0.5">Segera lakukan pembayaran untuk tagihan berikut</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($urgentBills as $bill)
                        <div class="p-4 bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl border-2 {{ $bill->status === 'overdue' ? 'border-red-300' : 'border-amber-200' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-bold text-gray-900">{{ $bill->billingType->name }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                            {{ $bill->status === 'overdue' ? 'bg-red-100 text-red-800' : 
                                               ($bill->status === 'partial' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                            {{ $bill->status_label }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-2 text-sm text-gray-600">
                                        <div>No. Tagihan: <span class="font-semibold text-gray-900">{{ $bill->bill_number }}</span></div>
                                        @if ($bill->period_start && $bill->period_end)
                                        <div>Periode: {{ $bill->period_start->format('d M Y') }} - {{ $bill->period_end->format('d M Y') }}</div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex items-center gap-4 text-sm">
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

                                @if ($bill->status === 'overdue')
                                <div class="shrink-0">
                                    <div class="p-2 bg-red-100 rounded-lg">
                                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- DAFTAR SEMUA TAGIHAN --}}
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
                                <h3 class="text-base sm:text-lg font-bold text-gray-900">Daftar Tagihan</h3>
                                <p class="text-xs sm:text-sm text-gray-600 mt-0.5">Riwayat semua tagihan Anda</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('dashboard') }}" class="mb-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                                    <option value="unpaid" {{ $statusFilter === 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                                    <option value="issued" {{ $statusFilter === 'issued' ? 'selected' : '' }}>Tertagih</option>
                                    <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>Dibayar Sebagian</option>
                                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Lunas</option>
                                    <option value="overdue" {{ $statusFilter === 'overdue' ? 'selected' : '' }}>Jatuh Tempo</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                                <select name="year" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all" {{ $yearFilter === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                                    @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" {{ $yearFilter == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- Tabel Tagihan --}}
                    @if ($billsCollection->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">No. Tagihan</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Jenis</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Periode</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Dibayar</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Sisa</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($billsCollection as $bill)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $bill->bill_number }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-900">{{ $bill->billingType->name }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        @if ($bill->period_start && $bill->period_end)
                                            {{ $bill->period_start->format('d M Y') }} - {{ $bill->period_end->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold text-gray-900">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold text-green-600">Rp {{ number_format($bill->paid_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold {{ $bill->remaining_amount > 0 ? 'text-red-600' : 'text-gray-900' }}">
                                        Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                            {{ $bill->status === 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($bill->status === 'partial' ? 'bg-blue-100 text-blue-800' : 
                                               ($bill->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800')) }}">
                                            {{ $bill->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($totalPages > 1)
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                        <div class="text-sm text-gray-700">
                            Halaman <span class="font-semibold">{{ $currentPage }}</span> dari <span class="font-semibold">{{ $totalPages }}</span>
                        </div>
                        <div class="flex gap-2">
                            @if ($currentPage > 1)
                            <a href="{{ route('dashboard', array_merge(request()->except('page'), ['page' => $currentPage - 1])) }}" 
                               class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Sebelumnya
                            </a>
                            @endif
                            
                            @if ($currentPage < $totalPages)
                            <a href="{{ route('dashboard', array_merge(request()->except('page'), ['page' => $currentPage + 1])) }}" 
                               class="px-4 py-2 bg-blue-600 border border-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700">
                                Selanjutnya
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Tidak ada tagihan</h3>
                        <p class="mt-2 text-sm text-gray-500">Belum ada tagihan yang sesuai dengan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- KONTAK DARURAT (OPSIONAL) --}}
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