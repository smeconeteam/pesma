<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\RoomResident;
use App\Models\RoomType;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {   
        $room = $this->record->fresh();

        // 1) Validasi: kategori hanya bisa diubah jika kamar kosong
        $oldCategory = $room->resident_category_id;
        $newCategory = $data['resident_category_id'] ?? null;

        if ($oldCategory !== $newCategory && ! $room->isEmpty()) {
            throw ValidationException::withMessages([
                'resident_category_id' => 'Kategori kamar hanya bisa diubah jika kamar kosong.',
            ]);
        }

        // 2) Validasi: jika kamar punya penghuni aktif, jangan izinkan ganti komplek/cabang
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

        // 3) Regenerate kode jika block_id atau number berubah
        $blockChanged = ($data['block_id'] ?? null) != $room->block_id;
        $numberChanged = ($data['number'] ?? null) != $room->number;
        
        if ($blockChanged || $numberChanged) {
            $block = \App\Models\Block::with('dorm')->find($data['block_id']);
            if ($block && $block->dorm) {
                $data['code'] = \Illuminate\Support\Str::slug($block->dorm->name) . '-' 
                    . \Illuminate\Support\Str::slug($block->name) . '-' 
                    . ($data['number'] ?? $room->number);
            }
        }

        // 4) Default capacity & monthly_rate dari room type (jika kosong)
        $roomType = RoomType::find($data['room_type_id'] ?? null);

        // Jika user tidak mengisi capacity, gunakan default dari room type
        if (blank($data['capacity'] ?? null) && $roomType) {
            $data['capacity'] = $roomType->default_capacity;
        }

        // Jika user tidak mengisi monthly_rate, gunakan default dari room type
        if (blank($data['monthly_rate'] ?? null) && $roomType) {
            $data['monthly_rate'] = $roomType->default_monthly_rate;
        }

        // PENTING: Pastikan code tetap ada (jika belum di-regenerate di atas)
        if (empty($data['code'])) {
            $data['code'] = $this->record->code;
        }

        // Hapus dorm_id karena itu field dehydrated(false)
        unset($data['dorm_id']);

        \Log::info('Data setelah mutate:', $data);
        
        return $data;
    }

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
}