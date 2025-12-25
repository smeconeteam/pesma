<?php

namespace App\Filament\Resources\DiscountResource\Pages;

use App\Filament\Resources\DiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;

    protected function getRedirectUrl(): string
    {
        return DiscountResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(), // soft delete
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->applies_to_all) {
            $this->record->dorms()->detach();
        }
    }
}
