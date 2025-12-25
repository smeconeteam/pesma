<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Dorm;
use App\Models\ResidentCategory;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /**
             * =========================================================
             * 1) ROLES
             * =========================================================
             */
            $roleNames = [
                'super_admin',
                'main_admin',
                'branch_admin',
                'block_admin',
                'resident',
            ];

            foreach ($roleNames as $name) {
                Role::firstOrCreate(['name' => $name]);
            }

            $roles = Role::whereIn('name', $roleNames)->get()->keyBy('name');

            /**
             * =========================================================
             * 2) USER ACCOUNTS
             * =========================================================
             */
            $superAdmin = User::firstOrCreate(
                ['email' => 'superadmin@example.com'],
                [
                    'name'      => 'Super Admin',
                    'password'  => Hash::make('123456789'),
                    'is_active' => true,
                ]
            );
            $superAdmin->roles()->syncWithoutDetaching([$roles['super_admin']->id]);

            $resident = User::firstOrCreate(
                ['email' => 'resident@example.com'],
                [
                    'name'      => 'Example Resident',
                    'password'  => Hash::make('123456789'),
                    'is_active' => true,
                ]
            );
            $resident->roles()->syncWithoutDetaching([$roles['resident']->id]);

            $branchAdminResident = User::firstOrCreate(
                ['email' => 'branch-resident@example.com'],
                [
                    'name'      => 'Resident Admin Cabang',
                    'password'  => Hash::make('123456789'),
                    'is_active' => true,
                ]
            );
            $branchAdminResident->roles()->syncWithoutDetaching([
                $roles['resident']->id,
                $roles['branch_admin']->id,
            ]);

            $blockAdminResident = User::firstOrCreate(
                ['email' => 'block-resident@example.com'],
                [
                    'name'      => 'Resident Admin Komplek',
                    'password'  => Hash::make('123456789'),
                    'is_active' => true,
                ]
            );
            $blockAdminResident->roles()->syncWithoutDetaching([
                $roles['resident']->id,
                $roles['block_admin']->id,
            ]);

            /**
             * =========================================================
             * 3) DATA MASTER UNTUK PENEMPATAN KAMAR
             * =========================================================
             */
            $category = ResidentCategory::firstOrCreate(
                ['name' => 'Pondok'],
                ['description' => 'Seeder kategori']
            );

            $dorm = Dorm::firstOrCreate(
                ['name' => 'Cabang Utama'],
                ['address' => 'Alamat contoh', 'description' => 'Seeder dorm', 'is_active' => true]
            );

            $block = Block::firstOrCreate(
                ['dorm_id' => $dorm->id, 'name' => 'Blok A'],
                ['description' => 'Seeder block', 'is_active' => true]
            );

            $roomType = RoomType::firstOrCreate(
                ['name' => 'Regular'],
                [
                    'description' => 'Seeder room type',
                    'default_capacity' => 8,
                    'default_monthly_rate' => 800000,
                    'is_active' => true,
                ]
            );

            $room = Room::firstOrCreate(
                ['block_id' => $block->id, 'code' => 'A-101'],
                [
                    'room_type_id' => $roomType->id,
                    'resident_category_id' => null, // auto-lock nanti
                    'number' => '101',
                    'capacity' => 8,
                    'monthly_rate' => 800000,
                    'is_active' => true,
                ]
            );

            /**
             * =========================================================
             * 4) BUAT DATA PENGHUNI (KECUALI SUPER ADMIN)
             * =========================================================
             */
            $users = User::whereHas('roles', fn ($q) => $q->where('name', 'resident'))
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
                ->get();

            foreach ($users as $index => $user) {
                // ===== LOCK ROOM =====
                $room = Room::query()->lockForUpdate()->findOrFail($room->id);

                // ===== AUTO-LOCK KATEGORI ROOM (hanya jika NULL) =====
                if (is_null($room->resident_category_id)) {
                    $room->resident_category_id = $category->id;
                    $room->save();
                }

                // ===== Resident Profile =====
                $profile = ResidentProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'resident_category_id' => $category->id,
                        'is_international' => false,
                        'national_id' => '32000000000000' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                        'student_id'  => 'NIM' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                        'full_name'   => $user->name,
                        'gender'      => 'M', // samakan agar tidak konflik
                        'birth_place' => 'Bandung',
                        'birth_date'  => '2003-01-01',
                        'university_school' => 'Contoh University',
                        'phone_number' => '0812345678' . str_pad($index, 2, '0', STR_PAD_LEFT),
                        'guardian_name' => 'Orang Tua',
                        'guardian_phone_number' => '0812987654' . str_pad($index, 2, '0', STR_PAD_LEFT),
                        'photo_path' => null,
                    ]
                );

                // ===== PIC: hanya penghuni pertama =====
                $isPic = ($index === 0);

                // Jika bukan penghuni pertama, cek apakah sudah ada PIC
                if ($isPic) {
                    $hasPic = RoomResident::query()
                        ->where('room_id', $room->id)
                        ->whereNull('check_out_date')
                        ->where('is_pic', true)
                        ->exists();

                    if ($hasPic) {
                        $isPic = false;
                    }
                }

                // ===== ROOM RESIDENT =====
                RoomResident::firstOrCreate(
                    [
                        'room_id' => $room->id,
                        'user_id' => $user->id,
                        'check_in_date' => now()->toDateString(),
                    ],
                    [
                        'check_out_date' => null,
                        'is_pic' => $isPic,
                    ]
                );
            }
        });
    }
}