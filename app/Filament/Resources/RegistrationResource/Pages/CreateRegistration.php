<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Country;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRegistration extends CreateRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto set Indonesia untuk WNI
        if (($data['citizenship_status'] ?? 'WNI') === 'WNI') {
            $indoId = Country::query()->where('iso2', 'ID')->value('id');
            if ($indoId) {
                $data['country_id'] = $indoId;
            }
        }

        // Set default password jika kosong
        if (empty($data['password'])) {
            $data['password'] = bcrypt('123456789');
        }

        // Set status pending
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Pendaftaran berhasil dibuat')
            ->body('Status: Menunggu persetujuan')
            ->success()
            ->send();
    }
}
