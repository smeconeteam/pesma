<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillingTypes extends ListRecords
{
    protected static string $resource = BillingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
