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
        
        // Ambil 3 tagihan terbaru
        $recentBills = $allBills->take(3);
        
        return view('dashboard', compact(
            'residentPhotoUrl',
            'residentName',
            'hasRoom',
            'roomCode',
            'statusLabel',
            'checkInDate',
            'contacts',
            // Data tagihan
            'totalBills',
            'unpaidBills',
            'totalUnpaid',
            'totalPaid',
            'recentBills'
        ));
    }
}