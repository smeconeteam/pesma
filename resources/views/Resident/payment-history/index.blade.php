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
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('payment-history.all_payments_desc') }}</p>
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
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">{{ __('payment-history.verified_desc') }}</p>
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
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('payment-history.pending_desc') }}</p>
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
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ __('payment-history.rejected_desc') }}</p>
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
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.payment_number') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.bill_number') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.payment_type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.payment_date') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.amount') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($paymentsCollection as $payment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors" onclick="openPaymentModal({{ $payment->id }})">
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $payment->payment_number }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $payment->bill->bill_number }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $payment->bill->billingType->name }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $payment->payment_date->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                            {{ $payment->status === 'verified' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                               ($payment->status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                            {{ $payment->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center justify-start gap-2">
                                            <button onclick="event.stopPropagation(); openPaymentModal({{ $payment->id }})" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors" 
                                                title="{{ __('payment-history.view_detail') }}">
                                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                {{ __('payment-history.detail') }}
                                            </button>
                                            @if ($payment->status === 'verified')
                                            <a href="{{ url('/receipt') }}/{{ $payment->id }}" target="_blank" onclick="event.stopPropagation()"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-xs font-medium text-blue-700 shadow-sm hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors"
                                                title="{{ __('payment-history.print_receipt') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 002 2zm-6 9h6m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0h6" />
                                                </svg>
                                                {{ __('payment-history.receipt') }}
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($totalPages > 1)
                        <div class="mt-6 flex items-center justify-center gap-2">
                            @if ($currentPage > 1)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                                    &laquo; {{ __('payment-history.previous') }}
                                </a>
                            @endif
                            <span class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('payment-history.page') }} {{ $currentPage }} {{ __('payment-history.of') }} {{ $totalPages }}
                            </span>
                            @if ($currentPage < $totalPages)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                                    {{ __('payment-history.next') }} &raquo;
                                </a>
                            @endif
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
        const payments = @json($paymentsJson);

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

        // Payment method translation
        const paymentMethodLabels = {
            'cash': '{{ __('payment-history.method_cash') }}',
            'transfer': '{{ __('payment-history.method_transfer') }}',
            'qris': '{{ __('payment-history.method_qris') }}'
        };

        function getPaymentMethodLabel(methodKind) {
            if (!methodKind) return '-';
            return paymentMethodLabels[methodKind.toLowerCase()] || methodKind;
        }

        function openPaymentModal(paymentId) {
            console.log('openPaymentModal called with:', paymentId);
            console.log('payments array:', payments);
            
            // Use loose equality (==) to handle type coercion (string vs int)
            const payment = payments.find(p => p.id == paymentId);
            console.log('found payment:', payment);
            
            if (!payment) {
                console.error('Payment not found for ID:', paymentId);
                return;
            }
            
            const modal = document.getElementById('paymentModal');
            const modalContent = document.getElementById('modalContent');

            const statusColors = {
                verified: {
                    bg: 'bg-green-100 dark:bg-green-900/30',
                    text: 'text-green-800 dark:text-green-300',
                    label: '{{ __('payment-history.status_verified') }}'
                },
                pending: {
                    bg: 'bg-amber-100 dark:bg-amber-900/30',
                    text: 'text-amber-800 dark:text-amber-300',
                    label: '{{ __('payment-history.status_pending') }}'
                },
                rejected: {
                    bg: 'bg-red-100 dark:bg-red-900/30',
                    text: 'text-red-800 dark:text-red-300',
                    label: '{{ __('payment-history.status_rejected') }}'
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
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('payment-history.payment_details') }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">${payment.payment_number}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold ${status.bg} ${status.text}">
                                ${payment.status === 'verified' ? `
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    ` : payment.status === 'pending' ? `
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    ` : `
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    `}
                                ${status.label}
                            </span>
                        </div>
                    </div>
                
                    <div class="space-y-4">
                        <h5 class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ __('payment-history.payment_details') }}</h5>
                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 space-y-3 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.payment_date') }}</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${formatDate(payment.payment_date)}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.amount') }}</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${formatCurrency(payment.amount)}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.payment_method') }}</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${getPaymentMethodLabel(payment.payment_method_kind)}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.bill_no_label') }}</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${payment.bill?.bill_number || '-'}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.billing_type_label') }}</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">${payment.bill?.billing_type_name || '-'}</span>
                            </div>
                        </div>
                        
                        ${payment.notes ? `
                            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg p-5">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h6 class="font-bold text-blue-900 dark:text-blue-200 mb-2">{{ __('payment-history.notes_label') }}</h6>
                                        <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap leading-relaxed">${payment.notes}</p>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${payment.rejection_reason ? `
                            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-5">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h6 class="font-bold text-red-900 dark:text-red-200 mb-2">{{ __('payment-history.rejection_reason_label') }}</h6>
                                        <p class="text-sm text-red-800 dark:text-red-300 leading-relaxed">${payment.rejection_reason}</p>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${payment.proof_of_payment ? `
                            <div class="space-y-3">
                                <h6 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ __('payment-history.proof_of_payment') }}
                                </h6>
                                <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-lg">
                                    <img src="/storage/${payment.proof_of_payment}" alt="{{ __('payment-history.proof_of_payment') }}" class="w-full h-auto">
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700 gap-3">
                        ${payment.status === 'verified' ? `
                        <a href="{{ url('/receipt') }}/${payment.id}" target="_blank" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 002 2zm-6 9h6m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0h6"></path>
                            </svg>
                            {{ __('payment-history.print_receipt') }}
                        </a>
                        ` : ''}
                        <button onclick="closePaymentModal()" class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors">
                            {{ __('payment-history.close') }}
                        </button>
                    </div>
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