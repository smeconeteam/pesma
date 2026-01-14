<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\RoomResident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Penempatan kamar aktif (belum checkout)
        $assignment = RoomResident::query()
            ->where('user_id', $user->id)
            ->whereNull('check_out_date')
            ->with([
                'room',                // Room
                'room.block',          // Block
                'room.block.dorm',     // Dorm
                'room.roomType',       // RoomType (kalau ada)
            ])
            ->latest('check_in_date')
            ->first();

        // Status penghuni:
        // - kalau ada residentProfile.status, pakai itu
        // - fallback: kalau punya assignment => aktif, kalau tidak => menunggu penempatan
        $profileStatus = null;
        if (method_exists($user, 'residentProfile')) {
            $profileStatus = optional($user->residentProfile)->status;
        }

        $status = $profileStatus ?: ($assignment ? 'aktif' : 'menunggu_penempatan');

        $statusLabel = match ($status) {
            'active', 'aktif' => 'Aktif',
            'inactive', 'nonaktif' => 'Nonaktif',
            'registered', 'pending', 'menunggu_penempatan' => 'Menunggu Penempatan',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };

        // Nama penghuni (prioritaskan full_name dari resident profile)
        $residentName = $user->name;
        if (method_exists($user, 'residentProfile')) {
            $residentName = $user->residentProfile?->full_name ?: $user->name;
        }

        // Foto penghuni (prioritaskan resident_profiles.photo_path kalau ada)
        $residentPhotoUrl = null;
        if (method_exists($user, 'residentProfile')) {
            $path = $user->residentProfile?->photo_path;
            if ($path) {
                $residentPhotoUrl = Storage::url($path);
            }
        }

        // Kode kamar (sesuaikan jika kolommu berbeda)
        $room = $assignment?->room;
        $roomCode = $room?->code
            ?? $room?->room_code
            ?? $room?->name
            ?? $room?->number
            ?? '-';

        // Tanggal check-in
        $checkInDate = '-';
        if ($assignment?->check_in_date) {
            // jika cast date: $assignment->check_in_date->format(...)
            $checkInDate = method_exists($assignment->check_in_date, 'format')
                ? $assignment->check_in_date->format('d M Y')
                : date('d M Y', strtotime((string) $assignment->check_in_date));
        }

        // Profil PIC kamar aktif
        $picName = '-';
        $picPhotoUrl = null;
        $picPhoneNumber = null;
        $isYouPic = false;

        if ($room?->id) {
            $picAssignment = RoomResident::query()
                ->where('room_id', $room->id)
                ->whereNull('check_out_date')
                ->where('is_pic', true)
                ->with([
                    'user.residentProfile',
                ])
                ->first();

            if ($picAssignment) {
                $picUser = $picAssignment->user;

                $picName = $picUser?->name ?? '-';
                if ($picUser && method_exists($picUser, 'residentProfile')) {
                    $picName = $picUser->residentProfile?->full_name ?: $picUser->name;
                    
                    // Ambil nomor telepon PIC
                    $picPhoneNumber = $picUser->residentProfile?->phone_number;
                }

                $isYouPic = (bool) ($picUser?->id && $picUser->id === $user->id);

                // foto PIC
                if ($picUser && method_exists($picUser, 'residentProfile')) {
                    $picPath = $picUser->residentProfile?->photo_path;
                    if ($picPath) {
                        $picPhotoUrl = Storage::url($picPath);
                    }
                }
            }
        }

        // Ambil daftar kontak
        $contacts = collect([]);
        
        if ($room?->block?->dorm_id) {
            // Jika sudah punya kamar, ambil kontak untuk cabang tersebut + kontak umum
            $dormId = $room->block->dorm_id;
            
            $contacts = Contact::where('is_active', true)
                ->where(function($query) use ($dormId) {
                    $query->whereNull('dorm_id')
                          ->orWhere('dorm_id', $dormId);
                })
                ->orderBy('display_name')
                ->get();
        } else {
            // Jika belum punya kamar, ambil kontak umum saja
            $contacts = Contact::where('is_active', true)
                ->whereNull('dorm_id')
                ->orderBy('display_name')
                ->get();
        }

        return view('dashboard', [
            'statusLabel'       => $statusLabel,

            'hasRoom'           => (bool) $assignment,
            'roomCode'          => $roomCode,
            'checkInDate'       => $checkInDate,

            'residentName'      => $residentName,
            'residentPhotoUrl'  => $residentPhotoUrl,

            'picName'           => $picName,
            'picPhotoUrl'       => $picPhotoUrl,
            'picPhoneNumber'    => $picPhoneNumber,
            'isYouPic'          => $isYouPic,

            'contacts'          => $contacts,
        ]);
    }
}
