<?php

namespace App\Observers;

use App\Models\RoomResident;
use App\Services\RoomPicService;

class RoomResidentObserver
{
    // biar tidak loop saat kita update is_pic di dalam observer
    protected static bool $running = false;

    public function updated(RoomResident $roomResident): void
    {
        if (self::$running) return;

        $originalIsPic = (bool) $roomResident->getOriginal('is_pic');
        $roomId = (int) $roomResident->getOriginal('room_id');

        // PIC dianggap "meninggalkan kamar" jika:
        // - dulunya PIC, lalu sekarang check_out_date terisi, atau is_pic jadi false
        $picLeftRoom =
            $originalIsPic === true
            && (
                ($roomResident->wasChanged('check_out_date') && !is_null($roomResident->check_out_date))
                || ($roomResident->wasChanged('is_pic') && $roomResident->is_pic === false)
            );

        if (! $picLeftRoom) return;

        self::$running = true;

        app(RoomPicService::class)->ensurePicForRoom($roomId);

        self::$running = false;
    }
}
