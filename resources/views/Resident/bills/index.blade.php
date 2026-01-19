<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tagihan Saya') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

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

            {{-- TAGIHAN YANG PERLU DIBAYAR --}}
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
                            <h3 class="text-base sm:text-lg font-bold text-gray-900">Tagihan yang Perlu Dibayar</h3>
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

                                    <div class="mt-3 flex items-center gap-4 text-sm flex-wrap">
                                        <div>
                                            <span class="text-gray-600">Total:</span>
                                            <span class="font-bold text-gray-900">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</span>
                                        </div>
                                        @if ($bill->status !== 'issued')
                                        <div>
                                            <span class="text-gray-600">Terbayar:</span>
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

            {{-- DAFTAR TAGIHAN --}}
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
                                <p class="text-xs sm:text-sm text-gray-600 mt-0.5">Riwayat lengkap tagihan Anda</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('resident.bills') }}" class="mb-6">
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
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Terbayar</th>
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
                            <a href="{{ route('resident.bills', array_merge(request()->except('page'), ['page' => $currentPage - 1])) }}" 
                               class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Sebelumnya
                            </a>
                            @endif
                            
                            @if ($currentPage < $totalPages)
                            <a href="{{ route('resident.bills', array_merge(request()->except('page'), ['page' => $currentPage + 1])) }}" 
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

        </div>
    </div>
</x-app-layout>
