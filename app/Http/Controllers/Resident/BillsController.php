<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Ambil semua tagihan user
        $allBills = $user->bills()
            ->with(['billingType', 'room.block.dorm', 'registration'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Statistik tagihan
        $totalBills = $allBills->count();
        $unpaidBills = $allBills->whereIn('status', ['issued', 'partial', 'overdue'])->count();
        $totalUnpaid = $allBills->whereIn('status', ['issued', 'partial', 'overdue'])->sum('remaining_amount');
        $totalPaid = $allBills->where('status', 'paid')->sum('total_amount');
        
        // Tagihan yang perlu perhatian (belum lunas, diurutkan berdasarkan tanggal jatuh tempo)
        $urgentBills = $allBills
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->sortBy(function($bill) {
                // Prioritas: overdue > issued > partial
                // Kemudian urutkan berdasarkan period_end
                $priority = match($bill->status) {
                    'overdue' => 1,
                    'issued' => 2,
                    'partial' => 3,
                    default => 4
                };
                
                $date = $bill->period_end ? $bill->period_end->timestamp : PHP_INT_MAX;
                
                return ($priority * 1000000000000) + $date;
            })
            ->take(5);
        
        // Filter status untuk dropdown
        $statusFilter = $request->get('status', 'all');
        $yearFilter = $request->get('year', 'all');
        
        // Ambil tahun-tahun yang tersedia
        $availableYears = $allBills->pluck('created_at')
            ->map(fn($date) => $date->year)
            ->unique()
            ->sort()
            ->values();
        
        // Filter bills berdasarkan status dan tahun
        $filteredBills = $allBills->when($statusFilter !== 'all', function($collection) use ($statusFilter) {
            if ($statusFilter === 'unpaid') {
                return $collection->whereIn('status', ['issued', 'partial', 'overdue']);
            }
            return $collection->where('status', $statusFilter);
        })->when($yearFilter !== 'all', function($collection) use ($yearFilter) {
            return $collection->filter(function($bill) use ($yearFilter) {
                return $bill->created_at->year == $yearFilter;
            });
        });
        
        // Paginate manual (10 per halaman)
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $billsCollection = $filteredBills->forPage($currentPage, $perPage);
        
        $totalPages = ceil($filteredBills->count() / $perPage);
        
        return view('resident.bills.index', compact(
            'totalBills',
            'unpaidBills',
            'totalUnpaid',
            'totalPaid',
            'urgentBills',
            'billsCollection',
            'statusFilter',
            'yearFilter',
            'availableYears',
            'currentPage',
            'totalPages'
        ));
    }
}