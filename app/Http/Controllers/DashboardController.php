<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Contact;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->residentProfile;
        
        // Data profil penghuni
        $residentPhotoUrl = $profile && $profile->photo_path 
            ? Storage::url($profile->photo_path) 
            : null;
        
        $residentName = $profile?->full_name ?? $user->name ?? 'Penghuni';
        
        // Data kamar aktif
        $assignment = $user->activeRoomResident;
        $room = $assignment?->room;
        $block = $room?->block;
        $dorm = $block?->dorm;
        
        $hasRoom = $assignment !== null;
        $roomCode = $room?->code ?? '-';
        
        $status = $profile?->status ?? ($assignment ? 'active' : 'registered');
        $statusLabel = match ($status) {
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'registered' => 'Menunggu Penempatan',
            default => ucfirst($status),
        };
        
        $checkInDate = $assignment?->check_in_date 
            ? $assignment->check_in_date->format('d M Y')
            : '-';
        
        // Data PIC kamar
        $picAssignment = $assignment 
            ? $room->roomResidents()->where('is_pic', true)->whereNull('check_out_date')->first()
            : null;
        
        $picUser = $picAssignment?->user;
        $picName = $picUser 
            ? ($picUser->residentProfile?->full_name ?? $picUser->name)
            : '-';
        
        $picPhotoUrl = $picUser && $picUser->residentProfile?->photo_path
            ? Storage::url($picUser->residentProfile->photo_path)
            : null;
        
        $picPhoneNumber = $picUser?->residentProfile?->phone_number ?? null;
        
        $isYouPic = $picUser && $picUser->id === $user->id;
        
        // Data kontak
        $contacts = Contact::where('is_active', true)
            ->where(function ($q) use ($dorm) {
                $q->whereNull('dorm_id')
                  ->orWhere('dorm_id', $dorm?->id);
            })
            ->orderBy('display_name')
            ->get();
        
        // ===== DATA TAGIHAN =====
        
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
        $statusFilter = request('status', 'all');
        $yearFilter = request('year', 'all');
        
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
        $currentPage = request('page', 1);
        $billsCollection = $filteredBills->forPage($currentPage, $perPage);
        
        $totalPages = ceil($filteredBills->count() / $perPage);
        
        return view('dashboard', compact(
            'residentPhotoUrl',
            'residentName',
            'hasRoom',
            'roomCode',
            'statusLabel',
            'checkInDate',
            'picName',
            'picPhotoUrl',
            'picPhoneNumber',
            'isYouPic',
            'contacts',
            // Data tagihan
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