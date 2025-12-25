<?php

namespace App\Filament\Resources\DiscountResource\Pages;

use App\Filament\Resources\DiscountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected function getRedirectUrl(): string
    {
        return DiscountResource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        if ($this->record->applies_to_all) {
            $this->record->dorms()->detach();
        }
    }
}
