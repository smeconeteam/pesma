<?php

namespace App\Filament\Resources\RoomResource\Pages;

use Filament\Actions;
use App\Models\RoomType;
use App\Filament\Resources\RoomResource;
use Filament\Resources\Pages\EditRecord;
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

        // 2) Default capacity & monthly_rate dari room type (jika kosong)
        $roomType = RoomType::find($data['room_type_id'] ?? null);

        if (empty($data['capacity'])) {
            $data['capacity'] = $roomType?->default_capacity;
        }

        if (empty($data['monthly_rate'])) {
            $data['monthly_rate'] = $roomType?->default_monthly_rate;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
