<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Log;

class ExportResidentsToExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 menit
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $residentIds,
        public int $userId,
        public array $filters = [],
        public string $activeTab = 'aktif'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $filename = 'exports/data-penghuni-' . now()->format('Y-m-d-His') . '.xlsx';

            // Generate Excel
            Excel::store(
                new \App\Exports\ResidentCustomExport($this->residentIds, $this->filters, $this->activeTab),
                $filename,
                'public'
            );

            // Kirim notifikasi ke user
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->success()
                    ->title('Export Excel Selesai')
                    ->body(count($this->residentIds) . ' data penghuni berhasil di-export')
                    ->actions([
                        Action::make('download')
                            ->label('Download File')
                            ->url(Storage::url($filename))
                            ->openUrlInNewTab(),
                    ])
                    ->sendToDatabase($user);
            }

            Log::info('Export Excel Success', [
                'user_id' => $this->userId,
                'count' => count($this->residentIds),
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            Log::error('Export Excel Job Failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Kirim notifikasi error
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->danger()
                    ->title('Export Excel Gagal')
                    ->body('Terjadi kesalahan saat export data. Silakan coba lagi.')
                    ->sendToDatabase($user);
            }

            throw $e;
        }
    }
}
