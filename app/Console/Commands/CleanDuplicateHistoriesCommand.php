<?php

namespace App\Console\Commands;

use App\Models\RoomHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateHistoriesCommand extends Command
{
    protected $signature = 'room:clean-duplicate-histories';
    protected $description = 'Membersihkan duplikasi data di tabel room_histories';

    public function handle()
    {
        $this->info('Memeriksa duplikasi di tabel room_histories...');

        // Cari duplikasi berdasarkan kombinasi: user_id, room_id, check_in_date, movement_type
        $duplicates = DB::table('room_histories')
            ->select(
                'user_id',
                'room_id',
                'check_in_date',
                'movement_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('MIN(id) as keep_id')
            )
            ->groupBy('user_id', 'room_id', 'check_in_date', 'movement_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('✓ Tidak ada duplikasi data ditemukan.');
            return Command::SUCCESS;
        }

        $this->warn('✗ Ditemukan ' . $duplicates->count() . ' grup data duplikat.');

        // Tampilkan detail
        $headers = ['User ID', 'Room ID', 'Check In', 'Type', 'Jumlah', 'ID yang Dipertahankan'];
        $rows = [];

        foreach ($duplicates as $dup) {
            $rows[] = [
                $dup->user_id,
                $dup->room_id,
                $dup->check_in_date,
                $dup->movement_type,
                $dup->count,
                $dup->keep_id,
            ];
        }

        $this->table($headers, $rows);

        if (!$this->confirm('Apakah Anda ingin menghapus data duplikat? (ID terkecil akan dipertahankan)', true)) {
            $this->info('Dibatalkan.');
            return Command::SUCCESS;
        }

        $deletedCount = 0;

        DB::transaction(function () use ($duplicates, &$deletedCount) {
            foreach ($duplicates as $dup) {
                // Hapus semua kecuali yang ID-nya terkecil
                $deleted = DB::table('room_histories')
                    ->where('user_id', $dup->user_id)
                    ->where('room_id', $dup->room_id)
                    ->where('check_in_date', $dup->check_in_date)
                    ->where('movement_type', $dup->movement_type)
                    ->where('id', '>', $dup->keep_id)
                    ->delete();

                $deletedCount += $deleted;
            }
        });

        $this->info("✓ Berhasil menghapus {$deletedCount} data duplikat.");
        $this->info('✓ Data yang unik telah dipertahankan.');

        return Command::SUCCESS;
    }
}
