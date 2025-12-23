<?php

namespace App\Filament\Resources\DiscountResource\Pages;

use App\Filament\Resources\DiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscount extends ViewRecord
{
    protected static string $resource = DiscountResource::class;

    public function getTitle(): string
    {
        return 'Detail Diskon';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->visible(fn () => ! ($this->record?->trashed() ?? false)),

            Actions\RestoreAction::make()
                ->visible(fn () => $this->record?->trashed() ?? false),
        ];
    }
}
