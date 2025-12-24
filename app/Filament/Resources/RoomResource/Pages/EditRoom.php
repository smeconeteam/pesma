<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\RoomResident;
use App\Models\RoomType;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(
                    fn(): bool =>
                    auth()->user()?->hasRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin'])
                        && ! $this->record->trashed()
                        && ! RoomResident::query()
                            ->where('room_id', $this->record->id)
                            ->whereNull('check_out_date')
                            ->exists()
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $roomType = RoomType::find($data['room_type_id']);

        // Set default capacity & rate jika kosong
        if (empty($data['capacity'])) {
            $data['capacity'] = $roomType?->default_capacity;
        }

        if (empty($data['monthly_rate'])) {
            $data['monthly_rate'] = $roomType?->default_monthly_rate;
        }

        // Jika kamar punya penghuni aktif, jangan izinkan ganti komplek
        $hasActiveResidents = RoomResident::query()
            ->where('room_id', $this->record->id)
            ->whereNull('check_out_date')
            ->exists();

        if ($hasActiveResidents) {
            // Kembalikan block_id yang lama
            $data['block_id'] = $this->record->block_id;

            // Tampilkan notifikasi warning
            Notification::make()
                ->title('Peringatan')
                ->body('Komplek dan cabang tidak dapat diubah karena kamar ini masih memiliki penghuni aktif.')
                ->warning()
                ->send();
        }

        return $data;
    }
}
