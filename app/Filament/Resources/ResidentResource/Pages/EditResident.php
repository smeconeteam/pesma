<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\Country;
use Filament\Resources\Pages\EditRecord;

class EditResident extends EditRecord
{
    protected static string $resource = ResidentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pastikan relationship data loaded
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle WNI => auto set Indonesia
        if (isset($data['residentProfile'])) {
            if (($data['residentProfile']['citizenship_status'] ?? 'WNI') === 'WNI') {
                $indoId = Country::query()->where('iso2', 'ID')->value('id');
                if ($indoId) {
                    $data['residentProfile']['country_id'] = $indoId;
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
