<?php

namespace App\Console\Commands;

use App\Models\RoomResident;
use Illuminate\Console\Command;

class UpdateResidentStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resident:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status resident berdasarkan tanggal check-in (registered -> active)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai update status resident...');

        // Ambil semua RoomResident yang check_in_date sudah lewat dan check_out_date masih null
        $roomResidents = RoomResident::whereNull('check_out_date')
            ->whereDate('check_in_date', '<=', now())
            ->with('user.residentProfile')
            ->get();

        $updated = 0;

        foreach ($roomResidents as $roomResident) {
            $user = $roomResident->user;

            if ($user && $user->residentProfile) {
                $profile = $user->residentProfile;

                // Jika status masih 'registered', update menjadi 'active'
                if ($profile->status === 'registered') {
                    $profile->update(['status' => 'active']);
                    $updated++;

                    $this->line("✓ Updated: {$profile->full_name} (ID: {$user->id}) → Active");
                }
            }
        }

        $this->info("Selesai! Total resident yang diupdate: {$updated}");

        return Command::SUCCESS;
    }
}
