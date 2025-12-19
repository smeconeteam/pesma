<?php

namespace Database\Seeders;

use App\Models\EmergencyNumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmergencyNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // buat dummy
        EmergencyNumber::create([
            'name' => 'Ambulan Cabang 1',
            'phone_number' => '6285155365405', // nomornya rizqi jir 😹😹😹
        ]);
    }
}
