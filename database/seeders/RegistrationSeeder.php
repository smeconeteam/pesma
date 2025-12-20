<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Country;
use App\Models\ResidentCategory;
use App\Models\Dorm;
use App\Models\RoomType;

class RegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $country = Country::updateOrCreate(
                ['iso2' => 'ID'],
                ['iso3' => 'IDN', 'name' => 'Indonesia', 'calling_code' => '62']
            );

            $category = ResidentCategory::firstOrCreate(
                ['name' => 'wisma'],
                ['description' => 'Tarif normal']
            );

            $dorm = Dorm::firstOrCreate(
                ['name' => 'Cabang Utama'],
                ['address' => 'Alamat contoh', 'description' => 'Seeder dorm']
            );

            $roomType = RoomType::firstOrCreate(
                ['name' => 'Regular'],
                ['description' => 'Seeder room type', 'default_capacity' => 8, 'default_monthly_rate' => 800000]
            );

            $now = now();

            DB::table('registrations')->insert([
                'status' => 'pending',
                'rejection_reason' => null,
                'email' => 'applicant1@example.com',
                'name' => 'Applicant 1',
                'password' => Hash::make('password123'),
                'resident_category_id' => $category->id,
                'citizenship_status' => 'WNI',
                'country_id' => $country->id,
                'national_id' => '3276000000000001',
                'student_id' => 'STU001',
                'full_name' => 'Applicant One',
                'gender' => 'F',
                'birth_place' => 'Jakarta',
                'birth_date' => '2002-05-15',
                'university_school' => 'Contoh University',
                'phone_number' => '081234567891',
                'guardian_name' => 'Parent',
                'guardian_phone_number' => '081299988877',
                'photo_path' => null,
                'preferred_dorm_id' => $dorm->id,
                'preferred_room_type_id' => $roomType->id,
                'planned_check_in_date' => $now->copy()->addWeeks(2)->toDateString(),
                'approved_by' => null,
                'approved_at' => null,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }
}
