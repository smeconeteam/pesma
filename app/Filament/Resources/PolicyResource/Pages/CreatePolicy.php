<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getRedirectUrl(): string
    {
        // Setelah create -> balik ke view aktif
        return PolicyResource::getUrl('active');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set created_by ke user yang sedang login
        $data['created_by'] = auth()->id();

        return $data;
    }
}
