<?php

namespace App\Observers;

use App\Models\RoomResident;
use App\Services\AdminPrivilegeService;

class RoomResidentRevokeAdminObserver
{
    public function created(RoomResident $roomResident): void
    {
        // Hanya proses jika record ini adalah penempatan aktif
        if (! is_null($roomResident->check_out_date)) {
            return;
        }

        $room = $roomResident->room()->with('block')->first();
        if (! $room?->block) {
            return;
        }

        app(AdminPrivilegeService::class)->evaluateOnTransfer(
            $roomResident->user,
            (int) $room->block->dorm_id,
            (int) $room->block_id,
        );
    }

    public function updated(RoomResident $roomResident): void
    {
        // Jika hanya check_out_date yang berubah (penutupan kamar lama saat transfer/checkout),
        // evaluasi akan ditangani oleh created() dari record kamar baru.
        // Kita tidak perlu melakukan apa-apa di sini untuk kasus itu.
        if ($roomResident->wasChanged('check_out_date') && ! $roomResident->wasChanged('room_id')) {
            return;
        }

        // Jika room_id berubah langsung (edge case), evaluasi berdasarkan kamar baru
        if ($roomResident->wasChanged('room_id')) {
            // Jika record sekarang sudah di-checkout, tidak relevan
            if (! is_null($roomResident->check_out_date)) {
                return;
            }

            $room = $roomResident->room()->with('block')->first();
            if (! $room?->block) {
                return;
            }

            app(AdminPrivilegeService::class)->evaluateOnTransfer(
                $roomResident->user,
                (int) $room->block->dorm_id,
                (int) $room->block_id,
            );
        }
    }
}
