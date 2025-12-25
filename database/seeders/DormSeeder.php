<?php

namespace Database\Seeders;

use App\Models\Dorm;
use Illuminate\Database\Seeder;

class DormSeeder extends Seeder
{
    public function run(): void
    {
        $dorms = [
            [
                'name' => 'Cabang Grendeng',
                'address' => 'Jl. Grendeng No. 1, Purwokerto',
                'description' => 'Cabang utama di kawasan Grendeng',
            ],
            [
                'name' => 'Cabang Banyumas',
                'address' => 'Jl. Banyumas No. 10, Banyumas',
                'description' => 'Cabang khusus santri putra',
            ],
            [
                'name' => 'Cabang Sokaraja',
                'address' => 'Jl. Sokaraja No. 5, Sokaraja',
                'description' => 'Cabang khusus santri putri',
            ],
        ];

        foreach ($dorms as $dorm) {
            Dorm::firstOrCreate(
                ['name' => $dorm['name']],
                $dorm
            );
        }
    }
}
