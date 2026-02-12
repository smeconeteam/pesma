<?php

namespace App\Filament\Resources\MainAdminResource\Pages;

use App\Filament\Resources\MainAdminResource;
use App\Services\AdminPrivilegeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMainAdmin extends CreateRecord
{
    protected static string $resource = MainAdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Siapkan data untuk service
        $adminData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'national_id' => $data['adminProfile']['national_id'],
            'full_name' => $data['adminProfile']['full_name'],
            'gender' => $data['adminProfile']['gender'],
            'phone_number' => $data['adminProfile']['phone_number'],
            'show_phone_on_landing' => $data['adminProfile']['show_phone_on_landing'] ?? false,
        ];

        if (isset($data['adminProfile']['photo_path'])) {
            $adminData['photo_path'] = $data['adminProfile']['photo_path'];
        }

        return $adminData;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminPrivilegeService::class)->createMainAdmin($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Admin utama berhasil dibuat';
    }
}