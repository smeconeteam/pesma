<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                'description' => 'Kamar nyaman 1-2 orang, AC',
                'default_capacity' => 2,
                'default_monthly_rate' => 1500000,
            ],
            [
                'name' => 'Standar',
                'description' => 'Kamar standar 2-4 orang',
                'default_capacity' => 4,
                'default_monthly_rate' => 1000000,
            ],
        ];

        foreach ($roomTypes as $item) {
            $code = Str::slug($item['name']); // contoh: "Kamar VIP" -> "kamar-vip"

            // Kalau ada kemungkinan nama sama, bikin code unik
            $baseCode = $code;
            $i = 2;
            while (DB::table('room_types')->where('code', $code)->exists()) {
                $code = "{$baseCode}-{$i}";
                $i++;
            }

            DB::table('room_types')->insert([
                'code' => $code,
                'name' => $item['name'],
                'description' => $item['description'],
                'default_capacity' => $item['default_capacity'],
                'default_monthly_rate' => $item['default_monthly_rate'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
