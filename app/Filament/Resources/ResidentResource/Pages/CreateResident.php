<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\RoomResident;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

            // 1) create user (name diambil dari full_name)
            $user = User::create([
                'name'      => $profile['full_name'] ?? 'Resident',
                'email'     => $data['email'],
                'password'  => Hash::make('123456789'),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            // 2) attach role resident
            $residentRole = Role::firstOrCreate(['name' => 'resident']);
            $user->roles()->syncWithoutDetaching([$residentRole->id]);

            // 3) create resident profile
            ResidentProfile::create([
                'user_id'               => $user->id,
                'national_id'           => $profile['national_id'] ?? null,
                'student_id'            => $profile['student_id'] ?? null,
                'full_name'             => $profile['full_name'] ?? $user->name,
                'gender'                => $profile['gender'] ?? null,
                'birth_place'           => $profile['birth_place'] ?? null,
                'birth_date'            => $profile['birth_date'] ?? null,
                'university_school'     => $profile['university_school'] ?? null,
                'phone_number'          => $profile['phone_number'] ?? null,
                'guardian_name'         => $profile['guardian_name'] ?? null,
                'guardian_phone_number' => $profile['guardian_phone_number'] ?? null,
                'check_in_date'         => $room['check_in_date'] ?? null,
                'check_out_date'        => null,
                'photo_path'            => $profile['photo_path'] ?? null,
            ]);

            // 4) create room_residents (penempatan kamar)
            RoomResident::create([
                'room_id'       => $room['room_id'],
                'user_id'       => $user->id,
                'check_in_date' => $room['check_in_date'],
                'check_out_date'=> null,
                'is_pic'        => (bool) ($room['is_pic'] ?? false),
            ]);

            Notification::make()
                ->title('Penghuni berhasil dibuat')
                ->body('Password default: 123456789')
                ->success()
                ->send();

            return $user;
        });
    }
}
