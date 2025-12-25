<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Dorm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua dorm aktif (sesuai kebiasaan di project kamu)
        $dorms = Dorm::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $dormByName = $dorms->keyBy('name');

        $getDormId = function (string $name) use ($dormByName): ?int {
            return $dormByName[$name]->id ?? null;
        };

        // Normalisasi kode voucher: uppercase, alfanumerik, max 50
        $normalizeVoucher = function (?string $raw): ?string {
            $raw = trim((string) $raw);
            if ($raw === '') return null;

            $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $raw));
            $code = substr($code, 0, 50);

            return $code !== '' ? $code : null;
        };

        // Auto generate voucher dari name + nilai (percent/fixed)
        $buildVoucherFromItem = function (array $item) use ($normalizeVoucher): ?string {
            $name = trim((string) ($item['name'] ?? ''));
            $type = (string) ($item['type'] ?? '');

            if ($name === '' || $type === '') return null;

            $valuePart = '';

            if ($type === 'percent') {
                $p = (float) ($item['percent'] ?? 0);
                // rapikan: 10.00 -> 10
                $pTxt = fmod($p, 1.0) === 0.0 ? (string) (int) $p : rtrim(rtrim((string) $p, '0'), '.');
                $valuePart = $pTxt . 'P';
            } elseif ($type === 'fixed') {
                $a = (int) ($item['amount'] ?? 0);
                $valuePart = $a . 'R';
            }

            return $normalizeVoucher($name . '-' . $valuePart);
        };

        /**
         * Format item:
         * - type: 'percent' | 'fixed'
         * - percent diisi kalau percent
         * - amount diisi kalau fixed
         * - voucher_code opsional (kalau null/kosong => auto dari name+nilai)
         * - valid_from/valid_until opsional (date string Y-m-d atau null)
         * - applies_to_all true => pivot kosong
         * - dorm_names => daftar cabang (kalau applies_to_all = false)
         */
        $today = Carbon::today();

        $items = [
            // ===== Global (Semua Cabang)
            [
                'name' => 'Diskon Early Bird',
                'type' => 'percent',
                'percent' => 10,
                'amount' => null,
                'voucher_code' => null, // auto
                'valid_from' => $today->copy()->toDateString(),
                'valid_until' => $today->copy()->addDays(30)->toDateString(),
                'applies_to_all' => true,
                'is_active' => true,
                'description' => 'Diskon pendaftaran lebih awal (contoh).',
                'dorm_names' => [],
            ],
            [
                'name' => 'Potongan Promo',
                'type' => 'fixed',
                'percent' => null,
                'amount' => 25000,
                'voucher_code' => null, // auto
                'valid_from' => null,
                'valid_until' => null, // selamanya
                'applies_to_all' => true,
                'is_active' => true,
                'description' => 'Promo potongan nominal untuk semua cabang (contoh).',
                'dorm_names' => [],
            ],

            // ===== Cabang-specific (sesuaikan nama dorm di database kamu)
            [
                'name' => 'Diskon Cabang Utama',
                'type' => 'percent',
                'percent' => 5,
                'amount' => null,
                'voucher_code' => null, // auto
                'valid_from' => $today->copy()->toDateString(),
                'valid_until' => $today->copy()->addDays(14)->toDateString(),
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Khusus Cabang Utama.',
                'dorm_names' => ['Cabang Utama'],
            ],
            [
                'name' => 'Diskon Asrama Pusat',
                'type' => 'fixed',
                'percent' => null,
                'amount' => 50000,
                'voucher_code' => null, // auto
                'valid_from' => null,
                'valid_until' => null,
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Khusus Asrama Pusat.',
                'dorm_names' => ['Asrama Pusat'],
            ],

            // ===== Contoh multi-cabang
            [
                'name' => 'Diskon Wilayah Jawa Barat',
                'type' => 'percent',
                'percent' => 8,
                'amount' => null,
                'voucher_code' => null, // auto
                'valid_from' => $today->copy()->toDateString(),
                'valid_until' => $today->copy()->addDays(21)->toDateString(),
                'applies_to_all' => false,
                'is_active' => true,
                'description' => 'Contoh diskon untuk Cabang Utama + Cimahi.',
                'dorm_names' => ['Cabang Utama', 'Asrama Cabang Cimahi'],
            ],
        ];

        foreach ($items as $item) {
            $voucher = $normalizeVoucher($item['voucher_code'] ?? null) ?: $buildVoucherFromItem($item);

            // Update/create diskon berdasarkan name supaya idempotent.
            // Pakai withTrashed agar kalau sebelumnya terhapus, tidak bikin duplikat.
            $discount = Discount::withTrashed()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'type' => $item['type'],
                    'percent' => $item['type'] === 'percent' ? (float) ($item['percent'] ?? 0) : null,
                    'amount' => $item['type'] === 'fixed' ? (int) ($item['amount'] ?? 0) : null,

                    'voucher_code' => $voucher,              // ✅ baru
                    'valid_from' => $item['valid_from'] ?? null,   // ✅ baru (date)
                    'valid_until' => $item['valid_until'] ?? null, // ✅ baru (date)

                    'applies_to_all' => (bool) $item['applies_to_all'],
                    'is_active' => (bool) $item['is_active'],
                    'description' => $item['description'] ?? null,
                ]
            );

            // Kalau sebelumnya soft deleted, restore
            if (method_exists($discount, 'trashed') && $discount->trashed()) {
                $discount->restore();
            }

            // Sync dorm pivot
            if ((bool) $discount->applies_to_all) {
                $discount->dorms()->sync([]); // semua cabang => pivot kosong
                continue;
            }

            $dormIds = collect($item['dorm_names'] ?? [])
                ->map(fn ($name) => $getDormId($name))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (! empty($dormIds)) {
                $discount->dorms()->sync($dormIds);
            } else {
                // Kalau cabang tidak ketemu di DB, fallback jadi semua cabang biar tidak "nggantung"
                $discount->update(['applies_to_all' => true]);
                $discount->dorms()->sync([]);
            }
        }
    }
}
