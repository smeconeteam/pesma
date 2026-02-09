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
        
        // ==========================================
        // 1. DATA PROFIL & KAMAR
        // ==========================================
        $residentPhotoUrl = $profile && $profile->photo_path 
            ? Storage::url($profile->photo_path) 
            : null;
        
        $residentName = $profile?->full_name ?? $user->name ?? 'Penghuni';
        
        // Cek status penghuni
        $isInactive = $profile && $profile->status === 'inactive';
        
        // Data kamar aktif - hanya jika status bukan inactive
        $assignment = !$isInactive ? $user->activeRoomResident : null;
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
        
        // ==========================================
        // 2. DATA KONTAK PENTING
        // ==========================================
        // Ambil data checkout terakhir untuk penghuni inactive
        $lastCheckout = null;
        $checkoutReason = null;
        if ($isInactive) {
            $lastHistory = $user->roomHistories()
                ->whereNotNull('check_out_date')
                ->orderBy('check_out_date', 'desc')
                ->first();
            
            if ($lastHistory) {
                $lastCheckout = $lastHistory->check_out_date->format('d M Y');
                $checkoutReason = $lastHistory->notes;
            }
        }

        // DATA PIC KAMAR (Penghuni yang ditunjuk sebagai PIC - berbeda dengan Penanggung Jawab)
        $picInfo = null;
        $isYouPic = false;

        if ($room?->id) {
            // Cari Resident yang jadi PIC (is_pic = true)
            $picAssignment = \App\Models\RoomResident::query()
                ->where('room_id', $room->id)
                ->whereNull('check_out_date')
                ->where('is_pic', true)
                ->with(['user.residentProfile'])
                ->first();

            if ($picAssignment) {
                $picUser = $picAssignment->user;
                $picProfile = $picUser?->residentProfile;
                
                $picInfo = [
                    'name'      => $picProfile?->full_name ?? $picUser?->name ?? '-',
                    'phone'     => $picProfile?->phone_number ?? '-',
                    'photo_url' => ($picProfile?->photo_path) ? Storage::url($picProfile->photo_path) : null,
                ];

                $isYouPic = ($user->id === $picUser?->id);
            }
            // Jika tidak ada resident PIC, biarkan picInfo tetap null
        }
        
        // Data kontak
        $contacts = Contact::where('is_active', true)
            ->where(function ($q) use ($dorm, $isInactive, $user) {
                if ($isInactive) {
                    // Untuk penghuni inactive: tampilkan kontak global + kontak dari cabang terakhir
                    $lastDorm = $user->roomHistories()
                        ->whereNotNull('check_out_date')
                        ->with('room.block.dorm')
                        ->orderBy('check_out_date', 'desc')
                        ->first()
                        ?->room
                        ?->block
                        ?->dorm;
                    
                    $q->whereNull('dorm_id') // Kontak global
                    ->when($lastDorm, function($query) use ($lastDorm) {
                        $query->orWhere('dorm_id', $lastDorm->id); // Kontak cabang terakhir
                    });
                } else {
                    // Untuk penghuni active: tampilkan kontak global + kontak cabang saat ini
                    $q->whereNull('dorm_id')
                    ->orWhere('dorm_id', $dorm?->id);
                }
            })
            ->orderBy('display_name')
            ->get();

        // Prepend room contact person ke daftar kontak (dari penghuni yang jadi admin cabang)
        if ($room?->contact_person_user_id) {
            $contactPerson = $room->contactPerson;
            $contactResidentProfile = $contactPerson?->residentProfile;
            $contactName = $contactResidentProfile?->full_name ?? $contactPerson?->name;
            $contactPhone = $contactResidentProfile?->phone_number;
            
            if ($contactName && $contactPhone) {
                $roomContact = (object) [
                    'display_name' => $contactName . ' (Penanggung Jawab)',
                    'name'         => $contactName,
                    'phone'        => $contactPhone,
                    'auto_message' => null,
                ];
                $contacts->prepend($roomContact);
            }
        }
        
        // ==========================================
        // 3. DATA TAGIHAN (BILLS)
        // ==========================================
        $allBills = collect();
        if (method_exists($user, 'bills')) {
            $allBills = $user->bills()
                ->with(['billingType'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        // Statistik
        $totalBills = $allBills->count();
        $unpaidBills = $allBills->whereIn('status', ['issued', 'partial', 'overdue'])->count();
        $totalUnpaid = $allBills->whereIn('status', ['issued', 'partial', 'overdue'])->sum('remaining_amount');
        $totalPaid = $allBills->where('status', 'paid')->sum('total_amount');
        
        // Ambil 3 tagihan terbaru (DATA ASLI)
        $recentBills = $allBills->take(3);
        
        // ==========================================
        // 4. DATA RIWAYAT PEMBAYARAN (PAYMENT HISTORY)
        // ==========================================
        $allPayments = \App\Models\BillPayment::whereHas('bill', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['bill.billingType', 'paymentMethod'])
            ->orderBy('payment_date', 'desc')
            ->get();
        
        // Statistik pembayaran
        $totalPayments = $allPayments->count();
        $verifiedPayments = $allPayments->where('status', 'verified')->count();
        $pendingPayments = $allPayments->where('status', 'pending')->count();
        $totalVerifiedAmount = $allPayments->where('status', 'verified')->sum('amount');
        
        // Ambil 3 pembayaran terbaru
        $recentPayments = $allPayments->take(3);
        
        // ==========================================
        
        return view('dashboard', compact(
            'residentPhotoUrl',
            'residentName',
            'hasRoom',
            'roomCode',
            'statusLabel',
            'checkInDate',
            'contacts',
            'isInactive',
            'lastCheckout',
            'checkoutReason',
            'totalBills',
            'unpaidBills',
            'totalUnpaid',
            'totalPaid',
            'recentBills',
            'totalPayments',
            'verifiedPayments',
            'pendingPayments',
            'totalVerifiedAmount',
            'recentPayments',
            'picInfo',
            'isYouPic'
        ));
    }
}