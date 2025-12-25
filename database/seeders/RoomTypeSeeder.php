<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = [
            [
                'name' => 'VVIP',
                'description' => 'Kamar premium 1 orang, AC, kamar mandi dalam',
                'default_capacity' => 1,
                'default_monthly_rate' => 2000000,
            ],
            [
                'name' => 'VIP',
                'description' => 'Kamar VIP 2 orang, AC, kamar mandi dalam',
                'default_capacity' => 2,
                'default_monthly_rate' => 1500000,
            ],
            [
                'name' => 'Reguler 4',
                'description' => 'Kamar standar 4 orang, kipas angin',
                'default_capacity' => 4,
                'default_monthly_rate' => 800000,
            ],
            [
                'name' => 'Reguler 8',
                'description' => 'Kamar ekonomis 6-8 orang',
                'default_capacity' => 8,
                'default_monthly_rate' => 500000,
            ],
        ];

        foreach ($roomTypes as $type) {
            RoomType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
