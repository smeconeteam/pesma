<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\ResidentProfile;
use App\Models\Room;
use App\Models\RoomResident;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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

        $profile = $record->residentProfile;

        $data['profile'] = [
            'resident_category_id'  => $profile?->resident_category_id, // ✅ TAMBAH
            'is_international'      => (bool) ($profile?->is_international ?? false),
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

        $active = $record->roomResidents()
            ->whereNull('check_out_date')
            ->latest('check_in_date')
            ->with(['room.block'])
            ->first();

        if ($active?->room) {
            $data['dorm_id']  = $active->room->block->dorm_id ?? null;
            $data['block_id'] = $active->room->block_id ?? null;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $profileData = $data['profile'] ?? [];
            $roomData    = $data['room'] ?? [];

            $newRoomId  = $roomData['room_id'] ?? null;
            $newCheckIn = isset($roomData['check_in_date'])
                ? Carbon::parse($roomData['check_in_date'])->toDateString()
                : null;
            $newIsPic   = (bool) ($roomData['is_pic'] ?? false);

            $gender = $profileData['gender'] ?? null;
            if (! in_array($gender, ['M', 'F'], true)) {
                throw ValidationException::withMessages([
                    'profile.gender' => 'Jenis kelamin wajib diisi.',
                ]);
            }

            $categoryId = $profileData['resident_category_id'] ?? null;
            if (blank($categoryId)) {
                throw ValidationException::withMessages([
                    'profile.resident_category_id' => 'Kategori penghuni wajib diisi.',
                ]);
            }

            // 1) update user
            $record->update([
                'email'     => $data['email'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'name'      => $profileData['full_name'] ?? $record->name,
            ]);

            // 2) upsert resident_profile
            ResidentProfile::updateOrCreate(
                ['user_id' => $record->id],
                [
                    'resident_category_id'  => $categoryId, // ✅ TAMBAH
                    'is_international'      => (bool) ($profileData['is_international'] ?? false),
                    'national_id'           => $profileData['national_id'] ?? null,
                    'student_id'            => $profileData['student_id'] ?? null,
                    'full_name'             => $profileData['full_name'] ?? $record->name,
                    'gender'                => $gender,
                    'birth_place'           => $profileData['birth_place'] ?? null,
                    'birth_date'            => $profileData['birth_date'] ?? null,
                    'university_school'     => $profileData['university_school'] ?? null,
                    'phone_number'          => $profileData['phone_number'] ?? null,
                    'guardian_name'         => $profileData['guardian_name'] ?? null,
                    'guardian_phone_number' => $profileData['guardian_phone_number'] ?? null,
                    'photo_path'            => $profileData['photo_path'] ?? null,
                ]
            );

            // 3) update penempatan kamar (boleh pindah kamar)
            if ($newRoomId && $newCheckIn) {
                // lock penghuni aktif kamar target + lock room target
                RoomResident::query()
                    ->where('room_id', $newRoomId)
                    ->whereNull('check_out_date')
                    ->lockForUpdate()
                    ->get();

                $targetRoom = Room::query()->lockForUpdate()->findOrFail($newRoomId);

                // ✅ validasi + auto-lock kategori kamar target
                $this->ensureRoomCategoryValidAndLock($targetRoom, (int) $categoryId);

                // cek gender kamar target dari penghuni aktif
                $activeGender = RoomResident::query()
                    ->where('room_residents.room_id', $newRoomId)
                    ->whereNull('room_residents.check_out_date')
                    ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                    ->value('resident_profiles.gender');

                if ($activeGender && $activeGender !== $gender) {
                    throw ValidationException::withMessages([
                        'room.room_id' => 'Kamar ini sudah khusus untuk gender lain (tidak boleh campur).',
                    ]);
                }

                $active = RoomResident::query()
                    ->where('user_id', $record->id)
                    ->whereNull('check_out_date')
                    ->latest('check_in_date')
                    ->first();

                if ($active && (int) $active->room_id === (int) $newRoomId) {
                    // masih kamar yang sama → update data aktif
                    if ($newIsPic) {
                        $hasPic = RoomResident::query()
                            ->where('room_id', $newRoomId)
                            ->whereNull('check_out_date')
                            ->where('is_pic', true)
                            ->where('id', '!=', $active->id)
                            ->exists();

                        if ($hasPic) {
                            throw ValidationException::withMessages([
                                'room.is_pic' => 'PIC aktif untuk kamar ini sudah ada. Tidak bisa menetapkan PIC kedua.',
                            ]);
                        }
                    }

                    $active->update([
                        'check_in_date' => $newCheckIn,
                        'is_pic'        => $newIsPic,
                    ]);
                } else {
                    // pindah kamar → tutup yang lama lalu buat yang baru
                    if ($active) {
                        $active->update([
                            'check_out_date' => Carbon::parse($newCheckIn)->subDay()->toDateString(),
                            'is_pic'         => false,
                        ]);
                    }

                    if ($newIsPic) {
                        $hasPic = RoomResident::query()
                            ->where('room_id', $newRoomId)
                            ->whereNull('check_out_date')
                            ->where('is_pic', true)
                            ->exists();

                        if ($hasPic) {
                            throw ValidationException::withMessages([
                                'room.is_pic' => 'PIC aktif untuk kamar ini sudah ada. Tidak bisa menetapkan PIC kedua.',
                            ]);
                        }
                    }

                    RoomResident::create([
                        'room_id'        => $newRoomId,
                        'user_id'        => $record->id,
                        'check_in_date'  => $newCheckIn,
                        'check_out_date' => null,
                        'is_pic'         => $newIsPic,
                    ]);
                }
            }

            return $record;
        });
    }

    private function ensureRoomCategoryValidAndLock(Room $room, int $residentCategoryId): void
    {
        $roomHasActive = RoomResident::query()
            ->where('room_id', $room->id)
            ->whereNull('check_out_date')
            ->exists();

        if (! is_null($room->resident_category_id)) {
            if ((int) $room->resident_category_id !== (int) $residentCategoryId) {
                throw ValidationException::withMessages([
                    'room.room_id' => 'Kategori penghuni tidak sesuai dengan kategori kamar.',
                ]);
            }
            return;
        }

        if ($roomHasActive) {
            throw ValidationException::withMessages([
                'room.room_id' => 'Kategori kamar belum ditentukan, tapi kamar sudah terisi. Periksa data kamar.',
            ]);
        }

        $room->resident_category_id = $residentCategoryId;
        $room->save();
    }
}
