<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Dorm;
use Illuminate\Database\Seeder;

class BlockSeeder extends Seeder
{
    public function run(): void
    {
        $grendeng = Dorm::where('name', 'Cabang Grendeng')->first();
        $banyumas = Dorm::where('name', 'Cabang Banyumas')->first();
        $sokaraja = Dorm::where('name', 'Cabang Sokaraja')->first();

        $blocks = [
            // Grendeng
            ['dorm_id' => $grendeng?->id, 'name' => 'Komplek Sejahtera', 'description' => 'Komplek utama'],
            ['dorm_id' => $grendeng?->id, 'name' => 'Komplek Barokah', 'description' => 'Komplek tambahan'],

            // Banyumas
            ['dorm_id' => $banyumas?->id, 'name' => 'Komplek Kaya', 'description' => 'Komplek putra A'],
            ['dorm_id' => $banyumas?->id, 'name' => 'Komplek Melati', 'description' => 'Komplek putra B'],

            // Sokaraja
            ['dorm_id' => $sokaraja?->id, 'name' => 'Komplek Mawar', 'description' => 'Komplek putri A'],
            ['dorm_id' => $sokaraja?->id, 'name' => 'Komplek Kenanga', 'description' => 'Komplek putri B'],
        ];

        foreach ($blocks as $block) {
            if ($block['dorm_id']) {
                Block::firstOrCreate(
                    ['dorm_id' => $block['dorm_id'], 'name' => $block['name']],
                    ['description' => $block['description']]
                );
            }
        }
    }
}
