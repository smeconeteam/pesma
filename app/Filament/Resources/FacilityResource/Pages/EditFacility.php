<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Filament\Resources\FacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacility extends EditRecord
{
    protected static string $resource = FacilityResource::class;

    protected static ?string $title = 'Ubah Fasilitas';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['activeTab' => $this->record->type]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Fasilitas berhasil diperbarui';
    }
}