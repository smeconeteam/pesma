<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('bills.my_bills') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8 bg-gray-50 dark:bg-gray-950 min-h-screen transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- STATISTIK TAGIHAN --}}
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                {{-- Total Tagihan --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-4 sm:p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">{{ __('bills.total_bills') }}</p>
                            <p class="mt-1 sm:mt-2 text-lg sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalBills }}</p>
                        </div>
                        <div class="p-2 sm:p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg shrink-0 ml-2">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Belum Lunas --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-4 sm:p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">{{ __('bills.unpaid') }}</p>
                            <p class="mt-1 sm:mt-2 text-lg sm:text-2xl font-bold text-amber-600 dark:text-amber-500">{{ $unpaidBills }}</p>
                        </div>
                        <div class="p-2 sm:p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg shrink-0 ml-2">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Belum Dibayar --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-4 sm:p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">{{ __('bills.total_unpaid') }}</p>
                            <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-red-600 dark:text-red-400 truncate">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-2 sm:p-3 bg-red-50 dark:bg-red-900/30 rounded-lg shrink-0 ml-2">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Sudah Dibayar --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-4 sm:p-5 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">{{ __('bills.total_paid') }}</p>
                            <p class="mt-1 sm:mt-2 text-base sm:text-xl font-bold text-green-600 dark:text-green-400 truncate">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-2 sm:p-3 bg-green-50 dark:bg-green-900/30 rounded-lg shrink-0 ml-2">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAGIHAN YANG PERLU DIBAYAR --}}
            @if ($urgentBills->count() > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100 dark:border-gray-700 transition-colors">
                <div class="p-5 sm:p-7">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="p-2.5 bg-gradient-to-br from-red-500 to-orange-600 rounded-xl shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('bills.urgent_bills') }}</h3>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ __('bills.urgent_bills_desc') }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($urgentBills as $bill)
                        <button 
                            onclick="openBillModal({{ $bill->id }})"
                            class="w-full text-left p-4 bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl border-2 {{ $bill->status === 'overdue' ? 'border-red-300 dark:border-red-900' : 'border-amber-200 dark:border-amber-900' }} hover:shadow-md transition-all">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ $bill->billingType->name }}</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                            {{ $bill->status === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                               ($bill->status === 'partial' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200') }}">
                                            {{ $bill->status_label }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <div>{{ __('bills.bill_number') }}: <span class="font-semibold text-gray-900 dark:text-gray-200">{{ $bill->bill_number }}</span></div>
                                        @if ($bill->period_start && $bill->period_end)
                                        <div>{{ __('bills.period') }}: {{ $bill->period_start->format('d M Y') }} - {{ $bill->period_end->format('d M Y') }}</div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex items-center gap-4 text-sm flex-wrap">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('bills.total') }}:</span>
                                            <span class="font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('bills.paid') }}:</span>
                                            <span class="font-bold text-green-600 dark:text-green-400">Rp {{ number_format($bill->paid_amount, 0, ',', '.') }}</span>
                                        </div>
                                        @if ($bill->remaining_amount > 0)
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('bills.remaining') }}:</span>
                                            <span class="font-bold text-red-600 dark:text-red-400">Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if ($bill->status === 'overdue')
                                <div class="shrink-0">
                                    <div class="p-2 bg-red-100 dark:bg-red-900/50 rounded-lg">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                @else
                                <div class="shrink-0">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                                @endif
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- DAFTAR TAGIHAN --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-2xl rounded-xl border border-gray-100 dark:border-gray-700 transition-colors">
                <div class="p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('bills.bills_list') }}</h3>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ __('bills.bills_list_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ localizedRoute('resident.bills') }}" class="mb-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('bills.status') }}</label>
                                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('bills.all_status') }}</option>
                                    <option value="unpaid" {{ $statusFilter === 'unpaid' ? 'selected' : '' }}>{{ __('bills.unpaid') }}</option>
                                    <option value="issued" {{ $statusFilter === 'issued' ? 'selected' : '' }}>{{ __('bills.status_issued') }}</option>
                                    <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>{{ __('bills.status_partial') }}</option>
                                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>{{ __('bills.status_paid') }}</option>
                                    <option value="overdue" {{ $statusFilter === 'overdue' ? 'selected' : '' }}>{{ __('bills.status_overdue') }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('bills.year') }}</label>
                                <select name="year" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all" {{ $yearFilter === 'all' ? 'selected' : '' }}>{{ __('bills.all_years') }}</option>
                                    @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" {{ $yearFilter == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- Tabel Tagihan --}}
                    @if ($billsCollection->count() > 0)
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.bill_number') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.period') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.total') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.paid') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.remaining') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('bills.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($billsCollection as $bill)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors" onclick="openBillModal({{ $bill->id }})">
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bill->bill_number }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $bill->billingType->name }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        @if ($bill->period_start && $bill->period_end)
                                            {{ $bill->period_start->format('d M Y') }} - {{ $bill->period_end->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold text-green-600 dark:text-green-400">Rp {{ number_format($bill->paid_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-sm text-right font-semibold {{ $bill->remaining_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-400' }}">
                                        Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                            {{ $bill->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                               ($bill->status === 'partial' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                               ($bill->status === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200')) }}">
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
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('bills.page') }} <span class="font-semibold">{{ $currentPage }}</span> {{ __('bills.of') }} <span class="font-semibold">{{ $totalPages }}</span>
                        </div>
                        <div class="flex gap-2">
                            @if ($currentPage > 1)
                            <a href="{{ localizedRoute('resident.bills', array_merge(request()->except('page'), ['page' => $currentPage - 1])) }}" 
                               class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ __('bills.previous') }}
                            </a>
                            @endif
                            
                            @if ($currentPage < $totalPages)
                            <a href="{{ localizedRoute('resident.bills', array_merge(request()->except('page'), ['page' => $currentPage + 1])) }}" 
                               class="px-4 py-2 bg-blue-600 border border-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700">
                                {{ __('bills.next') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('bills.no_bills') }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('bills.no_bills_desc') }}</p>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- MODAL DETAIL TAGIHAN --}}
    <div id="billModal" class="hidden fixed inset-0 bg-gray-900/75 dark:bg-black/80 z-50 overflow-y-auto backdrop-blur-sm" onclick="closeBillModal(event)">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full transition-colors" onclick="event.stopPropagation()">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-indigo-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">{{ __('bills.bill_detail') }}</h3>
                        <button onclick="closeBillModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="billModalContent" class="p-6 space-y-6">
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const billsData = @json($billsCollection->merge($urgentBills)->unique('id')->values());
        
        // Translation strings for JavaScript
        const translations = {
            bill_number: "{{ __('bills.bill_number') }}",
            period: "{{ __('bills.period') }}",
            payment_details: "{{ __('bills.payment_details') }}",
            bill_amount: "{{ __('bills.bill_amount') }}",
            discount: "{{ __('bills.discount') }}",
            total_bill: "{{ __('bills.total_bill') }}",
            paid: "{{ __('bills.paid') }}",
            remaining: "{{ __('bills.remaining') }}",
            payment_progress: "{{ __('bills.payment_progress') }}",
            notes: "{{ __('bills.notes') }}",
            close: "{{ __('bills.close') }}"
        };

        function openBillModal(billId) {
            const bill = billsData.find(b => b.id === billId);
            if (!bill) return;

            const modal = document.getElementById('billModal');
            const content = document.getElementById('billModalContent');

            // Dark mode adjustment for labels
            let statusColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
            if (bill.status === 'paid') statusColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            else if (bill.status === 'partial') statusColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            else if (bill.status === 'overdue') statusColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            else if (bill.status === 'issued') statusColor = 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';

            const formatDate = (dateStr) => {
                if (!dateStr) return '-';
                const date = new Date(dateStr);
                return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const formatCurrency = (num) => {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
            };

            // PERHATIKAN: Saya menambahkan class 'dark:...' di dalam string HTML ini
            // FIX: Added null checks for billing_type and other fields
            const billingTypeName = bill.billing_type?.name || bill.type_name || 'Tagihan';
            const statusLabel = bill.status_label || bill.status || '-';
            
            content.innerHTML = `
                <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-gray-100">${billingTypeName}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">${translations.bill_number}: <span class="font-semibold text-gray-900 dark:text-gray-200">${bill.bill_number || '-'}</span></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold ${statusColor}">
                            ${statusLabel}
                        </span>
                    </div>
                    ${bill.period_start && bill.period_end ? `
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700/50 rounded-lg px-4 py-2 border border-gray-200 dark:border-gray-600">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium">${translations.period}:</span>
                        <span class="font-bold">${formatDate(bill.period_start)} - ${formatDate(bill.period_end)}</span>
                    </div>
                    ` : ''}
                </div>
                <div class="space-y-4">
                    <h5 class="font-bold text-gray-900 dark:text-gray-100 text-lg">${translations.payment_details}</h5>
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 space-y-3 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">${translations.bill_amount}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">${formatCurrency(bill.base_amount)}</span>
                        </div>
                        ${bill.discount_amount > 0 ? `
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">${translations.discount} (${parseFloat(bill.discount_percent)}%)</span>
                            <span class="font-bold text-green-600 dark:text-green-400">- ${formatCurrency(bill.discount_amount)}</span>
                        </div>
                        ` : ''}
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">${translations.total_bill}</span>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">${formatCurrency(bill.total_amount)}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">${translations.paid}</span>
                            <span class="font-bold text-green-600 dark:text-green-400">${formatCurrency(bill.paid_amount)}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 bg-white dark:bg-gray-800 rounded-lg px-4 ${bill.remaining_amount > 0 ? 'border-2 border-red-200 dark:border-red-900/50' : 'border border-gray-200 dark:border-gray-700'}">
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">${translations.remaining}</span>
                            <span class="text-lg font-bold ${bill.remaining_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100'}">${formatCurrency(bill.remaining_amount)}</span>
                        </div>
                    </div>
                </div>
                ${bill.total_amount > 0 ? `
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">${translations.payment_progress}</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">${Math.round((bill.paid_amount / bill.total_amount) * 100)}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-500" style="width: ${(bill.paid_amount / bill.total_amount) * 100}%"></div>
                    </div>
                </div>
                ` : ''}
                ${bill.notes ? `
                <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1">
                            <h6 class="font-bold text-amber-900 dark:text-amber-200 mb-1">${translations.notes}</h6>
                            <p class="text-sm text-amber-800 dark:text-amber-300">${bill.notes}</p>
                        </div>
                    </div>
                </div>
                ` : ''}
                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button onclick="closeBillModal()" class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors">
                        ${translations.close}
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBillModal(event) {
            if (event && event.target.id !== 'billModal') return;
            const modal = document.getElementById('billModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeBillModal();
            }
        });
    </script>
</x-app-layout>