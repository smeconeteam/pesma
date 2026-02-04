<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateResident extends CreateRecord
{
    protected static string $resource = ResidentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $profile = $data['profile'] ?? [];
            $room    = $data['room'] ?? [];

            $roomId    = $room['room_id'] ?? null;
            $checkIn   = $room['check_in_date'] ?? null;
            $wantPic   = (bool) ($room['is_pic'] ?? false);

            $gender    = $profile['gender'] ?? null;
            $categoryId = $profile['resident_category_id'] ?? null;

            if (! in_array($gender, ['M', 'F'], true)) {
                throw ValidationException::withMessages([
                    'profile.gender' => 'Jenis kelamin wajib diisi.',
                ]);
            }

            if (blank($categoryId)) {
                throw ValidationException::withMessages([
                    'profile.resident_category_id' => 'Kategori penghuni wajib diisi.',
                ]);
            }

            if (blank($roomId) || blank($checkIn)) {
                throw ValidationException::withMessages([
                    'room.room_id' => 'Kamar wajib dipilih.',
                    'room.check_in_date' => 'Tanggal masuk wajib diisi.',
                ]);
            }

            // ====== LOCK ROOM (mencegah submit barengan) ======
            // lock penghuni aktif kamar ini
            RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

            // lock row kamar juga (biar aman saat auto-lock kategori kamar)
            $roomModel = Room::query()->lockForUpdate()->findOrFail($roomId);

            // ====== VALIDASI KATEGORI KAMAR + AUTO-LOCK ======
            $this->ensureRoomCategoryValidAndLock($roomModel, (int) $categoryId);

            // ====== VALIDASI GENDER KAMAR ======
            $activeGender = RoomResident::query()
                ->where('room_residents.room_id', $roomId)
                ->whereNull('room_residents.check_out_date')
                ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                ->value('resident_profiles.gender');

            if ($activeGender && $activeGender !== $gender) {
                throw ValidationException::withMessages([
                    'room.room_id' => 'Kamar ini sudah khusus untuk gender lain (tidak boleh campur).',
                ]);
            }

            // ====== VALIDASI PIC ======
            if ($wantPic) {
                $hasPic = RoomResident::query()
                    ->where('room_residents.room_id', $roomId)
                    ->whereNull('room_residents.check_out_date')
                    ->where('room_residents.is_pic', true)
                    ->exists();

                if ($hasPic) {
                    throw ValidationException::withMessages([
                        'room.is_pic' => 'PIC aktif untuk kamar ini sudah ada. Tidak bisa menetapkan PIC kedua.',
                    ]);
                }
            }

            // ====== 1) create user ======
            $user = User::create([
                'name'      => $profile['full_name'] ?? 'Resident',
                'email'     => $data['email'],
                'password'  => Hash::make('123456789'),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            // ====== 2) attach role resident ======
            $residentRole = Role::firstOrCreate(['name' => 'resident']);
            $user->roles()->syncWithoutDetaching([$residentRole->id]);

            // ====== 3) create resident profile ======
            ResidentProfile::create([
                'user_id'               => $user->id,
                'resident_category_id'  => $categoryId,
                'is_international'      => (bool) ($profile['is_international'] ?? false),
                'national_id'           => $profile['national_id'] ?? null,
                'student_id'            => $profile['student_id'] ?? null,
                'full_name'             => $profile['full_name'] ?? $user->name,
                'gender'                => $gender,
                'birth_place'           => $profile['birth_place'] ?? null,
                'birth_date'            => $profile['birth_date'] ?? null,
                'university_school'     => $profile['university_school'] ?? null,
                'phone_number'          => $profile['phone_number'] ?? null,
                'guardian_name'         => $profile['guardian_name'] ?? null,
                'guardian_phone_number' => $profile['guardian_phone_number'] ?? null,
                'address'               => $profile['address'] ?? null,
                'photo_path'            => $profile['photo_path'] ?? null,
            ]);

            // ====== 4) create room_residents (penempatan kamar) ======
            RoomResident::create([
                'room_id'        => $roomId,
                'user_id'        => $user->id,
                'check_in_date'  => $checkIn,
                'check_out_date' => null,
                'is_pic'         => $wantPic,
            ]);

            Notification::make()
                ->title('Penghuni berhasil dibuat')
                ->body('Password default: 123456789')
                ->success()
                ->send();

            return $user;
        });
    }

    /**
     * Rule:
     * - Jika room.resident_category_id sudah ada -> harus sama
     * - Jika NULL dan kamar kosong -> lock ke kategori penghuni pertama
     * - Jika NULL tapi kamar sudah terisi -> data tidak konsisten -> tolak
     */
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