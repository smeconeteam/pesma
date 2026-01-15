<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use App\Services\BillService;

class UpdateOverdueBills extends Command
{
    protected $signature = 'bills:update-overdue';

    protected $description = 'Update status tagihan yang sudah lewat periode selesai (jatuh tempo) menjadi overdue';

    #[Schedule('daily')]
    public function handle(BillService $billService): int
    {
        $this->info('Memeriksa tagihan yang sudah jatuh tempo...');

        $updated = $billService->updateOverdueStatus();

        if ($updated > 0) {
            $this->info("✅ {$updated} tagihan diupdate ke status overdue");
        } else {
            $this->info('✅ Tidak ada tagihan yang jatuh tempo');
        }

        return Command::SUCCESS;
    }
}
