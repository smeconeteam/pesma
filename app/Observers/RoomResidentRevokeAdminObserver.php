<?php

namespace App\Observers;

use App\Models\RoomResident;
use App\Services\AdminPrivilegeService;

class RoomResidentRevokeAdminObserver
{
    public function created(RoomResident $roomResident): void
    {
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
        // Penutupan kamar lama saat transfer — ditangani di tempat lain
        if ($roomResident->wasChanged('check_out_date') && ! $roomResident->wasChanged('room_id')) {
            return;
        }

        // Edge case: room_id berubah langsung pada record aktif
        if ($roomResident->wasChanged('room_id') && is_null($roomResident->check_out_date)) {
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
