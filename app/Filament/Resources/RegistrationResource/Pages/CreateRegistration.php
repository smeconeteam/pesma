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
        $user = auth()->user();

        // Branch admin dan block admin redirect ke create lagi
        if ($user?->hasAnyRole(['branch_admin', 'block_admin'])) {
            return $this->getResource()::getUrl('create');
        }

        // Super admin dan main admin redirect ke index
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        
        // Default Indonesia untuk WNI hanya jika country_id belum diisi
        if (($data['citizenship_status'] ?? 'WNI') === 'WNI' && empty($data['country_id'])) {
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

        // Jika created_at tidak diisi, gunakan waktu sekarang
        if (empty($data['created_at'])) {
            $data['created_at'] = now();
        }// Auto set Indonesia untuk WNI

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

    public function mount(): void
    {
        // Pastikan user punya akses create
        abort_unless(static::getResource()::canCreate(), 403);

        parent::mount();
    }
}
