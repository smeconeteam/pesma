<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\RoomHistory;
use App\Models\RoomResident;
use Illuminate\Http\Request;

class RoomHistoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $residentProfile = $user->residentProfile;

        if (!$residentProfile) {
            abort(404, 'Profil penghuni tidak ditemukan');
        }

        // Cek status penghuni - tolak akses jika inactive
        if ($residentProfile->status === 'inactive') {
            abort(403, 'Akses ditolak. Akun Anda sudah tidak aktif.');
        }

        // Ambil kamar saat ini (room_resident yang masih aktif)
        $currentRoom = RoomResident::with([
            'room.block.dorm',
            'room.roomType'
        ])
            ->where('user_id', $user->id)
            ->whereNull('check_out_date')
            ->first();

        // Ambil semua riwayat perpindahan kamar
        $histories = RoomHistory::with([
            'room.block.dorm',
            'room.roomType',
            'recordedBy'
        ])
            ->where('user_id', $user->id)
            ->orderBy('check_in_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('resident.room-history.index', compact(
            'residentProfile',
            'currentRoom',
            'histories'
        ));
    }
}