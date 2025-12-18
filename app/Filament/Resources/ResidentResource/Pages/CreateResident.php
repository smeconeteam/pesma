<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateResident extends CreateRecord
{
    protected static string $resource = ResidentResource::class;

    protected function handleRecordCreation(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1) Buat user
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make('123456789'),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            // 2) Attach role resident
            $residentRole = Role::firstOrCreate(['name' => 'resident']);
            $user->roles()->syncWithoutDetaching([$residentRole->id]);

            // 3) Ambil data profile dari nested form
            $profileData = $data['residentProfile'] ?? [];

            // 4) Handle WNI => auto set Indonesia
            if (($profileData['citizenship_status'] ?? 'WNI') === 'WNI') {
                $indoId = Country::query()->where('iso2', 'ID')->value('id');
                if ($indoId) {
                    $profileData['country_id'] = $indoId;
                }
            }

            // 5) Buat profile
            $user->residentProfile()->create($profileData);

            Notification::make()
                ->title('Berhasil')
                ->body('Penghuni berhasil dibuat.')
                ->success()
                ->send();

            return $user;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
