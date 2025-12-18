<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillingType extends EditRecord
{
    protected static string $resource = BillingTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return BillingTypeResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Kalau berlaku untuk semua cabang, bersihkan pivot
        if ($this->record->applies_to_all) {
            $this->record->dorms()->detach();
        }
    }
}
