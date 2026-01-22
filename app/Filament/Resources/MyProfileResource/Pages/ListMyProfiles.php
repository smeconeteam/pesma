<?php

namespace App\Filament\Resources\MyProfileResource\Pages;

use App\Filament\Resources\MyProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMyProfiles extends ListRecords
{
    protected static string $resource = MyProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
