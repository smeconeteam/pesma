<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RoomType::create([
            'name'                 => 'VVIP 2 Orang',
            'description'          => 'Kamar VVIP berkapasitas 2 orang, fasilitas lengkap dan privasi tinggi.',
            'default_capacity'     => 2,
            'default_monthly_rate' => 1500000, // contoh: 1.500.000 / bulan
        ]);

        RoomType::create([
            'name'                 => 'VIP 4 Orang',
            'description'          => 'Kamar VIP berkapasitas 4 orang, fasilitas lebih nyaman.',
            'default_capacity'     => 4,
            'default_monthly_rate' => 1200000, // 1.200.000 / bulan
        ]);

        RoomType::create([
            'name'                 => 'Reguler 4 Orang',
            'description'          => 'Kamar reguler dengan kapasitas 4 orang.',
            'default_capacity'     => 4,
            'default_monthly_rate' => 900000, // 900.000 / bulan
        ]);

        RoomType::create([
            'name'                 => 'Reguler 8 Orang',
            'description'          => 'Kamar reguler kapasitas 8 orang, cocok untuk santri/mahasiswa.',
            'default_capacity'     => 8,
            'default_monthly_rate' => 800000, // 800.000 / bulan
        ]);
    }
}
