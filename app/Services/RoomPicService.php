<?php

namespace App\Services;

use App\Models\RoomResident;
use Illuminate\Support\Facades\DB;

class RoomPicService
{
    /**
     * Pastikan kamar memiliki 1 PIC aktif jika masih ada penghuni aktif.
     * Rule: pilih penghuni aktif yang paling awal check_in_date (lalu id terkecil).
     */
    public function ensurePicForRoom(int $roomId): void
    {
        DB::transaction(function () use ($roomId) {
            // Lock semua penghuni aktif kamar tsb (anti race)
            $active = RoomResident::query()
                ->where('room_residents.room_id', $roomId)
                ->whereNull('room_residents.check_out_date')
                ->lockForUpdate()
                ->get();

            if ($active->isEmpty()) {
                return; // kamar kosong -> tidak perlu PIC
            }

            $activePics = $active->where('is_pic', true);

            // Kalau sudah ada 1 PIC, biarkan
            if ($activePics->count() === 1) {
                return;
            }

            // Kalau data bug >1 PIC, rapikan: nonaktifkan semua PIC dulu
            if ($activePics->count() > 1) {
                RoomResident::query()
                    ->where('room_residents.room_id', $roomId)
                    ->whereNull('room_residents.check_out_date')
                    ->update(['is_pic' => false]);
            }

            // Pilih kandidat PIC
            $candidate = RoomResident::query()
                ->where('room_residents.room_id', $roomId)
                ->whereNull('room_residents.check_out_date')
                ->orderBy('room_residents.check_in_date')
                ->orderBy('room_residents.id')
                ->first();

            if ($candidate) {
                $candidate->update(['is_pic' => true]);
            }
        });
    }
}
