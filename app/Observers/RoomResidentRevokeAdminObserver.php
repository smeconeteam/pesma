<?php

namespace App\Observers;

use App\Models\RoomResident;
use App\Services\AdminPrivilegeService;

class RoomResidentRevokeAdminObserver
{
    public function created(RoomResident $roomResident): void
    {
        // hanya cek ketika record ini aktif (check_out_date null)
        if (! is_null($roomResident->check_out_date)) {
            return;
        }

        // cek mismatch berdasarkan kamar aktif terbaru
        app(AdminPrivilegeService::class)->revokeAdmin($roomResident->user);
    }

    public function updated(RoomResident $roomResident): void
    {
        // setiap update yang bisa mempengaruhi status aktif/pindah
        if ($roomResident->wasChanged(['room_id', 'check_in_date', 'check_out_date'])) {
            app(AdminPrivilegeService::class)->revokeAdmin($roomResident->user);
        }
    }
}