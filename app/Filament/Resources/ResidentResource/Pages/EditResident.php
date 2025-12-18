<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\ResidentProfile;
use App\Models\RoomResident;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EditResident extends EditRecord
{
    protected static string $resource = ResidentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        // ====== Resident Profile ======
        $profile = $record->residentProfile;

        $data['profile'] = [
            'resident_category_id'  => $profile?->resident_category_id,
            'is_international'      => $profile?->is_international,
            'national_id'           => $profile?->national_id,
            'student_id'            => $profile?->student_id,
            'full_name'             => $profile?->full_name ?? $record->name,
            'gender'                => $profile?->gender,
            'birth_place'           => $profile?->birth_place,
            'birth_date'            => $profile?->birth_date?->format('Y-m-d'),
            'university_school'     => $profile?->university_school,
            'phone_number'          => $profile?->phone_number,
            'guardian_name'         => $profile?->guardian_name,
            'guardian_phone_number' => $profile?->guardian_phone_number,
            'photo_path'            => $profile?->photo_path,
        ];

        // ====== Room Placement (ambil kamar aktif) ======
        $active = $record->roomResidents()
            ->whereNull('check_out_date')
            ->latest('check_in_date')
            ->with(['room.block']) // supaya bisa isi dorm & block
            ->first();

        if ($active?->room) {
            $data['dorm_id'] = $active->room->block->dorm_id ?? null; // untuk filter
            $data['block_id'] = $active->room->block_id ?? null;      // untuk filter

            $data['room'] = [
                'room_id'       => $active->room_id,
                'check_in_date' => $active->check_in_date?->format('Y-m-d'),
                'is_pic'        => (bool) $active->is_pic,
            ];
        } else {
            $data['dorm_id'] = null;
            $data['block_id'] = null;
            $data['room'] = [
                'room_id'       => null,
                'check_in_date' => now()->format('Y-m-d'),
                'is_pic'        => false,
            ];
        }

        return $data;
    }

    /**
     * Simpan perubahan ke users + resident_profiles + room_residents
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $profileData = $data['profile'] ?? [];
            $roomData    = $data['room'] ?? [];

            // 1) update user
            $record->update([
                'email'     => $data['email'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                // optional: sync name dari full_name biar konsisten
                'name'      => $profileData['full_name'] ?? $record->name,
            ]);

            // 2) upsert resident_profile
            ResidentProfile::updateOrCreate(
                ['user_id' => $record->id],
                [
                    'resident_category_id'  => $profileData['resident_category_id'] ?? null,
                    'is_international'      => $profileData['is_international'] ?? null,
                    'national_id'           => $profileData['national_id'] ?? null,
                    'student_id'            => $profileData['student_id'] ?? null,
                    'full_name'             => $profileData['full_name'] ?? $record->name,
                    'gender'                => $profileData['gender'] ?? null,
                    'birth_place'           => $profileData['birth_place'] ?? null,
                    'birth_date'            => $profileData['birth_date'] ?? null,
                    'university_school'     => $profileData['university_school'] ?? null,
                    'phone_number'          => $profileData['phone_number'] ?? null,
                    'guardian_name'         => $profileData['guardian_name'] ?? null,
                    'guardian_phone_number' => $profileData['guardian_phone_number'] ?? null,
                    'photo_path'            => $profileData['photo_path'] ?? null,
                ]
            );

            // 3) update penempatan kamar (opsional: boleh pindah kamar)
            $newRoomId   = $roomData['room_id'] ?? null;
            $newCheckIn  = isset($roomData['check_in_date']) ? Carbon::parse($roomData['check_in_date'])->toDateString() : null;
            $newIsPic    = (bool) ($roomData['is_pic'] ?? false);

            if ($newRoomId && $newCheckIn) {
                $active = RoomResident::query()
                    ->where('user_id', $record->id)
                    ->whereNull('check_out_date')
                    ->latest('check_in_date')
                    ->first();

                if ($active && (int) $active->room_id === (int) $newRoomId) {
                    // masih kamar yang sama → update data aktif
                    $active->update([
                        'check_in_date' => $newCheckIn,
                        'is_pic'        => $newIsPic,
                    ]);
                } else {
                    // pindah kamar → tutup yang lama lalu buat yang baru
                    if ($active) {
                        $active->update([
                            'check_out_date' => Carbon::parse($newCheckIn)->subDay()->toDateString(),
                            'is_pic' => false,
                        ]);
                    }

                    RoomResident::create([
                        'room_id'       => $newRoomId,
                        'user_id'       => $record->id,
                        'check_in_date' => $newCheckIn,
                        'check_out_date' => null,
                        'is_pic'        => $newIsPic,
                    ]);
                }
            }

            return $record;
        });
    }
}
