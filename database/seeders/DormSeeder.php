<?php

namespace Database\Seeders;

use App\Models\Dorm;
use Illuminate\Database\Seeder;

class DormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Dorm::create([
            'name'        => 'Asrama Pusat',
            'address'     => 'Jl. Pesantren No. 1, Kota Bandung, Jawa Barat',
            'description' => 'Asrama utama yang menjadi pusat administrasi.',
        ]);

        Dorm::create([
            'name'        => 'Asrama Cabang Cimahi',
            'address'     => 'Jl. Pendidikan No. 10, Cimahi, Jawa Barat',
            'description' => 'Asrama cabang khusus mahasiswa luar kota.',
        ]);

        Dorm::create([
            'name'        => 'Asrama Cabang Jakarta',
            'address'     => 'Jl. Kebon Jeruk No. 5, Jakarta Barat, DKI Jakarta',
            'description' => 'Asrama cabang untuk program kerja sama dengan kampus di Jakarta.',
        ]);
    }
}
