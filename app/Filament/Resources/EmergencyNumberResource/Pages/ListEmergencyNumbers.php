<?php

namespace App\Filament\Resources\EmergencyNumberResource\Pages;

use App\Filament\Resources\EmergencyNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmergencyNumbers extends ListRecords
{
    protected static string $resource = EmergencyNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
