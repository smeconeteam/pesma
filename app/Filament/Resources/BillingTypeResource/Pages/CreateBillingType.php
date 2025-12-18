<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingType extends CreateRecord
{
    protected static string $resource = BillingTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return BillingTypeResource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Kalau berlaku untuk semua cabang, bersihkan pivot
        if ($this->record->applies_to_all) {
            $this->record->dorms()->detach();
        }
    }
}