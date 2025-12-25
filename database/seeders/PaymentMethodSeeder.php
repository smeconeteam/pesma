<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'kind' => 'cash',
                'instructions' => 'Pembayaran dilakukan secara tunai ke admin. Simpan bukti/kwitansi jika ada.',
                'qr_image_path' => null,
                'is_active' => true,
            ],
            [
                'kind' => 'transfer',
                'instructions' => 'Transfer ke rekening resmi pesantren. Pastikan mencantumkan nama penghuni pada berita transfer.',
                'qr_image_path' => null,
                'is_active' => true,
            ],
            [
                'kind' => 'qris',
                'instructions' => 'Scan QRIS berikut untuk melakukan pembayaran. Setelah bayar, simpan bukti pembayaran.',
                // isi path kalau kamu sudah punya file QR di storage/public/payment-methods/qris/...
                // kalau belum, biarkan null dan upload dari admin panel.
                'qr_image_path' => null,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            // Kalau sebelumnya record pernah soft-deleted, kita ambil juga
            $method = PaymentMethod::withTrashed()->updateOrCreate(
                ['kind' => $item['kind']],
                [
                    'instructions' => $item['instructions'],
                    'qr_image_path' => $item['qr_image_path'],
                    'is_active' => (bool) $item['is_active'],
                    'deleted_at' => null, // hidupkan lagi kalau dulu pernah terhapus
                ]
            );

            // Pastikan benar-benar tidak trashed
            if (method_exists($method, 'restore') && $method->trashed()) {
                $method->restore();
            }
        }
    }
}
