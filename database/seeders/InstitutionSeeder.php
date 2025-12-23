<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        Institution::firstOrCreate(
            ['legal_number' => '1234567890'],
            [
                'institution_name' => 'Pondok Pesantren Modern Elfira',
                'dormitory_name' => 'Asrama Elfira',
                'address' => 'Jl. Raya Purwokerto No. 123, Banyumas, Jawa Tengah',
                'phone' => '0281234567',
                'email' => 'info@elfira.ac.id',
                'website' => 'elfira.ac.id',
                'logo_path' => null,
            ]
        );
    }
}
