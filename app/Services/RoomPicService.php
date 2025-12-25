<?php

namespace App\Services;

use App\Models\RoomResident;
use App\Models\RoomHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomPicService
{
    /**
     * Memastikan setiap kamar selalu punya 1 PIC aktif
     * Dipanggil ketika PIC meninggalkan kamar
     * 
     * @param int $roomId
     * @return bool
     */
    public function ensurePicForRoom(int $roomId): bool
    {
        try {
            return DB::transaction(function () use ($roomId) {
                // Cek apakah kamar masih punya PIC aktif
                $currentPic = RoomResident::where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->where('is_pic', true)
                    ->exists();

                // Jika masih ada PIC, tidak perlu assign baru
                if ($currentPic) {
                    return true;
                }

                // Cari resident aktif di kamar (yang belum checkout)
                $activeResidents = RoomResident::where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->orderBy('check_in_date', 'asc') // Yang paling lama tinggal jadi PIC
                    ->get();

                // Jika tidak ada resident aktif, tidak perlu assign PIC
                if ($activeResidents->isEmpty()) {
                    return true;
                }

                // Jika hanya 1 resident, otomatis jadi PIC
                if ($activeResidents->count() === 1) {
                    $newPic = $activeResidents->first();
                } else {
                    // Jika lebih dari 1, pilih yang paling lama tinggal
                    $newPic = $activeResidents->first();
                }

                // Update menjadi PIC
                $newPic->update(['is_pic' => true]);

                // Update RoomHistory
                RoomHistory::where('room_resident_id', $newPic->id)
                    ->whereNull('check_out_date')
                    ->update([
                        'is_pic' => true,
                        'notes' => 'Auto-assigned sebagai PIC karena PIC sebelumnya meninggalkan kamar',
                    ]);

                Log::info("Auto-assigned PIC untuk room #{$roomId}: User #{$newPic->user_id}");

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Error ensuring PIC for room #{$roomId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transfer PIC ke resident lain di kamar yang sama
     * 
     * @param int $currentPicId - ID RoomResident yang sekarang jadi PIC
     * @param int $newPicId - ID RoomResident yang akan jadi PIC baru
     * @return bool
     */
    public function transferPic(int $currentPicId, int $newPicId): bool
    {
        try {
            return DB::transaction(function () use ($currentPicId, $newPicId) {
                $currentPic = RoomResident::findOrFail($currentPicId);
                $newPic = RoomResident::findOrFail($newPicId);

                // Validasi: harus di kamar yang sama
                if ($currentPic->room_id !== $newPic->room_id) {
                    throw new \Exception('PIC transfer hanya bisa dilakukan dalam kamar yang sama');
                }

                // Validasi: keduanya harus aktif (belum checkout)
                if (!is_null($currentPic->check_out_date) || !is_null($newPic->check_out_date)) {
                    throw new \Exception('PIC transfer hanya bisa dilakukan untuk resident aktif');
                }

                // Update PIC lama
                $currentPic->update(['is_pic' => false]);
                RoomHistory::where('room_resident_id', $currentPic->id)
                    ->whereNull('check_out_date')
                    ->update([
                        'is_pic' => false,
                        'notes' => 'PIC dipindahkan ke resident lain',
                    ]);

                // Update PIC baru
                $newPic->update(['is_pic' => true]);
                RoomHistory::where('room_resident_id', $newPic->id)
                    ->whereNull('check_out_date')
                    ->update([
                        'is_pic' => true,
                        'notes' => 'Ditunjuk sebagai PIC baru',
                    ]);

                Log::info("PIC transferred in room #{$currentPic->room_id}: User #{$currentPic->user_id} -> User #{$newPic->user_id}");

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Error transferring PIC: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validasi: pastikan hanya ada 1 PIC per kamar
     * Dipanggil untuk data cleanup/audit
     * 
     * @param int|null $roomId - Jika null, check semua kamar
     * @return array
     */
    public function validatePicPerRoom(?int $roomId = null): array
    {
        $query = DB::table('room_residents')
            ->select('room_id', DB::raw('COUNT(*) as pic_count'))
            ->whereNull('check_out_date')
            ->where('is_pic', true)
            ->groupBy('room_id')
            ->havingRaw('COUNT(*) != 1'); // Kamar dengan PIC != 1

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        $invalidRooms = $query->get();

        $results = [
            'valid' => $invalidRooms->isEmpty(),
            'invalid_rooms' => [],
        ];

        foreach ($invalidRooms as $room) {
            $results['invalid_rooms'][] = [
                'room_id' => $room->room_id,
                'pic_count' => $room->pic_count,
                'status' => $room->pic_count === 0 ? 'NO_PIC' : 'MULTIPLE_PIC',
            ];
        }

        return $results;
    }

    /**
     * Fix kamar yang tidak punya PIC atau punya lebih dari 1 PIC
     * 
     * @param int|null $roomId
     * @return array
     */
    public function fixInvalidPicRooms(?int $roomId = null): array
    {
        $validation = $this->validatePicPerRoom($roomId);

        if ($validation['valid']) {
            return ['status' => 'success', 'message' => 'Semua kamar valid', 'fixed' => 0];
        }

        $fixed = 0;

        foreach ($validation['invalid_rooms'] as $invalidRoom) {
            $roomId = $invalidRoom['room_id'];
            $status = $invalidRoom['status'];

            if ($status === 'NO_PIC') {
                // Assign PIC baru
                if ($this->ensurePicForRoom($roomId)) {
                    $fixed++;
                }
            } elseif ($status === 'MULTIPLE_PIC') {
                // Reset semua PIC, lalu assign ulang
                RoomResident::where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->update(['is_pic' => false]);

                if ($this->ensurePicForRoom($roomId)) {
                    $fixed++;
                }
            }
        }

        return [
            'status' => 'success',
            'message' => "Fixed {$fixed} kamar",
            'fixed' => $fixed,
            'details' => $validation['invalid_rooms'],
        ];
    }
}
