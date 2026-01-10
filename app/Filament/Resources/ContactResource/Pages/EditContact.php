<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function getRedirectUrl(): string
    {
        return ContactResource::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            // ✅ Validasi unik: name + dorm_id hanya untuk data aktif (ignore record ini)
            $ignoreId = $this->record?->getKey();
            ContactResource::ensureUniqueActiveNameDorm($data, $ignoreId);
        } catch (ValidationException $e) {
            // ✅ Toast notifikasi gagal
            $first = collect($e->errors())->flatten()->first() ?? 'Gagal menyimpan perubahan.';
            Notification::make()
                ->title('Gagal Mengedit Kontak')
                ->body($first)
                ->danger()
                ->send();

            throw $e;
        }

        // pastikan display_name tersusun ulang
        $data['display_name'] = ContactResource::buildDisplayName(
            $data['name'] ?? null,
            $data['dorm_id'] ?? null,
        );

        return $data;
    }
}
