<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
            {{ __('payment-history.title') }}
        </h2>
    </x-slot>

    <div class="min-h-screen bg-gray-50 py-4 transition-colors duration-200 sm:py-8 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:space-y-6 sm:px-6 lg:px-8">

            {{-- STATISTIK PEMBAYARAN --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Total Pembayaran --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-md transition-colors transition-transform duration-200 hover:scale-105 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.total_payments') }}</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $totalPayments }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Semua pembayaran</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/30">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Terverifikasi --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-md transition-colors transition-transform duration-200 hover:scale-105 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.verified_payments') }}</p>
                            <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-500">{{ $verifiedPayments }}</p>
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">Sudah diverifikasi</p>
                        </div>
                        <div class="rounded-lg bg-green-50 p-3 dark:bg-green-900/30">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pending --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-md transition-colors transition-transform duration-200 hover:scale-105 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.pending_payments') }}</p>
                            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-500">{{ $pendingPayments }}</p>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Menunggu verifikasi</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/30">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Ditolak --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-md transition-colors transition-transform duration-200 hover:scale-105 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.rejected_payments') }}</p>
                            <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ $rejectedPayments }}</p>
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">Pembayaran ditolak</p>
                        </div>
                        <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/30">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DAFTAR PEMBAYARAN --}}
            <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg transition-colors sm:rounded-2xl dark:border-gray-700 dark:bg-gray-800">
                <div class="p-5 sm:p-7">
                    <div class="mb-6 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 p-2.5 shadow-md">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 sm:text-lg dark:text-gray-100">{{ __('payment-history.title') }}</h3>
                                <p class="mt-0.5 text-xs text-gray-600 sm:text-sm dark:text-gray-400">{{ __('payment-history.subtitle') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('resident.payment-history') }}" class="mb-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="mr-1 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    {{ __('payment-history.filter_status') }}
                                </label>
                                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 transition-colors focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('payment-history.all_status') }}</option>
                                    <option value="verified" {{ $statusFilter === 'verified' ? 'selected' : '' }}>✓ {{ __('payment-history.status_verified') }}</option>
                                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>⏱ {{ __('payment-history.status_pending') }}</option>
                                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>✕ {{ __('payment-history.status_rejected') }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <svg class="mr-1 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ __('payment-history.filter_year') }}
                                </label>
                                <select name="year" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 transition-colors focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="all" {{ $yearFilter === 'all' ? 'selected' : '' }}>{{ __('payment-history.all_years') }}</option>
                                    @foreach ($availableYears as $year)
                                        <option value="{{ $year }}" {{ $yearFilter == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- Tabel Pembayaran --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        No. Pembayaran
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Tagihan
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Tanggal
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Nominal
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Metode
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Status
                                    </th>
                                    <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($payments as $payment)
                                    <tr class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50" onclick="openPaymentModal({{ json_encode($payment) }})">
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="rounded bg-purple-100 p-1.5 dark:bg-purple-900/30">
                                                    <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $payment->payment_number }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        @if ($payment->is_pic_payment)
                                                            <span class="inline-flex items-center gap-1">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                                                                </svg>
                                                                PIC Kamar
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                Pribadi
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $payment->bill->bill_number }}</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $payment->bill->billingType->name }}</div>
                                                @if ($payment->bill->room)
                                                    <div class="text-xs font-medium text-purple-600 dark:text-purple-400">{{ $payment->bill->room->code }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('H:i') }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-base font-bold text-gray-900 dark:text-gray-100">
                                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="{{ $payment->paymentMethod->kind === 'qris' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }} {{ $payment->paymentMethod->kind === 'transfer' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }} {{ $payment->paymentMethod->kind === 'cash' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : '' }} inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold">
                                                @if ($payment->paymentMethod->kind === 'qris')
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h2v1H5zM3 13a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zm2 2v-1h2v1H5zM13 3a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V4a1 1 0 00-1-1h-4zm1 2v1h2V5h-2z" clip-rule="evenodd"></path>
                                                        <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"></path>
                                                    </svg>
                                                    QRIS
                                                @elseif($payment->paymentMethod->kind === 'transfer')
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                                                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Transfer
                                                @else
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Tunai
                                                @endif
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            @if ($payment->status === 'verified')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Terverifikasi
                                                </span>
                                            @elseif($payment->status === 'pending')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Menunggu
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Ditolak
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <button onclick="event.stopPropagation(); openPaymentModal({{ json_encode($payment) }})" class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 font-semibold text-white shadow-md transition-all hover:scale-105 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                Lihat Detail
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center space-y-3">
                                                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                                                    <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">{{ __('payment-history.no_payments') }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-500">Belum ada riwayat pembayaran</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($payments->hasPages())
                        <div class="mt-6">
                            {{ $payments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detail Pembayaran --}}
    <div id="paymentModal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-all" onclick="closePaymentModal(event)">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl dark:bg-gray-800" onclick="event.stopPropagation()">
            <div id="modalContent" class="p-6 sm:p-8"></div>
        </div>
    </div>

    <script>
        const payments = @json($payments->items());

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatDateShort(dateString) {
            return new Date(dateString).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function openPaymentModal(payment) {
            const modal = document.getElementById('paymentModal');
            const modalContent = document.getElementById('modalContent');

            const statusColors = {
                verified: {
                    bg: 'bg-green-100 dark:bg-green-900/30',
                    text: 'text-green-800 dark:text-green-300',
                    label: 'Terverifikasi'
                },
                pending: {
                    bg: 'bg-amber-100 dark:bg-amber-900/30',
                    text: 'text-amber-800 dark:text-amber-300',
                    label: 'Menunggu Verifikasi'
                },
                rejected: {
                    bg: 'bg-red-100 dark:bg-red-900/30',
                    text: 'text-red-800 dark:text-red-300',
                    label: 'Ditolak'
                }
            };

            const status = statusColors[payment.status] || statusColors.pending;

            modalContent.innerHTML = `
                <div class="space-y-6">
                    {{-- Header --}}
                    <div class="flex items-start justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Detail Pembayaran</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">${payment.payment_number}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold ${status.bg} ${status.text}">
                                ${payment.status === 'verified' ? `
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    ` : payment.status === 'pending' ? `
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    ` : `
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    `}
                                ${status.label}
                            </span>
                        </div>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold ${statusColor}">
                            ${payment.status === 'verified' ? 'Terverifikasi' : (payment.status === 'pending' ? 'Menunggu Verifikasi' : 'Ditolak')}
                        </span>
                    </div>

                    {{-- Informasi Tagihan --}}
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-800">
                        <h5 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Informasi Tagihan
                        </h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">No. Tagihan</p>
                                <p class="font-bold text-gray-900 dark:text-gray-100">${payment.bill.bill_number}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Jenis Tagihan</p>
                                <p class="font-bold text-gray-900 dark:text-gray-100">${payment.bill.billing_type.name}</p>
                            </div>
                            ${payment.bill.room ? `
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Kamar</p>
                                    <p class="font-bold text-purple-600 dark:text-purple-400">${payment.bill.room.code}</p>
                                </div>
                                ` : ''}
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total Tagihan</p>
                                <p class="font-bold text-gray-900 dark:text-gray-100">${formatCurrency(payment.bill.total_amount)}</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Detail Pembayaran --}}
                    <div class="space-y-4">
                        <h5 class="font-bold text-gray-900 dark:text-gray-100 text-lg flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Detail Pembayaran
                        </h5>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-5 space-y-3 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Tanggal Pembayaran
                                </span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${formatDateShort(payment.payment_date)}</span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Nominal Dibayar
                                </span>
                                <span class="font-bold text-xl text-green-600 dark:text-green-400">${formatCurrency(payment.amount)}</span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Metode Pembayaran
                                </span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${
                                    payment.payment_method ? 
                                        (payment.payment_method.kind === 'qris' ? 'QRIS' : 
                                        (payment.payment_method.kind === 'transfer' ? 'Transfer Bank' : 
                                        (payment.payment_method.kind === 'cash' ? 'Tunai' : payment.payment_method.kind))) 
                                    : '-'
                                }</span>
                            </div>
                            <div class="flex items-center justify-between py-3 ${payment.verified_at ? 'border-b border-gray-200 dark:border-gray-600' : ''}">
                                <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Tipe Pembayaran
                                </span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${payment.is_pic_payment ? '👥 PIC Kamar (Gabungan)' : '👤 Pribadi'}</span>
                            </div>
                            ${payment.verified_at ? `
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Diverifikasi Pada
                                    </span>
                                    <span class="font-bold text-gray-900 dark:text-gray-100">${formatDate(payment.verified_at)}</span>
                                </div>
                                ` : ''}
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.payment_method') }}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">${
                                payment.payment_method ? 
                                    (payment.payment_method.kind === 'qris' ? 'QRIS' : 
                                    (payment.payment_method.kind === 'transfer' ? 'Transfer Bank' : 
                                    (payment.payment_method.kind === 'cash' ? 'Tunai' : payment.payment_method.kind))) 
                                : '-'
                            }</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.payment_type') }}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">${payment.is_pic_payment ? 'PIC (Gabungan)' : 'Pribadi'}</span>
                        </div>
                        ${payment.verified_at ? `
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.verified_at') }}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">${formatDate(payment.verified_at)}</span>
                        </div>
                        ` : ''}
                    
                    ${payment.rejection_reason ? `
                        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-5">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <h6 class="font-bold text-red-900 dark:text-red-200 mb-2">Alasan Penolakan</h6>
                                    <p class="text-sm text-red-800 dark:text-red-300 leading-relaxed">${payment.rejection_reason}</p>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    
                    ${payment.proof_path ? `
                        <div class="space-y-3">
                            <h6 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Bukti Pembayaran
                            </h6>
                            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-lg">
                                <img src="${payment.proof_url}" alt="Bukti Pembayaran" class="w-full h-auto">
                            </div>
                        </div>
                        ` : ''}
                    
                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700 gap-3">
                        ${payment.status === 'verified' ? `
                            <a href="{{ url('/receipt') }}/${payment.id}" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition-all hover:scale-105 shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 002 2zm-6 9h6m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0h6"></path>
                                </svg>
                                Cetak Nota
                            </a>
                            ` : ''}
                        <button onclick="closePaymentModal()" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg font-semibold transition-all hover:scale-105">
                            Tutup
                        </button>
                    </div>
                </div>
                ` : ''}
                
                ${payment.proof_path ? `
                <div class="space-y-2">
                    <h6 class="font-bold text-gray-900 dark:text-gray-100">{{ __('payment-history.proof') }}</h6>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <img src="${payment.proof_url}" alt="Bukti Pembayaran" class="w-full h-auto">
                    </div>
                </div>
                ` : ''}
                
                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700 gap-3">
                    ${payment.status === 'verified' ? `
                    <a href="{{ url('/receipt') }}/${payment.id}" target="_blank" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 002 2zm-6 9h6m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0h6"></path>
                        </svg>
                        Download Nota
                    </a>
                    ` : ''}
                    <button onclick="closePaymentModal()" class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors">
                        {{ __('payment-history.close') }}
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePaymentModal(event) {
            if (event && event.target.id !== 'paymentModal') return;
            const modal = document.getElementById('paymentModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
            }
        });
    </script>
</x-app-layout>
