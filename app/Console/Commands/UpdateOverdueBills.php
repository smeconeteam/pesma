<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use App\Services\BillService;

class UpdateOverdueBills extends Command
{
    protected $signature = 'bills:update-overdue';

    protected $description = 'Update status tagihan yang sudah lewat jatuh tempo menjadi overdue';

    #[Schedule('daily')]
    public function handle(BillService $billService): int
    {
        $this->info('🔄 Checking for overdue bills...');

        $updated = $billService->updateOverdueStatus();

        if ($updated > 0) {
            $this->info("✅ {$updated} bills updated to overdue status");
        } else {
            $this->info('✅ No overdue bills found');
        }

        return Command::SUCCESS;
    }
}
