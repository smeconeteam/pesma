<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\Country;
use App\Models\Role;
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
            $profile = $data['residentProfile'] ?? [];
            $room    = $data['room_assignment'] ?? [];

            if (empty($profile['full_name'])) {
                throw ValidationException::withMessages([
                    'residentProfile.full_name' => 'Nama Lengkap wajib diisi.',
                ]);
            }

            // WNI => Indonesia
            if (($profile['citizenship_status'] ?? 'WNI') === 'WNI') {
                $indoId = Country::query()->where('iso2', 'ID')->value('id');
                if ($indoId) {
                    $profile['country_id'] = $indoId;
                }
            }

            // 1) user
            $user = User::create([
                'name'      => $data['name'] ?? $profile['full_name'],
                'email'     => $data['email'],
                'password'  => Hash::make('123456789'),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            // 2) role resident
            $residentRole = Role::firstOrCreate(['name' => 'resident']);
            $user->roles()->syncWithoutDetaching([$residentRole->id]);

            // 3) profile
            $user->residentProfile()->create($profile);

            /**
             * 4) Penempatan kamar (OPSIONAL)
             * Jalan hanya jika room_id diisi.
             */
            $roomId  = $room['room_id'] ?? null;

            if ($roomId) {
                $checkIn = $room['check_in_date'] ?? now()->toDateString();
                $wantPic = (bool) ($room['is_pic'] ?? false);
                $gender  = $profile['gender'] ?? null;

                if (! in_array($gender, ['M', 'F'], true)) {
                    throw ValidationException::withMessages([
                        'residentProfile.gender' => 'Jenis kelamin wajib diisi jika ingin memilih kamar.',
                    ]);
                }

                // lock penghuni aktif kamar (hindari submit barengan)
                RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->lockForUpdate()
                    ->get();

                // cek gender kamar dari penghuni aktif
                $activeGender = RoomResident::query()
                    ->where('room_residents.room_id', $roomId)
                    ->whereNull('room_residents.check_out_date')
                    ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                    ->value('resident_profiles.gender');

                if ($activeGender && $activeGender !== $gender) {
                    throw ValidationException::withMessages([
                        'room_assignment.room_id' => 'Kamar ini sudah khusus untuk gender lain (tidak boleh campur).',
                    ]);
                }

                // PIC rule: hanya 1 PIC aktif
                $activeCount = RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->count();

                $hasPic = RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->where('is_pic', true)
                    ->exists();

                // jika kamar kosong => penghuni pertama otomatis PIC
                if ($activeCount === 0) {
                    $wantPic = true;
                } elseif ($wantPic && $hasPic) {
                    throw ValidationException::withMessages([
                        'room_assignment.is_pic' => 'PIC aktif untuk kamar ini sudah ada.',
                    ]);
                }

                RoomResident::create([
                    'room_id'        => $roomId,
                    'user_id'        => $user->id,
                    'check_in_date'  => $checkIn,
                    'check_out_date' => null,
                    'is_pic'         => $wantPic,
                ]);
            }

            Notification::make()
                ->title('Penghuni berhasil dibuat')
                ->body('Password default: 123456789')
                ->success()
                ->send();

            return $user;
        });
    }
}
