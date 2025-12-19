<?php

namespace App\Filament\Resources\EmergencyNumberResource\Pages;

use App\Filament\Resources\EmergencyNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmergencyNumber extends EditRecord
{
    protected static string $resource = EmergencyNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
