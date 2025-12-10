<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Dorm;
use Illuminate\Database\Seeder;

class BlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pusat   = Dorm::where('name', 'Asrama Pusat')->first();
        $cimahi  = Dorm::where('name', 'Asrama Cabang Cimahi')->first();
        $jakarta = Dorm::where('name', 'Asrama Cabang Jakarta')->first();

        if ($pusat) {
            Block::create([
                'dorm_id'     => $pusat->id,
                'name'        => 'Komplek A Pusat',
                'description' => 'Komplek utama di asrama pusat.',
            ]);

            Block::create([
                'dorm_id'     => $pusat->id,
                'name'        => 'Komplek B Pusat',
                'description' => 'Komplek tambahan di asrama pusat.',
            ]);
        }

        if ($cimahi) {
            Block::create([
                'dorm_id'     => $cimahi->id,
                'name'        => 'Komplek A Cimahi',
                'description' => 'Komplek pertama di cabang Cimahi.',
            ]);
        }

        if ($jakarta) {
            Block::create([
                'dorm_id'     => $jakarta->id,
                'name'        => 'Komplek A Jakarta',
                'description' => 'Komplek mahasiswa kerja sama kampus Jakarta.',
            ]);
        }
    }
}
