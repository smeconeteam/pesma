<?php

namespace Database\Seeders;

use App\Models\ResidentCategory;
use Illuminate\Database\Seeder;

class ResidentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pondok', 'description' => 'Penghuni kategori pondok (tarif khusus + biaya pengembangan)'],
            ['name' => 'Wisma',  'description' => 'Penghuni kategori wisma (tarif normal)'],
            ['name' => 'Asrama', 'description' => 'Penghuni kategori asrama (tarif normal)'],
            ['name' => 'Kos',    'description' => 'Penghuni kategori kos (tarif normal)'],
        ];

        foreach ($categories as $cat) {
            ResidentCategory::updateOrCreate(
                ['name' => $cat['name']],
                ['description' => $cat['description']]
            );
        }
    }
}
