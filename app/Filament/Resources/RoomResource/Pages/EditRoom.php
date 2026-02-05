<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\RoomResident;
use App\Models\RoomType;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    /**
     * Tampilkan NOTIFIKASI kalau gagal edit/simpan (termasuk alasan validasi).
     * Signature harus sama dengan EditRecord::save(...)
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);

            // Kalau kamu mau, bisa matikan notif bawaan Filament dan pakai notif ini saja.
            Notification::make()
                ->title('Berhasil')
                ->body('Data kamar berhasil disimpan.')
                ->success()
                ->send();
        } catch (ValidationException $e) {
            $messages = collect($e->errors())
                ->flatten()
                ->filter()
                ->unique()
                ->values()
                ->all();

            Notification::make()
                ->title('Gagal menyimpan')
                ->body(implode("\n", $messages) ?: 'Validasi gagal. Silakan periksa input.')
                ->danger()
                ->send();

            // Tetap lempar agar error juga muncul di field form
            throw $e;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menyimpan')
                ->body($e->getMessage() ?: 'Terjadi kesalahan saat menyimpan data.')
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $room = $this->record->fresh();

        // Normalisasi value yang kadang datang sebagai "" dari komponen form
        foreach (['resident_category_id', 'capacity', 'monthly_rate', 'room_type_id', 'block_id', 'number'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        // Hitung penghuni aktif (yang belum checkout)
        $activeResidentsCount = RoomResident::query()
            ->where('room_id', $room->id)
            ->whereNull('check_out_date')
            ->count();

        /**
         * 1) Kategori kamar:
         * Field kategori biasanya disabled saat kamar terisi => tidak ikut terkirim => bisa jadi null.
         * Jadi: kalau key-nya tidak ada di $data, anggap tidak berubah (pakai nilai lama).
         */
        $oldCategory = $room->resident_category_id;
        $newCategory = array_key_exists('resident_category_id', $data)
            ? ($data['resident_category_id'] ?? null)
            : $oldCategory;

        if ((string) $oldCategory !== (string) $newCategory && $activeResidentsCount > 0) {
            throw ValidationException::withMessages([
                'resident_category_id' => 'Kategori kamar hanya bisa diubah jika kamar kosong (tidak ada penghuni aktif).',
            ]);
        }

        /**
         * 2) Pastikan capacity selalu punya nilai sebelum dibandingkan
         */
        $roomTypeId = $data['room_type_id'] ?? $room->room_type_id;
        $roomType   = $roomTypeId ? RoomType::find($roomTypeId) : null;

        if (! array_key_exists('capacity', $data) || blank($data['capacity'])) {
            $data['capacity'] = $room->capacity ?? ($roomType?->default_capacity);
        }

        $newCapacity = (int) ($data['capacity'] ?? 0);

        if ($activeResidentsCount > 0 && $newCapacity < $activeResidentsCount) {
            throw ValidationException::withMessages([
                'capacity' => "Kapasitas tidak boleh kurang dari jumlah penghuni aktif saat ini ({$activeResidentsCount} orang).",
            ]);
        }

        /**
         * 4) Monthly rate default jika kosong
         */
        if (! array_key_exists('monthly_rate', $data) || blank($data['monthly_rate'])) {
            $data['monthly_rate'] = $room->monthly_rate ?? ($roomType?->default_monthly_rate);
        }

        /**
         * 5) Regenerate code jika block_id atau number berubah (jika tidak dikunci oleh rule di atas)
         */
        $blockChanged  = array_key_exists('block_id', $data) && (string) $data['block_id'] !== (string) $room->block_id;
        $numberChanged = array_key_exists('number', $data) && (string) $data['number'] !== (string) $room->number;

        if (($blockChanged || $numberChanged) && ! empty($data['block_id'])) {
            $block = \App\Models\Block::with('dorm')->find($data['block_id']);
            if ($block && $block->dorm) {
                $data['code'] = \Illuminate\Support\Str::slug($block->dorm->name) . '-'
                    . \Illuminate\Support\Str::slug($block->name) . '-'
                    . ($data['number'] ?? $room->number);
            }
        }

        // Pastikan code tetap ada
        if (empty($data['code'])) {
            $data['code'] = $room->code;
        }

        // dorm_id biasanya field view-only (dehydrated(false)), jangan ikut disimpan
        unset($data['dorm_id']);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load facilities untuk setiap tipe
        $data['facility_parkir'] = $this->record->facilities()->where('type', 'parkir')->pluck('facilities.id')->toArray();
        $data['facility_umum'] = $this->record->facilities()->where('type', 'umum')->pluck('facilities.id')->toArray();
        $data['facility_kamar_mandi'] = $this->record->facilities()->where('type', 'kamar_mandi')->pluck('facilities.id')->toArray();
        $data['facility_kamar'] = $this->record->facilities()->where('type', 'kamar')->pluck('facilities.id')->toArray();
        
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::handleRecordUpdate($record, $data);
        
        // Sync facilities dari semua tipe
        $allFacilities = array_merge(
            $data['facility_parkir'] ?? [],
            $data['facility_umum'] ?? [],
            $data['facility_kamar_mandi'] ?? [],
            $data['facility_kamar'] ?? []
        );
        
        $record->facilities()->sync($allFacilities);
        
        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(
                    fn (): bool =>
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
