<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\ResidentCategory;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomHistory;
use App\Models\RoomResident;
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
            $residentRole = Role::where('name', 'resident')->first();
            $indonesia = Country::where('iso2', 'ID')->first();
            $superAdmin = User::where('email', 'superadmin@example.com')->first();

            if (!$residentRole || !$indonesia || !$superAdmin) {
                return;
            }

            // Data penghuni realistis
            $residents = [
                // PENGHUNI PUTRA - Cabang Grendeng
                [
                    'email' => 'muhammad.rizki@gmail.com',
                    'name' => 'Muhammad Rizki',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Pondok')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302120101040001',
                        'student_id' => '2023010001',
                        'full_name' => 'Muhammad Rizki Ananda',
                        'gender' => 'M',
                        'birth_place' => 'Purwokerto',
                        'birth_date' => '2004-01-01',
                        'university_school' => 'Universitas Jenderal Soedirman',
                        'phone_number' => '081234561001',
                        'guardian_name' => 'Bapak Ananda Wijaya',
                        'guardian_phone_number' => '081298761001',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-grendeng-komplek-sejahtera-reguler-4-01',
                    'check_in_date' => now()->subMonths(6)->toDateString(),
                    'is_pic' => true,
                ],

                [
                    'email' => 'dedi.kurniawan@gmail.com',
                    'name' => 'Dedi Kurniawan',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Pondok')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302121503040002',
                        'student_id' => '2023010002',
                        'full_name' => 'Dedi Kurniawan Saputra',
                        'gender' => 'M',
                        'birth_place' => 'Banyumas',
                        'birth_date' => '2004-03-15',
                        'university_school' => 'Universitas Jenderal Soedirman',
                        'phone_number' => '081234561002',
                        'guardian_name' => 'Bapak Kurniawan',
                        'guardian_phone_number' => '081298761002',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-grendeng-komplek-sejahtera-reguler-4-01',
                    'check_in_date' => now()->subMonths(5)->toDateString(),
                    'is_pic' => false,
                ],

                [
                    'email' => 'agus.santoso@gmail.com',
                    'name' => 'Agus Santoso',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Asrama')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302122008040003',
                        'student_id' => '2023010003',
                        'full_name' => 'Agus Santoso Pratama',
                        'gender' => 'M',
                        'birth_place' => 'Cilacap',
                        'birth_date' => '2004-08-20',
                        'university_school' => 'Politeknik Negeri Cilacap',
                        'phone_number' => '081234561003',
                        'guardian_name' => 'Bapak Santoso',
                        'guardian_phone_number' => '081298761003',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-grendeng-komplek-sejahtera-subsidi-01',
                    'check_in_date' => now()->subMonths(8)->toDateString(),
                    'is_pic' => true,
                ],

                // PENGHUNI PUTRA - Cabang Banyumas
                [
                    'email' => 'fahmi.hidayat@gmail.com',
                    'name' => 'Fahmi Hidayat',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Wisma')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302121005040004',
                        'student_id' => '2023010004',
                        'full_name' => 'Fahmi Hidayat Ramadhan',
                        'gender' => 'M',
                        'birth_place' => 'Purbalingga',
                        'birth_date' => '2004-05-10',
                        'university_school' => 'IAIN Purwokerto',
                        'phone_number' => '081234561004',
                        'guardian_name' => 'Bapak Hidayat',
                        'guardian_phone_number' => '081298761004',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-banyumas-komplek-kaya-megah-01',
                    'check_in_date' => now()->subMonths(4)->toDateString(),
                    'is_pic' => true,
                ],

                [
                    'email' => 'imam.maulana@gmail.com',
                    'name' => 'Imam Maulana',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Wisma')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302122512040005',
                        'student_id' => '2023010005',
                        'full_name' => 'Imam Maulana Ibrahim',
                        'gender' => 'M',
                        'birth_place' => 'Kebumen',
                        'birth_date' => '2004-12-25',
                        'university_school' => 'Universitas Muhammadiyah Purwokerto',
                        'phone_number' => '081234561005',
                        'guardian_name' => 'Bapak Ibrahim',
                        'guardian_phone_number' => '081298761005',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-banyumas-komplek-kaya-megah-01',
                    'check_in_date' => now()->subMonths(4)->toDateString(),
                    'is_pic' => false,
                ],

                // PENGHUNI PUTRI - Cabang Sokaraja
                [
                    'email' => 'nur.azizah@gmail.com',
                    'name' => 'Nur Azizah',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Pondok')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302124505050001',
                        'student_id' => '2023020001',
                        'full_name' => 'Nur Azizah Rahmawati',
                        'gender' => 'F',
                        'birth_place' => 'Purwokerto',
                        'birth_date' => '2005-05-05',
                        'university_school' => 'IAIN Purwokerto',
                        'phone_number' => '081234562001',
                        'guardian_name' => 'Ibu Rahmawati',
                        'guardian_phone_number' => '081298762001',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-sokaraja-komplek-mawar-reguler-4-01',
                    'check_in_date' => now()->subMonths(7)->toDateString(),
                    'is_pic' => true,
                ],

                [
                    'email' => 'fitri.handayani@gmail.com',
                    'name' => 'Fitri Handayani',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Pondok')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302123107050002',
                        'student_id' => '2023020002',
                        'full_name' => 'Fitri Handayani Putri',
                        'gender' => 'F',
                        'birth_place' => 'Banyumas',
                        'birth_date' => '2005-07-31',
                        'university_school' => 'Universitas Jenderal Soedirman',
                        'phone_number' => '081234562002',
                        'guardian_name' => 'Ibu Handayani',
                        'guardian_phone_number' => '081298762002',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-sokaraja-komplek-mawar-reguler-4-01',
                    'check_in_date' => now()->subMonths(6)->toDateString(),
                    'is_pic' => false,
                ],

                [
                    'email' => 'dewi.lestari@gmail.com',
                    'name' => 'Dewi Lestari',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Wisma')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302121408050003',
                        'student_id' => '2023020003',
                        'full_name' => 'Dewi Lestari Sari',
                        'gender' => 'F',
                        'birth_place' => 'Cilacap',
                        'birth_date' => '2005-08-14',
                        'university_school' => 'Politeknik Negeri Cilacap',
                        'phone_number' => '081234562003',
                        'guardian_name' => 'Bapak Lestari',
                        'guardian_phone_number' => '081298762003',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-sokaraja-komplek-mawar-megah-01',
                    'check_in_date' => now()->subMonths(3)->toDateString(),
                    'is_pic' => true,
                ],

                [
                    'email' => 'siti.aminah@gmail.com',
                    'name' => 'Siti Aminah',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Asrama')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302122203050004',
                        'student_id' => '2023020004',
                        'full_name' => 'Siti Aminah Zahra',
                        'gender' => 'F',
                        'birth_place' => 'Purbalingga',
                        'birth_date' => '2005-03-22',
                        'university_school' => 'Universitas Muhammadiyah Purwokerto',
                        'phone_number' => '081234562004',
                        'guardian_name' => 'Ibu Zahra',
                        'guardian_phone_number' => '081298762004',
                        'status' => 'active',
                    ],
                    'room_code' => 'cabang-sokaraja-komplek-kenanga-subsidi-01',
                    'check_in_date' => now()->subMonths(9)->toDateString(),
                    'is_pic' => true,
                ],

                // PENGHUNI YANG SUDAH KELUAR (INACTIVE)
                [
                    'email' => 'andi.wijaya@gmail.com',
                    'name' => 'Andi Wijaya',
                    'password' => Hash::make('123456789'),
                    'profile' => [
                        'resident_category_id' => ResidentCategory::where('name', 'Kos')->value('id'),
                        'citizenship_status' => 'WNI',
                        'country_id' => $indonesia->id,
                        'national_id' => '3302121806030001',
                        'student_id' => '2022010001',
                        'full_name' => 'Andi Wijaya Kusuma',
                        'gender' => 'M',
                        'birth_place' => 'Kebumen',
                        'birth_date' => '2003-06-18',
                        'university_school' => 'Universitas Jenderal Soedirman',
                        'phone_number' => '081234563001',
                        'guardian_name' => 'Bapak Wijaya',
                        'guardian_phone_number' => '081298763001',
                        'status' => 'inactive',
                    ],
                    'room_code' => 'cabang-grendeng-komplek-barokah-reguler-4-01',
                    'check_in_date' => now()->subYear()->toDateString(),
                    'check_out_date' => now()->subMonths(2)->toDateString(),
                    'is_pic' => true,
                ],
            ];

            foreach ($residents as $data) {
                // 1. Buat User
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => $data['password'],
                        'is_active' => $data['profile']['status'] === 'active',
                    ]
                );

                // 2. Attach role resident
                $user->roles()->syncWithoutDetaching([$residentRole->id]);

                // 3. Buat Resident Profile
                ResidentProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    $data['profile']
                );

                // 4. Tempatkan ke kamar
                $room = Room::where('code', $data['room_code'])->first();

                if ($room) {
                    $checkOutDate = $data['check_out_date'] ?? null;

                    $roomResident = RoomResident::firstOrCreate(
                        [
                            'room_id' => $room->id,
                            'user_id' => $user->id,
                            'check_in_date' => $data['check_in_date'],
                        ],
                        [
                            'check_out_date' => $checkOutDate,
                            'is_pic' => $data['is_pic'],
                        ]
                    );

                    // 5. Buat Room History
                    RoomHistory::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'room_id' => $room->id,
                            'room_resident_id' => $roomResident->id,
                            'check_in_date' => $data['check_in_date'],
                        ],
                        [
                            'check_out_date' => $checkOutDate,
                            'is_pic' => $data['is_pic'],
                            'movement_type' => $checkOutDate ? 'checkout' : 'new',
                            'notes' => $checkOutDate ? 'Keluar dari asrama' : 'Penghuni baru',
                            'recorded_by' => $superAdmin->id,
                        ]
                    );
                }
            }
        });
    }
}
