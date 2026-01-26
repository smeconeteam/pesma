<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('payment-history.title') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- STATISTIK PEMBAYARAN --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Pembayaran --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.total_payments') }}</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalPayments }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Terverifikasi --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.verified_payments') }}</p>
                            <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-500">{{ $verifiedPayments }}</p>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pending --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.pending_payments') }}</p>
                            <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-500">{{ $pendingPayments }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Ditolak --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('payment-history.rejected_payments') }}</p>
                            <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">{{ $rejectedPayments }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DAFTAR PEMBAYARAN --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100 dark:border-gray-700 transition-colors">
                <div class="p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('payment-history.title') }}</h3>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ __('payment-history.subtitle') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('resident.payment-history') }}" class="mb-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('payment-history.filter_status') }}</label>
                                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('payment-history.all_status') }}</option>
                                    <option value="verified" {{ $statusFilter === 'verified' ? 'selected' : '' }}>{{ __('payment-history.status_verified') }}</option>
                                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>{{ __('payment-history.status_pending') }}</option>
                                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>{{ __('payment-history.status_rejected') }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('payment-history.filter_year') }}</label>
                                <select name="year" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                    <option value="all" {{ $yearFilter === 'all' ? 'selected' : '' }}>{{ __('payment-history.all_years') }}</option>
                                    @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" {{ $yearFilter == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- Tabel Pembayaran --}}
                    @if ($paymentsCollection->count() > 0)
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.payment_number') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.bill_number') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.payment_type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.payment_date') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.amount') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('payment-history.status') }}</th>
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
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($totalPages > 1)
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('payment-history.page') }} <span class="font-semibold">{{ $currentPage }}</span> {{ __('payment-history.of') }} <span class="font-semibold">{{ $totalPages }}</span>
                        </div>
                        <div class="flex gap-2">
                            @if ($currentPage > 1)
                            <a href="{{ route('resident.payment-history', array_merge(request()->except('page'), ['page' => $currentPage - 1])) }}" 
                               class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ __('payment-history.previous') }}
                            </a>
                            @endif
                            
                            @if ($currentPage < $totalPages)
                            <a href="{{ route('resident.payment-history', array_merge(request()->except('page'), ['page' => $currentPage + 1])) }}" 
                               class="px-4 py-2 bg-purple-600 border border-purple-600 rounded-lg text-sm font-medium text-white hover:bg-purple-700">
                                {{ __('payment-history.next') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('payment-history.no_payments_title') }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            @if ($statusFilter !== 'all' || $yearFilter !== 'all')
                                {{ __('payment-history.no_filtered_payments') }}
                            @else
                                {{ __('payment-history.no_payments_message') }}
                            @endif
                        </p>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- MODAL DETAIL PEMBAYARAN --}}
    <div id="paymentModal" class="hidden fixed inset-0 bg-gray-900/75 dark:bg-black/80 z-50 overflow-y-auto backdrop-blur-sm" onclick="closePaymentModal(event)">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full transition-colors" onclick="event.stopPropagation()">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-500 to-indigo-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">{{ __('payment-history.payment_details') }}</h3>
                        <button onclick="closePaymentModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="paymentModalContent" class="p-6 space-y-6">
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const paymentsData = @json($paymentsCollection->values());

        function openPaymentModal(paymentId) {
            const payment = paymentsData.find(p => p.id === paymentId);
            if (!payment) return;

            const modal = document.getElementById('paymentModal');
            const content = document.getElementById('paymentModalContent');

            let statusColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
            if (payment.status === 'verified') statusColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            else if (payment.status === 'pending') statusColor = 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
            else if (payment.status === 'rejected') statusColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';

            const formatDate = (dateStr) => {
                if (!dateStr) return '-';
                const date = new Date(dateStr);
                return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const formatCurrency = (num) => {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
            };

            content.innerHTML = `
                <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-gray-100">${payment.bill.billing_type.name}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('payment-history.payment_number') }}: <span class="font-semibold text-gray-900 dark:text-gray-200">${payment.payment_number}</span></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('payment-history.bill_number') }}: <span class="font-semibold text-gray-900 dark:text-gray-200">${payment.bill.bill_number}</span></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold ${statusColor}">
                            ${payment.status_label}
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
                            <span class="font-bold text-gray-900 dark:text-gray-100">${payment.payment_method?.name || '-'}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.payment_type') }}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">${payment.payment_type_label}</span>
                        </div>
                        ${payment.verified_at ? `
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('payment-history.verified_at') }}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">${formatDate(payment.verified_at)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                ${payment.notes ? `
                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1">
                            <h6 class="font-bold text-blue-900 dark:text-blue-200 mb-1">{{ __('payment-history.notes') }}</h6>
                            <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap">${payment.notes}</p>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${payment.rejection_reason ? `
                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1">
                            <h6 class="font-bold text-red-900 dark:text-red-200 mb-1">{{ __('payment-history.rejection_reason') }}</h6>
                            <p class="text-sm text-red-800 dark:text-red-300">${payment.rejection_reason}</p>
                        </div>
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
                
                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
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
