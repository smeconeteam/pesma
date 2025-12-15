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
use Illuminate\Validation\ValidationException;

class ResidentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) Pastikan role resident ada
            $residentRole = Role::firstOrCreate(['name' => 'resident']);

            // 2) Pastikan kategori penghuni ada (contoh: Pondok)
            $category = ResidentCategory::firstOrCreate(
                ['name' => 'Pondok'],
                ['description' => 'Seeder category']
            );

            // 3) Pastikan ada dorm, block, roomType, room
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
                ['description' => 'Seeder room type', 'default_capacity' => 8, 'default_monthly_rate' => 800000, 'is_active' => true]
            );

            // Pastikan room punya resident_category_id (boleh null saat create awal)
            $room = Room::firstOrCreate(
                ['block_id' => $block->id, 'code' => 'A-101'],
                [
                    'room_type_id' => $roomType->id,
                    'resident_category_id' => null, // nanti di-lock otomatis
                    'number' => '101',
                    'capacity' => 8,
                    'monthly_rate' => 800000,
                    'is_active' => true,
                ]
            );

            // 4) Buat user resident
            $user = User::firstOrCreate(
                ['email' => 'resident1@example.com'],
                [
                    'name' => 'Resident 1',
                    'password' => Hash::make('123456789'),
                    'is_active' => true,
                ]
            );

            // 5) Attach role resident
            $user->roles()->syncWithoutDetaching([$residentRole->id]);

            // 6) Buat/Update resident profile + kategori wajib
            ResidentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'resident_category_id' => $category->id, // ✅ wajib
                    'is_international' => false,
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

            // 7) LOCK kamar + validasi kategori + validasi gender + validasi PIC
            $roomId = $room->id;
            $today  = now()->toDateString();
            $wantPic = true;

            // lock room row
            $room = Room::query()->lockForUpdate()->findOrFail($roomId);

            // lock penghuni aktif kamar
            RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

            // ====== (A) KATEGORI ROOM ======
            $roomHasActive = RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->exists();

            if (! is_null($room->resident_category_id)) {
                if ((int) $room->resident_category_id !== (int) $category->id) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Seeder gagal: kategori penghuni tidak sesuai dengan kategori kamar.',
                    ]);
                }
            } else {
                // room category null
                if ($roomHasActive) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Seeder gagal: kategori kamar null tapi kamar sudah terisi (data tidak konsisten).',
                    ]);
                }

                // kamar kosong -> lock kategori kamar sesuai penghuni pertama
                $room->resident_category_id = $category->id;
                $room->save();
            }

            // ====== (B) GENDER ROOM (anti campur) ======
            $activeGender = RoomResident::query()
                ->where('room_residents.room_id', $roomId)
                ->whereNull('room_residents.check_out_date')
                ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                ->value('resident_profiles.gender');

            if ($activeGender && $activeGender !== 'M') {
                throw ValidationException::withMessages([
                    'room_id' => 'Seeder gagal: kamar sudah khusus gender lain.',
                ]);
            }

            // ====== (C) PIC ======
            if ($wantPic) {
                $hasPic = RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->where('is_pic', true)
                    ->exists();

                if ($hasPic) {
                    // jika sudah ada PIC, jadikan false (atau lempar error). Aku pilih tidak error agar idempotent.
                    $wantPic = false;
                }
            }

            // 8) Tempatkan ke kamar
            RoomResident::firstOrCreate(
                [
                    'room_id' => $roomId,
                    'user_id' => $user->id,
                    'check_in_date' => $today,
                ],
                [
                    'check_out_date' => null,
                    'is_pic' => $wantPic,
                ]
            );
        });
    }
}
