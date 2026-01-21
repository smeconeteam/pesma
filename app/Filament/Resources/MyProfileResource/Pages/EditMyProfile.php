<?php

namespace App\Filament\Resources\MyProfileResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Filament\Resources\MyProfileResource;

class EditMyProfile extends EditRecord
{
    protected static string $resource = MyProfileResource::class;

    public function mount(int | string $record = null): void
    {
        // Gunakan user yang sedang login
        $this->record = Auth::user();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    public function getTitle(): string
    {
        return 'Edit Profile';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Batal')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->url(MyProfileResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan')
                ->icon('heroicon-o-check')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load admin profile data jika ada
        if ($this->record->adminProfile) {
            $data['adminProfile'] = $this->record->adminProfile->toArray();
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle password update
        if (isset($data['new_password']) && filled($data['new_password'])) {
            $data['password'] = Hash::make($data['new_password']);
        }
        
        // Hapus field yang tidak perlu disimpan di tabel users
        unset($data['new_password']);
        unset($data['new_password_confirmation']);
        
        // Handle admin profile photo deletion
        if (isset($data['adminProfile']['photo_path'])) {
            $oldPhotoPath = $this->record->adminProfile?->getOriginal('photo_path');
            
            // Jika foto lama ada dan foto baru berbeda
            if ($oldPhotoPath && $oldPhotoPath !== $data['adminProfile']['photo_path']) {
                // Hapus foto lama dari storage
                if (Storage::disk('public')->exists($oldPhotoPath)) {
                    Storage::disk('public')->delete($oldPhotoPath);
                }
            }
        }
        
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile berhasil diupdate')
            ->body('Informasi profile Anda telah berhasil diperbarui.');
    }

    protected function getRedirectUrl(): string
    {
        return MyProfileResource::getUrl('index');
    }
}