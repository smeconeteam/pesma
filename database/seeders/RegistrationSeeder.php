<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Dorm;
use App\Models\Registration;
use App\Models\ResidentCategory;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $indonesia = Country::where('iso2', 'ID')->first();
        $malaysia = Country::where('iso2', 'MY')->first();

        $wisma = ResidentCategory::where('name', 'Wisma')->first();
        $pondok = ResidentCategory::where('name', 'Pondok')->first();
        $asrama = ResidentCategory::where('name', 'Asrama')->first();

        $grendeng = Dorm::where('name', 'Cabang Grendeng')->first();
        $banyumas = Dorm::where('name', 'Cabang Banyumas')->first();
        $sokaraja = Dorm::where('name', 'Cabang Sokaraja')->first();

        $istana = RoomType::where('name', 'Istana')->first();
        $megah = RoomType::where('name', 'Megah')->first();
        $reguler = RoomType::where('name', 'Reguler 4')->first();

        $registrations = [
            // PENDING - Menunggu approval
            [
                'status' => 'pending',
                'email' => 'ahmad.fauzi@gmail.com',
                'name' => 'Ahmad Fauzi',
                'password' => Hash::make('123456789'),
                'resident_category_id' => $pondok?->id,
                'citizenship_status' => 'WNI',
                'country_id' => $indonesia?->id,
                'national_id' => '3302121506050001',
                'student_id' => '2024010001',
                'full_name' => 'Ahmad Fauzi Rahman',
                'gender' => 'M',
                'birth_place' => 'Purwokerto',
                'birth_date' => '2005-06-15',
                'university_school' => 'Universitas Jenderal Soedirman',
                'phone_number' => '081234567801',
                'guardian_name' => 'Bapak Rahman Hakim',
                'guardian_phone_number' => '081298765401',
                'preferred_dorm_id' => $banyumas?->id,
                'preferred_room_type_id' => $reguler?->id,
                'planned_check_in_date' => now()->addWeeks(2)->toDateString(),
            ],

            [
                'status' => 'pending',
                'email' => 'siti.nurhaliza@gmail.com',
                'name' => 'Siti Nurhaliza',
                'password' => Hash::make('123456789'),
                'resident_category_id' => $wisma?->id,
                'citizenship_status' => 'WNI',
                'country_id' => $indonesia?->id,
                'national_id' => '3302124508050002',
                'student_id' => '2024010002',
                'full_name' => 'Siti Nurhaliza Azzahra',
                'gender' => 'F',
                'birth_place' => 'Banyumas',
                'birth_date' => '2005-08-25',
                'university_school' => 'IAIN Purwokerto',
                'phone_number' => '081234567802',
                'guardian_name' => 'Ibu Siti Aisyah',
                'guardian_phone_number' => '081298765402',
                'preferred_dorm_id' => $sokaraja?->id,
                'preferred_room_type_id' => $megah?->id,
                'planned_check_in_date' => now()->addWeeks(3)->toDateString(),
            ],

            // APPROVED - Sudah disetujui tapi belum ditempatkan
            [
                'status' => 'approved',
                'email' => 'budi.santoso@gmail.com',
                'name' => 'Budi Santoso',
                'password' => Hash::make('123456789'),
                'resident_category_id' => $asrama?->id,
                'citizenship_status' => 'WNI',
                'country_id' => $indonesia?->id,
                'national_id' => '3302123007050003',
                'student_id' => '2024010003',
                'full_name' => 'Budi Santoso Wijaya',
                'gender' => 'M',
                'birth_place' => 'Cilacap',
                'birth_date' => '2005-07-30',
                'university_school' => 'Politeknik Negeri Cilacap',
                'phone_number' => '081234567803',
                'guardian_name' => 'Bapak Santoso',
                'guardian_phone_number' => '081298765403',
                'preferred_dorm_id' => $grendeng?->id,
                'preferred_room_type_id' => $reguler?->id,
                'planned_check_in_date' => now()->addDays(5)->toDateString(),
                'approved_by' => 1, // Super Admin
                'approved_at' => now()->subDays(2),
            ],

            // REJECTED
            [
                'status' => 'rejected',
                'rejection_reason' => 'Dokumen tidak lengkap. Mohon lengkapi KTP dan Surat Keterangan dari kampus.',
                'email' => 'rina.wati@gmail.com',
                'name' => 'Rina Wati',
                'password' => Hash::make('123456789'),
                'resident_category_id' => $wisma?->id,
                'citizenship_status' => 'WNI',
                'country_id' => $indonesia?->id,
                'national_id' => '3302121209050004',
                'student_id' => '2024010004',
                'full_name' => 'Rina Wati Kusuma',
                'gender' => 'F',
                'birth_place' => 'Purbalingga',
                'birth_date' => '2005-09-12',
                'university_school' => 'Universitas Muhammadiyah Purwokerto',
                'phone_number' => '081234567804',
                'guardian_name' => 'Ibu Kusuma Dewi',
                'guardian_phone_number' => '081298765404',
                'preferred_dorm_id' => $sokaraja?->id,
                'preferred_room_type_id' => $megah?->id,
                'planned_check_in_date' => now()->addWeeks(1)->toDateString(),
            ],

            // PENDING - WNA (Malaysia)
            [
                'status' => 'pending',
                'email' => 'fatimah.zahra@gmail.com',
                'name' => 'Fatimah Zahra',
                'password' => Hash::make('123456789'),
                'resident_category_id' => $wisma?->id,
                'citizenship_status' => 'WNA',
                'country_id' => $malaysia?->id,
                'national_id' => '850515086234',
                'student_id' => '2024010005',
                'full_name' => 'Fatimah Zahra binti Abdullah',
                'gender' => 'F',
                'birth_place' => 'Kuala Lumpur',
                'birth_date' => '2005-05-15',
                'university_school' => 'Universitas Jenderal Soedirman',
                'phone_number' => '60123456789',
                'guardian_name' => 'Abdullah bin Ahmad',
                'guardian_phone_number' => '60198765432',
                'preferred_dorm_id' => $sokaraja?->id,
                'preferred_room_type_id' => $istana?->id,
                'planned_check_in_date' => now()->addMonth()->toDateString(),
            ],
        ];

        foreach ($registrations as $data) {
            Registration::firstOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}