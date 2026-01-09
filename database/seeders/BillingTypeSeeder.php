<?php

namespace Database\Seeders;

use App\Models\BillingType;
use App\Models\Dorm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Ambil cabang aktif saja
            $dorms = Dorm::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            /**
             * 1) Jenis tagihan berlaku untuk semua cabang
             *    Disimpan sebagai: "Nama - Semua Cabang"
             */
            $globalBases = [
                [
                    'base' => 'Biaya Pengembangan',
                    'description' => 'Biaya pengembangan fasilitas.',
                    'is_active' => true,
                ],
                [
                    'base' => 'Biaya Administrasi',
                    'description' => 'Biaya administrasi pendaftaran/pengelolaan.',
                    'is_active' => true,
                ],
            ];

            foreach ($globalBases as $item) {
                $this->upsertGlobal(
                    baseName: $item['base'],
                    description: $item['description'],
                    isActive: (bool) $item['is_active'],
                );
            }

            /**
             * 2) Jenis tagihan khusus per cabang
             *    Dibuat 1 record per dorm:
             *    "Nama - {Dorm}"
             */
            $perDormBases = [
                [
                    'base' => 'Biaya Kebersihan',
                    'description_prefix' => 'Biaya kebersihan khusus cabang: ',
                    'is_active' => true,
                ],
                [
                    'base' => 'Biaya Internet',
                    'description_prefix' => 'Biaya internet khusus cabang: ',
                    'is_active' => true,
                ],
            ];

            foreach ($dorms as $dorm) {
                foreach ($perDormBases as $item) {
                    $this->upsertPerDorm(
                        dormId: (int) $dorm->id,
                        dormName: (string) $dorm->name,
                        baseName: (string) $item['base'],
                        description: (string) ($item['description_prefix'] . $dorm->name),
                        isActive: (bool) $item['is_active'],
                    );
                }
            }
        });
    }

    private function upsertGlobal(string $baseName, string $description, bool $isActive): void
    {
        $newName = $baseName . ' - Semua Cabang';

        // 1) coba cari dengan format baru
        $record = BillingType::withTrashed()
            ->where('name', $newName)
            ->first();

        // 2) kalau belum ada, coba migrasi format lama: "Biaya Pengembangan" (tanpa suffix)
        if (! $record) {
            $record = BillingType::withTrashed()
                ->where('name', $baseName)
                ->where('applies_to_all', true)
                ->first();
        }

        if (! $record) {
            $record = new BillingType();
        }

        // pastikan nama sesuai format baru
        $record->name = $newName;
        $record->description = $description;
        $record->applies_to_all = true;
        $record->is_active = $isActive;
        $record->deleted_at = null; // restore kalau pernah dihapus
        $record->save();

        // global: pivot harus kosong
        $record->dorms()->sync([]);
    }

    private function upsertPerDorm(
        int $dormId,
        string $dormName,
        string $baseName,
        string $description,
        bool $isActive
    ): void {
        $newName = $baseName . ' - ' . $dormName;

        $record = BillingType::withTrashed()
            ->where('name', $newName)
            ->first();

        if (! $record) {
            $record = new BillingType();
        }

        $record->name = $newName;
        $record->description = $description;
        $record->applies_to_all = false;
        $record->is_active = $isActive;
        $record->deleted_at = null; // restore kalau pernah dihapus
        $record->save();

        // per cabang: pastikan cuma 1 dorm
        $record->dorms()->sync([$dormId]);
    }
}
