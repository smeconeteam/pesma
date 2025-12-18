<?php

namespace Database\Seeders;

use App\Models\BillingType;
use App\Models\Dorm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BillingTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua cabang (kalau mau hanya aktif, ganti jadi ->where('is_active', true))
        $dorms = Dorm::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        /**
         * 1) Jenis tagihan yang berlaku untuk semua cabang
         */
        $globalTypes = [
            [
                'name' => 'Biaya Pengembangan',
                'description' => 'Biaya pengembangan fasilitas.',
                'amount' => 150000,
                'applies_to_all' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Biaya Administrasi',
                'description' => 'Biaya administrasi pendaftaran/pengelolaan.',
                'amount' => 50000,
                'applies_to_all' => true,
                'is_active' => true,
            ],
        ];

        foreach ($globalTypes as $item) {
            $billingType = BillingType::updateOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['description'],
                    'amount' => (int) $item['amount'],
                    'applies_to_all' => (bool) $item['applies_to_all'],
                    'is_active' => (bool) $item['is_active'],
                ]
            );

            // Kalau semua cabang, pastikan pivot bersih
            $billingType->dorms()->detach();
        }

        /**
         * 2) Jenis tagihan yang khusus per cabang (mengikuti dorms yang ada di DB)
         *    (Aman kalau cabang banyak; ini idempotent.)
         */
        foreach ($dorms as $dorm) {
            // Contoh: buat 2 jenis tagihan per cabang
            $perDormTypes = [
                [
                    'name' => 'Biaya Kebersihan - ' . $dorm->name,
                    'description' => 'Biaya kebersihan khusus cabang: ' . $dorm->name,
                    'amount' => 30000,
                    'applies_to_all' => false,
                    'is_active' => true,
                ],
                [
                    'name' => 'Biaya Internet - ' . $dorm->name,
                    'description' => 'Biaya internet khusus cabang: ' . $dorm->name,
                    'amount' => 25000,
                    'applies_to_all' => false,
                    'is_active' => true,
                ],
            ];

            foreach ($perDormTypes as $item) {
                $billingType = BillingType::updateOrCreate(
                    ['name' => $item['name']],
                    [
                        'description' => $item['description'],
                        'amount' => (int) $item['amount'],
                        'applies_to_all' => false,
                        'is_active' => (bool) $item['is_active'],
                    ]
                );

                // Sync hanya ke dorm ini
                $billingType->dorms()->sync([$dorm->id]);
            }
        }
    }
}
