<?php

namespace App\Filament\Resources\DormResource\Pages;

use App\Filament\Resources\DormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDorms extends ListRecords
{
    protected static string $resource = DormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
