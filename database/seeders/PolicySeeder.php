<?php

namespace Database\Seeders;

use App\Models\Policy;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        Policy::firstOrCreate(
            ['title' => 'Kebijakan & Ketentuan Asrama'],
            [
                'content' => '<p>Tuliskan kebijakan di sini...</p>',
                'is_active' => true,
                'published_at' => now(),
            ]
        );
    }
}
