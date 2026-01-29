<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Filament\Resources\FacilityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFacility extends CreateRecord
{
    protected static string $resource = FacilityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['activeTab' => $this->record->type]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Fasilitas berhasil ditambahkan';
    }
}