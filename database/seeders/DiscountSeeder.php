<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Dorm;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua dorm (aktif saja). Kalau kamu mau termasuk non-aktif, hapus where('is_active', true).
        $dorms = Dorm::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $dormByName = $dorms->keyBy('name');

        $getDormId = function (string $name) use ($dormByName): ?int {
            return $dormByName[$name]->id ?? null;
        };

        /**
         * Format item:
         * - type: 'percent' | 'fixed'
         * - percent diisi kalau percent
         * - amount diisi kalau fixed
         * - applies_to_all true => pivot akan dikosongkan
         * - dorm_names => daftar cabang yang berlaku (kalau applies_to_all = false)
         */
        $items = [
            // ===== Global (Semua Cabang)
            [
                'name' => 'Diskon Early Bird 10%',
                'type' => 'percent',
                'percent' => 10.00,
                'amount' => null,
                'applies_to_all' => true,
                'is_active' => true,
                'description' => 'Diskon pendaftaran lebih awal (contoh).',
                'dorm_names' => [],
            ],
            [
                'name' => 'Potongan Promo Rp 25.000',
                'type' => 'fixed',
                'percent' => null,
                'amount' => 25000,
                'applies_to_all' => true,
                'is_active' => true,
                'description' => 'Promo potongan nominal untuk semua cabang (contoh).',
                'dorm_names' => [],
            ],

            // ===== Cabang-specific (mengikuti dorms dari pesma.sql)
            [
                'name' => 'Diskon Cabang Utama 5%',
                'type' => 'percent',
                'percent' => 5.00,
                'amount' => null,
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Khusus Cabang Utama.',
                'dorm_names' => ['Cabang Utama'],
            ],
            [
                'name' => 'Diskon Asrama Pusat Rp 50.000',
                'type' => 'fixed',
                'percent' => null,
                'amount' => 50000,
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Khusus Asrama Pusat.',
                'dorm_names' => ['Asrama Pusat'],
            ],
            [
                'name' => 'Diskon Cimahi 12%',
                'type' => 'percent',
                'percent' => 12.00,
                'amount' => null,
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Khusus Asrama Cabang Cimahi.',
                'dorm_names' => ['Asrama Cabang Cimahi'],
            ],
            [
                'name' => 'Diskon Jakarta Rp 75.000',
                'type' => 'fixed',
                'percent' => null,
                'amount' => 75000,
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Khusus Asrama Cabang Jakarta.',
                'dorm_names' => ['Asrama Cabang Jakarta'],
            ],

            // ===== Contoh multi-cabang (pilih beberapa cabang)
            [
                'name' => 'Diskon Wilayah Jawa Barat 8%',
                'type' => 'percent',
                'percent' => 8.00,
                'amount' => null,
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Contoh diskon untuk Cabang Utama + Cimahi.',
                'dorm_names' => ['Cabang Utama', 'Asrama Cabang Cimahi'],
            ],
        ];

        foreach ($items as $item) {
            // Update/create diskon berdasarkan name supaya idempotent
            $discount = Discount::updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'percent' => $item['type'] === 'percent' ? $item['percent'] : null,
                    'amount' => $item['type'] === 'fixed' ? (int) $item['amount'] : null,
                    'applies_to_all' => (bool) $item['applies_to_all'],
                    'is_active' => (bool) $item['is_active'],
                    'description' => $item['description'] ?? null,
                ]
            );

            // Bersihkan pivot dulu biar tidak dobel
            $discount->dorms()->detach();

            if ($discount->applies_to_all) {
                continue; // semua cabang => pivot kosong
            }

            // Convert dorm_names -> dorm_ids (yang benar-benar ada)
            $dormIds = collect($item['dorm_names'] ?? [])
                ->map(fn ($name) => $getDormId($name))
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Jika ada dormIds, attach
            if (!empty($dormIds)) {
                $discount->dorms()->sync($dormIds);
            } else {
                // Kalau cabang tidak ketemu, supaya tidak bikin diskon "nggantung",
                // kamu bisa fallback jadi semua cabang. Kalau nggak mau, hapus 2 baris ini.
                $discount->update(['applies_to_all' => true]);
            }
        }
    }
}
