<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Ambil semua pembayaran user melalui bills
        $allPayments = \App\Models\BillPayment::whereHas('bill', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['bill.billingType', 'bill.room.block.dorm', 'paymentMethod', 'paidByUser', 'verifiedBy'])
            ->orderBy('payment_date', 'desc')
            ->get();
        
        // Statistik pembayaran
        $totalPayments = $allPayments->count();
        $verifiedPayments = $allPayments->where('status', 'verified')->count();
        $pendingPayments = $allPayments->where('status', 'pending')->count();
        $rejectedPayments = $allPayments->where('status', 'rejected')->count();
        
        $totalVerified = $allPayments->where('status', 'verified')->sum('amount');
        $totalPending = $allPayments->where('status', 'pending')->sum('amount');
        
        // Filter status untuk dropdown
        $statusFilter = $request->get('status', 'all');
        $yearFilter = $request->get('year', 'all');
        
        // Ambil tahun-tahun yang tersedia
        $availableYears = $allPayments->pluck('payment_date')
            ->map(fn($date) => $date->year)
            ->unique()
            ->sort()
            ->values();
        
        // Filter payments berdasarkan status dan tahun
        $filteredPayments = $allPayments->when($statusFilter !== 'all', function($collection) use ($statusFilter) {
            return $collection->where('status', $statusFilter);
        })->when($yearFilter !== 'all', function($collection) use ($yearFilter) {
            return $collection->filter(function($payment) use ($yearFilter) {
                return $payment->payment_date->year == $yearFilter;
            });
        });
        
        // Paginate manual (10 per halaman)
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $paymentsCollection = $filteredPayments->forPage($currentPage, $perPage);
        
        $totalPages = ceil($filteredPayments->count() / $perPage);
        
        return view('resident.payment-history.index', compact(
            'totalPayments',
            'verifiedPayments',
            'pendingPayments',
            'rejectedPayments',
            'totalVerified',
            'totalPending',
            'paymentsCollection',
            'statusFilter',
            'yearFilter',
            'availableYears',
            'currentPage',
            'totalPages'
        ));
    }
}
