<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function getRedirectUrl(): string
    {
        return ContactResource::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            // ✅ Validasi unik: name + dorm_id hanya untuk data aktif (deleted_at null)
            ContactResource::ensureUniqueActiveNameDorm($data);
        } catch (ValidationException $e) {
            // ✅ Toast notifikasi gagal
            $first = collect($e->errors())->flatten()->first() ?? 'Gagal menyimpan data.';
            Notification::make()
                ->title('Gagal Membuat Kontak')
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
