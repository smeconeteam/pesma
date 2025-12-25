<?php

namespace App\Console\Commands;

use App\Services\RoomPicService;
use Illuminate\Console\Command;

class FixRoomPicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room:fix-pic {room_id? : ID kamar tertentu (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix kamar yang tidak punya PIC atau punya lebih dari 1 PIC';

    /**
     * Execute the console command.
     */
    public function handle(RoomPicService $service)
    {
        $roomId = $this->argument('room_id');

        $this->info('Memeriksa PIC di semua kamar...');

        // Validasi dulu
        $validation = $service->validatePicPerRoom($roomId);

        if ($validation['valid']) {
            $this->info('✓ Semua kamar valid. Setiap kamar punya 1 PIC.');
            return Command::SUCCESS;
        }

        // Tampilkan kamar yang invalid
        $this->warn('✗ Ditemukan kamar dengan PIC invalid:');

        $headers = ['Room ID', 'Jumlah PIC', 'Status'];
        $rows = [];

        foreach ($validation['invalid_rooms'] as $room) {
            $status = $room['status'] === 'NO_PIC' ? 'Tidak ada PIC' : 'Lebih dari 1 PIC';
            $rows[] = [
                $room['room_id'],
                $room['pic_count'],
                $status,
            ];
        }

        $this->table($headers, $rows);

        // Tanya konfirmasi
        if (!$this->confirm('Apakah Anda ingin memperbaiki kamar-kamar ini?', true)) {
            $this->info('Dibatalkan.');
            return Command::SUCCESS;
        }

        // Fix
        $this->info('Memperbaiki...');
        $result = $service->fixInvalidPicRooms($roomId);

        if ($result['status'] === 'success') {
            $this->info("✓ Berhasil memperbaiki {$result['fixed']} kamar.");

            // Validasi ulang
            $revalidation = $service->validatePicPerRoom($roomId);

            if ($revalidation['valid']) {
                $this->info('✓ Semua kamar sekarang valid!');
            } else {
                $this->warn('⚠ Masih ada kamar yang perlu dicek manual:');
                $this->table($headers, array_map(function ($room) {
                    $status = $room['status'] === 'NO_PIC' ? 'Tidak ada PIC' : 'Lebih dari 1 PIC';
                    return [$room['room_id'], $room['pic_count'], $status];
                }, $revalidation['invalid_rooms']));
            }
        } else {
            $this->error('✗ Gagal memperbaiki.');
        }

        return Command::SUCCESS;
    }
}
