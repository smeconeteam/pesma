<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Institution::firstOrCreate(
            ['legal_number' => '1234567890'],
            [
                'institution_name' => 'Lembaga Coba Coba',
                'dormitory_name'  => 'Asrama Tiba Tiba Ada',
                'address'         => 'Jalan Lorem Ipsum No. 123, Kota Contoh, Negara Contoh',
                'phone'           => '+1234567890',
                'email'           => 'yayasan@example.com',
                'website'         => 'https://www.yayasan.com',
                'logo_path'       => null,
            ]
        );
    }
}
