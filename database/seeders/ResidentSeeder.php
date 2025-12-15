<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Dorm;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResidentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Pastikan role resident ada
            $residentRole = Role::firstOrCreate(['name' => 'resident']);

            // Pastikan ada 1 kamar untuk contoh
            $dorm = Dorm::firstOrCreate(
                ['name' => 'Cabang Utama'],
                ['address' => 'Alamat contoh', 'description' => 'Seeder dorm']
            );

            $block = Block::firstOrCreate(
                ['dorm_id' => $dorm->id, 'name' => 'Blok A'],
                ['description' => 'Seeder block']
            );

            $roomType = RoomType::firstOrCreate(
                ['name' => 'Regular'],
                ['description' => 'Seeder room type', 'default_capacity' => 8, 'default_monthly_rate' => 800000]
            );

            $room = Room::firstOrCreate(
                ['block_id' => $block->id, 'code' => 'A-101'],
                [
                    'room_type_id' => $roomType->id,
                    'number' => '101',
                    'capacity' => 8,
                    'monthly_rate' => 800000,
                    'is_active' => true,
                ]
            );

            // Buat user resident
            $user = User::firstOrCreate(
                ['email' => 'resident1@example.com'],
                [
                    'name' => 'Resident 1',
                    'password' => Hash::make('123456789'),
                    'is_active' => true,
                ]
            );

            // Attach role resident (pivot role_user)
            if (! $user->roles()->where('roles.id', $residentRole->id)->exists()) {
                $user->roles()->attach($residentRole->id);
            }

            // Buat/Update resident profile
            ResidentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'national_id' => '3200000000000001',
                    'student_id'  => 'NIM001',
                    'full_name'   => 'Resident 1',
                    'gender'      => 'M',
                    'birth_place' => 'Bandung',
                    'birth_date'  => '2004-01-01',
                    'university_school' => 'Contoh University',
                    'phone_number' => '081234567890',
                    'guardian_name' => 'Orang Tua',
                    'guardian_phone_number' => '081298765432',
                    'check_in_date' => now()->toDateString(),
                    'check_out_date' => null,
                    'photo_path' => null,
                ]
            );

            // Tempatkan ke kamar (langsung = opsi A)
            RoomResident::firstOrCreate(
                [
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                    'check_in_date' => now()->toDateString(),
                ],
                [
                    'check_out_date' => null,
                    'is_pic' => true,
                ]
            );
        });
    }
}
