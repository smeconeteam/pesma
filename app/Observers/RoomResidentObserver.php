<?php

namespace App\Observers;

use App\Models\RoomResident;
use App\Models\RoomHistory;
use App\Services\RoomPicService;

class RoomResidentObserver
{
    // Prevent infinite loop saat update is_pic di dalam observer
    protected static bool $running = false;

    /**
     * Handle the RoomResident "created" event.
     * Otomatis buat RoomHistory ketika RoomResident dibuat
     */
    public function created(RoomResident $roomResident): void
    {
        // Skip jika sudah ada RoomHistory (dibuat manual di controller)
        $historyExists = RoomHistory::where('room_resident_id', $roomResident->id)->exists();

        if (!$historyExists) {
            RoomHistory::create([
                'user_id' => $roomResident->user_id,
                'room_id' => $roomResident->room_id,
                'room_resident_id' => $roomResident->id,
                'check_in_date' => $roomResident->check_in_date,
                'check_out_date' => $roomResident->check_out_date,
                'is_pic' => $roomResident->is_pic,
                'movement_type' => 'new',
                'notes' => 'Auto-generated history on room resident creation',
                'recorded_by' => auth()->id(),
            ]);
        }

        // Update status resident menjadi active jika check_out_date null
        if (is_null($roomResident->check_out_date)) {
            $user = $roomResident->user;
            if ($user && $user->residentProfile) {
                $user->residentProfile->update([
                    'status' => 'active',
                ]);
            }
        }
    }

    /**
     * Handle the RoomResident "updated" event.
     */
    public function updated(RoomResident $roomResident): void
    {
        if (self::$running) return;

        $originalIsPic = (bool) $roomResident->getOriginal('is_pic');
        $originalCheckOutDate = $roomResident->getOriginal('check_out_date');
        $roomId = (int) $roomResident->getOriginal('room_id');

        // 1. Handle checkout - Update RoomHistory
        if ($roomResident->isDirty('check_out_date') && !is_null($roomResident->check_out_date)) {
            $this->handleCheckout($roomResident);
        }

        // 2. Handle PIC changes - Ensure room always has a PIC
        $picLeftRoom = $originalIsPic === true && (
            ($roomResident->wasChanged('check_out_date') && !is_null($roomResident->check_out_date)) ||
            ($roomResident->wasChanged('is_pic') && $roomResident->is_pic === false)
        );

        if ($picLeftRoom) {
            self::$running = true;

            // Use service untuk assign PIC baru
            if (class_exists(\App\Services\RoomPicService::class)) {
                app(\App\Services\RoomPicService::class)->ensurePicForRoom($roomId);
            } else {
                // Fallback: auto assign PIC ke penghuni tertua jika service belum ada
                $this->autoAssignPic($roomId);
            }

            self::$running = false;
        }

        // 3. Update RoomHistory jika is_pic berubah
        if ($roomResident->wasChanged('is_pic')) {
            RoomHistory::where('room_resident_id', $roomResident->id)
                ->whereNull('check_out_date')
                ->update([
                    'is_pic' => $roomResident->is_pic,
                ]);
        }
    }

    /**
     * Handle the RoomResident "deleted" event.
     */
    public function deleted(RoomResident $roomResident): void
    {
        // Hapus juga RoomHistory terkait
        RoomHistory::where('room_resident_id', $roomResident->id)->delete();

        // Jika yang dihapus adalah PIC, assign PIC baru
        if ($roomResident->is_pic && !self::$running) {
            self::$running = true;

            if (class_exists(\App\Services\RoomPicService::class)) {
                app(\App\Services\RoomPicService::class)->ensurePicForRoom($roomResident->room_id);
            } else {
                $this->autoAssignPic($roomResident->room_id);
            }

            self::$running = false;
        }
    }

    /**
     * Handle checkout process
     */
    protected function handleCheckout(RoomResident $roomResident): void
    {
        // Update RoomHistory
        RoomHistory::where('room_resident_id', $roomResident->id)
            ->whereNull('check_out_date')
            ->update([
                'check_out_date' => $roomResident->check_out_date,
                'movement_type' => 'checkout',
            ]);

        // Cek apakah user masih punya kamar aktif lain
        $user = $roomResident->user;
        if ($user && $user->residentProfile) {
            $hasActiveRoom = RoomResident::where('user_id', $user->id)
                ->whereNull('check_out_date')
                ->where('id', '!=', $roomResident->id) // Exclude yang baru di-checkout
                ->exists();

            // Jika tidak ada kamar aktif, set status inactive
            if (!$hasActiveRoom) {
                $user->residentProfile->update([
                    'status' => 'inactive',
                ]);

                // Optional: nonaktifkan user
                $user->update([
                    'is_active' => false,
                ]);
            }
        }
    }

    /**
     * Auto assign PIC ke resident tertua di kamar (fallback)
     */
    protected function autoAssignPic(int $roomId): void
    {
        // Cari penghuni tertua yang masih aktif di kamar ini
        $oldestResident = RoomResident::where('room_id', $roomId)
            ->whereNull('check_out_date')
            ->orderBy('check_in_date', 'asc')
            ->first();

        if ($oldestResident) {
            $oldestResident->update(['is_pic' => true]);

            // Update history juga
            RoomHistory::where('room_resident_id', $oldestResident->id)
                ->whereNull('check_out_date')
                ->update(['is_pic' => true]);
        }
    }
}
