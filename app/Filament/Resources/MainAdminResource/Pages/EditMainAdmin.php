<?php

namespace App\Filament\Resources\MainAdminResource\Pages;

use App\Filament\Resources\MainAdminResource;
use App\Services\AdminPrivilegeService;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMainAdmin extends EditRecord
{
    protected static string $resource = MainAdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalHeading('Hapus Admin Utama')
                ->modalDescription('Apakah Anda yakin ingin menghapus admin utama ini?')
                ->successNotificationTitle('Admin utama berhasil dihapus')
                ->using(function () {
                    app(AdminPrivilegeService::class)->deleteMainAdmin($this->record);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relasi adminProfile untuk form
        $this->record->load('adminProfile');
        
        return [
            'email' => $this->record->email,
            'adminProfile' => [
                'national_id' => $this->record->adminProfile?->national_id,
                'full_name' => $this->record->adminProfile?->full_name,
                'gender' => $this->record->adminProfile?->gender,
                'phone_number' => $this->record->adminProfile?->phone_number,
                'show_phone_on_landing' => $this->record->adminProfile?->show_phone_on_landing ?? false,
                'photo_path' => $this->record->adminProfile?->photo_path,
            ],
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Siapkan data untuk service
        $adminData = [
            'email' => $data['email'] ?? $this->record->email,
            'national_id' => $data['adminProfile']['national_id'],
            'full_name' => $data['adminProfile']['full_name'],
            'gender' => $data['adminProfile']['gender'],
            'phone_number' => $data['adminProfile']['phone_number'],
            'show_phone_on_landing' => $data['adminProfile']['show_phone_on_landing'] ?? false,
        ];

        if (!empty($data['password'])) {
            $adminData['password'] = $data['password'];
        }

        if (isset($data['adminProfile']['photo_path'])) {
            $adminData['photo_path'] = $data['adminProfile']['photo_path'];
        }

        return $adminData;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        return app(AdminPrivilegeService::class)->updateMainAdmin($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Admin utama berhasil diperbarui';
    }
}